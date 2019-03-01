<?php

namespace Deployer;

require 'recipe/common.php';

// Project name
set('application', 'csmc');

// Project repository
set('repository', 'https://bitbucket.org/CSMC/ui-update.git');

set('branch', 'master');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true);

set('git_recursive', false);

set('allow_anonymous_stats', false);

set('http_user', 'apache');

// set('writeable_use_sudo', true);
// set('writeable_mode', 'chmod');
//
// set('cleanup_use_sudo', true);

/**
 * Symfony Configuration
 */

// Symfony build set
set('symfony_env', 'prod');

// Symfony shared dirs
set('shared_dirs', [
    'symfony/node_modules',
    'symfony/vendor',
    'symfony/var',
    'symfony/public/uploads'
]);

// Symfony shared files
set('shared_files', [
    'symfony/.env',
]);

// Symfony writable dirs
set('writable_dirs', [
    'symfony/var',
    'symfony/public/uploads'
]);

// Clear paths
set('clear_paths', []);

// Assets
set('assets', [
    'symfony/public/build'
]);

// Environment vars
set('env', function () {
    return [
        'APP_ENV' => get('symfony_env')
    ];
});

//
set('bin_dir', 'symfony/bin');
set('var_dir', 'symfony/var');

// Symfony console bin
set('bin/console', function () {
    return sprintf('{{release_path}}/%s/console', trim(get('bin_dir'), '/'));
});

// Symfony console opts
set('console_options', function () {
    $options = '--no-interaction --env={{symfony_env}}';
    return get('symfony_env') !== 'prod' ? $options : sprintf('%s --no-debug', $options);
});

// Hosts
host('csmcuser@csmc.utdallas.edu')
    ->set('deploy_path', '/var/www/{{application}}');

// Tasks

/**
 * Create cache dir
 */
task('deploy:create_cache_dir', function () {
    // Set cache dir
    set('cache_dir', '{{release_path}}/' . trim(get('var_dir'), '/') . '/cache');
    // Remove cache dir if it exist
    run('if [ -d "{{cache_dir}}" ]; then rm -rf {{cache_dir}}; fi');
    // Create cache dir
    run('mkdir -p {{cache_dir}}');
    // Set rights
    run("chmod -R g+w {{cache_dir}}");
})->desc('Create cache dir');

/**
 * Normalize asset timestamps
 */
task('deploy:assets', function () {
    $assets = implode(' ', array_map(function ($asset) {
        return "{{release_path}}/$asset";
    }, get('assets')));
    run(sprintf('find %s -exec touch -t %s {} \';\' &> /dev/null || true', $assets, date('YmdHi.s')));
})->desc('Normalize asset timestamps');

task('deploy:assets:install', function() {
   run('cd {{release_path}}/symfony && yarn install && yarn run encore production');
});

/**
 * Clear Cache
 */
task('deploy:cache:clear', function () {
    run('{{bin/php}} {{bin/console}} cache:clear {{console_options}} --no-warmup');
})->desc('Clear cache');

/**
 * Warm up cache
 */
task('deploy:cache:warmup', function () {
    run('{{bin/php}} {{bin/console}} cache:warmup {{console_options}}');
})->desc('Warm up cache');

/**
 * Migrate database
 */
task('database:migrate', function () {
    run('{{bin/php}} {{bin/console}} doctrine:migrations:migrate {{console_options}} --allow-no-migration');
})->desc('Migrate database');

task('deploy:vendors', function () {
    if (!commandExist('unzip')) {
        writeln('<comment>To speed up composer installation setup "unzip" command with PHP zip extension https://goo.gl/sxzFcD</comment>');
    }
    run('cd {{release_path}}/symfony && {{bin/composer}} {{composer_options}}');
})->desc('');

/**
 * Main task
 */
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:clear_paths',
    'deploy:create_cache_dir',
    'deploy:shared',
    'deploy:vendors',
    'deploy:assets:install',
    'deploy:assets',
    'deploy:cache:clear',
    'deploy:cache:warmup',
    'deploy:writable',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
])->desc('Deploy your project');
// Display success message on completion
after('deploy', 'success');

// [Optional] If deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');
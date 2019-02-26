var Encore = require('@symfony/webpack-encore');

Encore
// the project directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // the public path used by the web server to access the previous directory
    .setPublicPath('/build')
    // .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning()

    .addStyleEntry('css/global', './assets/css/global.scss')
    .addStyleEntry('css/admin', './assets/css/admin.scss')
    .addStyleEntry('css/dev', './assets/css/dev.scss')

    .addEntry('js/images/swipe-background', './assets/images/swipe-background.png')
    .addEntry('js/images/favicon', './assets/images/favicon.png')
    .addEntry('js/images/logo-csmc', './assets/images/logo-csmc.png')
    .addEntry('js/images/logo-utdallas', './assets/images/logo-utdallas.png')
    .addEntry('js/images/user', './assets/images/user.png')
    .addEntry('js/images/ajax-loader', './assets/images/ajax-loader.gif')

    .enableTypeScriptLoader()
    .enableVueLoader()
    .enableSassLoader()
;

module.exports = Encore.getWebpackConfig();
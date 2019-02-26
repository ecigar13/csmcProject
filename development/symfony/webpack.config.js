var Encore = require('@symfony/webpack-encore');

Encore
// the project directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // the public path used by the web server to access the previous directory
    .setPublicPath('/build')
    // .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning()

    .addEntry('js/fastclick', 'fastclick/lib/fastclick.js')
    .addEntry('js/nprogress', 'nprogress/nprogress.js')

    .addStyleEntry('css/global', './assets/css/global.scss')
    .addStyleEntry('css/admin', './assets/css/admin.scss')
    .addStyleEntry('css/custom', './assets/css/custom.scss')
    .addStyleEntry('css/display', './assets/css/display.scss')
    .addStyleEntry('css/mentor_profile', './assets/css/mentor_profile.scss')
    .addStyleEntry('css/edit_profile', './assets/css/edit_profile.scss')
    .addStyleEntry('css/view_summary', './assets/css/view_summary.css')
    .addStyleEntry('css/occurrence_submission', './assets/css/occurrence_submission.scss')
    
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
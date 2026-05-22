const Encore = require('@symfony/webpack-encore');

// Configure runtime environment
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // Output folder
    .setOutputPath('public/build/')
    .setPublicPath('/build')

    /*
     * ENTRY CONFIG
     * Each entry generates one JS (and optional CSS) file.
     * Page-specific CSS is imported inside its JS file.
     */
    .addEntry('app', './assets/app.js')
    .addEntry('dashboard', './assets/scripts/dashboard.js')     // Imports dashboard.css inside
    .addEntry('orderindex', './assets/scripts/orderindex.js')   // Imports orderindex.css inside
    .addEntry('usersindex', './assets/scripts/usersindex.js')   // Imports usersindex.css inside
    .addEntry('orderedit', './assets/scripts/orderedit.js')     // Imports orderedit.css inside
    .addEntry('serviceindex', './assets/scripts/serviceindex.js') // Imports serviceindex.css inside
    .addEntry('servicenew', './assets/scripts/servicenew.js') // Imports servicenew.css inside
    .addEntry('productindex', './assets/scripts/productindex.js') // Products list page
    .addEntry('home', './assets/styles/home.css')
   

    // ✅ New entry for New Order page
    .addEntry('ordernew', './assets/scripts/ordernew.js')   // Imports ordersform.css inside

    // Page-independent CSS
    .addStyleEntry('form', './assets/styles/form.css')
    .addStyleEntry('view', './assets/styles/view.css')

    // Stimulus controllers
    .enableStimulusBridge('./assets/controllers.json')

    // Optimization
    .splitEntryChunks()
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()

    // Build notifications
    .enableBuildNotifications()

    // Source maps
    .enableSourceMaps(!Encore.isProduction())

    // Versioning for cache-busting
    .enableVersioning(Encore.isProduction())

    // Babel config
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.38';
    })

    // PostCSS
    .enablePostCssLoader()


    // Uncomment if needed
    // .enableSassLoader()
    // .enableReactPreset()
    // .enableIntegrityHashes(Encore.isProduction())
    // .autoProvidejQuery()
;

module.exports = Encore.getWebpackConfig();

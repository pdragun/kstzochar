import Encore from '@symfony/webpack-encore';

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.mjs file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath('/build')
    // only needed for CDN's or subdirectory deploy
    //.setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    .addEntry('app', './assets/js/app.js')
    .addEntry('a2lixSfCollection', './assets/js/a2lixSfCollection.js')
    .addEntry('newLocation', './assets/js/newLocation.js')
    .addEntry('cookieConsent', './assets/js/cookieConsent.js')
    .addStyleEntry('css/app', ['./assets/scss/app.scss'])

    .copyFiles([
        {
            from: './assets/images',
            // optional target path, relative to the output dir
            to: 'images/[path][name].[ext]',

            // if versioning is enabled, add the file hash too
            //to: 'images/[path][name].[hash:8].[ext]',

            // only copy files matching this pattern
            pattern: /\.(png|jpg|jpeg|svg|ico)$/},
        {
            from: './assets/downloads',
            // optional target path, relative to the output dir
            to: 'downloads/[path][name].[ext]',

            // if versioning is enabled, add the file hash too
            //to: 'images/[path][name].[hash:8].[ext]',

            // only copy files matching this pattern
            pattern: /\.(xlsx|xls|doc|docx|rtf|pdf)$/},
            {from: './vendor/friendsofsymfony/ckeditor-bundle/src/Resources/public/', to: 'ckeditor/[path][name].[ext]', pattern: /\.(js|css)$/, includeSubdirectories: false},
            {from: './vendor/friendsofsymfony/ckeditor-bundle/src/Resources/public/adapters', to: 'ckeditor/adapters/[path][name].[ext]'},
            {from: './vendor/friendsofsymfony/ckeditor-bundle/src/Resources/public/lang', to: 'ckeditor/lang/[path][name].[ext]'},
            {from: './vendor/friendsofsymfony/ckeditor-bundle/src/Resources/public/plugins', to: 'ckeditor/plugins/[path][name].[ext]'},
            {from: './vendor/friendsofsymfony/ckeditor-bundle/src/Resources/public/skins', to: 'ckeditor/skins/[path][name].[ext]'}
        ]
        )

    // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
    .enableStimulusBridge('./assets/controllers.json')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    // configure Babel
    // .configureBabel((config) => {
    //     config.plugins.push('@babel/a-babel-plugin');
    // })

    // Babel 8 compiles for the browsers in package.json "browserslist" and adds no polyfills.

    // Encore 7 no longer minifies CSS on its own: cssnano does it in production.
    .configureCssMinimizerPlugin((options, MinimizerPlugin) => {
        options.minify = MinimizerPlugin.cssnanoMinify;
    })

    // enables Sass/SCSS support; Bootstrap 5's SCSS uses @import and functions that Dart Sass
    // deprecates, so warnings from node_modules and about app.scss's @import of it are silenced
    .enableSassLoader((options) => {
        options.sassOptions = {
            ...options.sassOptions,
            quietDeps: true,
            silenceDeprecations: ['import'],
        };
    })

    // uncomment if you use TypeScript
    .enableTypeScriptLoader()

    // uncomment if you use React
    //.enableReactPreset()

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    //.enableIntegrityHashes(Encore.isProduction())

    // uncomment if you're having problems with a jQuery plugin
    //.autoProvidejQuery()
;

export default await Encore.getWebpackConfig();

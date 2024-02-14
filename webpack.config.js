const Encore = require('@symfony/webpack-encore');
const path = require('path');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
  Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
  // directory where compiled assets will be stored
  .setOutputPath('public/build/')
  // public path used by the web server to access the output path
  .setPublicPath('/build')
  // only needed for CDN's or sub-directory deploy
  // .setManifestKeyPrefix('build/')

  /*
    * COPY FILES
    */
  .copyFiles({
    from: 'assets/img',
    to: 'img/[path][name].[hash:8].[ext]',
  })

  /*
    * ENTRY CONFIG
    *
    * Each entry will result in one JavaScript file (e.g. app.js)
    * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
    */
  .addEntry('admin', './assets/js/admin/index.ts')
  .addEntry('public', './assets/js/public/index.ts')

  .addEntry('worker-charts', './assets/js/misc/charts.js')

  .enableTypeScriptLoader()

  // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
  .splitEntryChunks()

  .enableVueLoader(() => { }, { runtimeCompilerBuild: true })

  // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
  .enableStimulusBridge('./assets/js/admin/controllers.json')

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
  .enableBuildNotifications()
  .enableSourceMaps(!Encore.isProduction())
  // enables hashed filenames (e.g. app.abc123.css)
  .enableVersioning(Encore.isProduction())

  .configureBabel((config) => {
    config.plugins.push('@babel/plugin-proposal-class-properties');
  })

  // enables @babel/preset-env polyfills
  .configureBabelPresetEnv((config) => {
    config.useBuiltIns = 'usage';
    config.corejs = 3;
  })

  .enablePostCssLoader()

  .addAliases({
    /**
     * The import aliases below are defined in multiple files. So remember to update them all if you change one.
     * - jest.config.js
     * - tsconfig.json
     * - webpack.config.js
     */

    '@js': path.resolve(__dirname, 'assets/js/'),
    '@fonts': path.resolve(__dirname, 'assets/fonts/'),
    '@img': path.resolve(__dirname, 'assets/img/'),
    '@styles': path.resolve(__dirname, 'assets/styles/'),
    '@test': path.resolve(__dirname, 'assets/js/test/'),
    '@utils': path.resolve(__dirname, 'assets/js/utils/'),
    '@admin-fe': path.resolve(__dirname, 'assets/js/admin/vue/'),
  });

// uncomment to get integrity="..." attributes on your script & link tags
// requires WebpackEncoreBundle 1.4 or higher
// .enableIntegrityHashes(Encore.isProduction())

module.exports = Encore.getWebpackConfig();

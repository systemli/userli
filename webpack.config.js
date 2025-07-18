const Encore = require("@symfony/webpack-encore");
const CopyWebpackPlugin = require("copy-webpack-plugin");

Encore
  // directory where compiled assets will be stored
  .setOutputPath("public/build/")
  // public path used by the web server to access the output path
  .setPublicPath("/build")
  // only needed for CDN's or sub-directory deploy
  //.setManifestKeyPrefix('build/')

  // Tell Webpack to *not* output a separate runtime.js file.
  .disableSingleRuntimeChunk()

  /*
   * ENTRY CONFIG
   *
   * Add 1 entry for each "page" of your app
   * (including one that's included on every page - e.g. "app")
   *
   * Each entry will result in one JavaScript file (e.g. app.js)
   * and one CSS file (e.g. app.css) if you JavaScript imports CSS.
   */
  .addEntry("app", "./assets/js/app.js")

  // impossible, because webpack tries to find the included images
  //.addStyleEntry('AdminLTE.min.css', './assets/css/AdminLTE.min.css')

  .cleanupOutputBeforeBuild()
  .enableSourceMaps(!Encore.isProduction())
  // enables hashed filenames (e.g. app.abc123.css)
  .enableVersioning(Encore.isProduction())

  // uncomment if you use TypeScript
  //.enableTypeScriptLoader()

  // uncomment if you use Sass/SCSS files
  //.enableSassLoader()

  // Enable PostCSS support for Tailwind CSS
  .enablePostCssLoader()

  .addPlugin(
    new CopyWebpackPlugin({
      patterns: [{ from: "./assets/images", to: "images" }],
    })
  );

module.exports = Encore.getWebpackConfig();

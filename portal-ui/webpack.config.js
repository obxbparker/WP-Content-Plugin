const defaultConfig = require('@wordpress/scripts/config/webpack.config');

module.exports = {
    ...defaultConfig,
    plugins: defaultConfig.plugins.filter(
        (plugin) => plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
    ),
};

/* jshint node:true */
module.exports = function( grunt ) {
	'use strict';

	var config = {};

	config.shell = {
		options: {
			execOptions: {
				maxBuffer: 1000 * 1000 * 1000,
			},
		},
		makepot: {
			command: 'wp i18n make-pot ./woocommerce ./woocommerce/i18n/languages/woocommerce-plugin-framework.pot --domain=woocommerce-plugin-framework --package-name=\'SkyVerge WooCommerce Plugin Framework\''
		}
	};

	return config;
};

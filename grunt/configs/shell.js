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
			command: 'wp i18n make-pot . ./woocommerce/i18n/languages/woocommerce-plugin-framework.pot --include=woocommerce --domain=woocommerce-plugin-framework'
		}
	};

	return config;
};

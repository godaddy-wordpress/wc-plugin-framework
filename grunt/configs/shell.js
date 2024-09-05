/* jshint node:true */
module.exports = function () {
	'use strict';

	let headers = {
		"Report-Msgid-Bugs-To": "https://github.com/godaddy-wordpress/wc-plugin-framework/issues",
		"Last-Translator": "plugins@godaddy.com",
		"Language-Team": "plugins@godaddy.com",
		"Project-Id-Version": "SkyVerge WooCommerce Plugin Framework"
	};

	return {
		shell: {
			options: {
				execOptions: {
					maxBuffer: 1000 * 1000 * 1000,
				},
			},
			makepot: {
				command: [
					'wp i18n make-pot . ./woocommerce/i18n/languages/woocommerce-plugin-framework.pot',
					'--include="woocommerce"',
					'--domain="woocommerce-plugin-framework"',
					`--headers='${JSON.stringify(headers)}'`,
					'--file-comment="Copyright (c) GoDaddy Operating Company, LLC. All Rights Reserved."'
				].join(' ')
			}
		}
	};
};

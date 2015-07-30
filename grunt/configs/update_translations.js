/* jshint node:true */
module.exports = function( grunt ) {
	'use strict';

	var config = {};

	// The update_translations task updates any existing translations with
	// messages from the POT file and compiles them to MO files.
	config.update_translations = {
		'framework': {
			options: {
				potFile: 'woocommerce/i18n/languages/sv-wc-plugin-framework.pot',
			},
			expand: true,
			src: [ 'woocommerce/i18n/languages/**.po' ],
		}
	};

	return config;
};

/* jshint node:true */
module.exports = function( grunt ) {
	'use strict';

	var config = {};

	// The makepot task extracts gettext messages from source
	// code and generates the POT file
	config.makepot = {
		framework: {
			options: {
				cwd: 'woocommerce',
				domainPath: 'i18n/languages',
				exclude: [],
				potFilename: 'woocommerce-plugin-framework.pot',
				mainFile: 'index.php',
				potHeaders: {
					'report-msgid-bugs-to': 'https://support.woocommerce.com/hc/',
					'project-id-version': '<%= pkg.title %> <%= pkg.version %>',
				},
				processPot: function( pot ) {
					delete pot.headers['x-generator'];
					return pot;
				}, // jshint ignore:line
				type: 'wp-plugin',
				updateTimestamp: false,
				updatePoFiles: true,
			}
		}
	};

	return config;
};

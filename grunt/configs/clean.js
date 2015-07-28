/* jshint node:true */
module.exports = function( grunt ) {
	'use strict';

	var util = grunt.option( 'util' );
	var _ = require( 'underscore' );
	var config = {};

	// Delete source map from the CoffeeScript compilation
	config.clean = {
		options: {
			force: true
		},
		clean: [
			// Delete map files
			'woocommerce/payment-gateway/assets/js/frontend/*.min.js.map'
		]
	};

	return config;
};

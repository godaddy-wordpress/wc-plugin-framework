/* jshint node:true */
module.exports = function( grunt ) {
	'use strict';
	var config = {};

	// Add Uglify task with legacy rules
	config.uglify = {
		uglify: {
			options : {
				sourceMap: true,
				sourceMapIncludeSources: true,
				sourceMapIn: '<%= dirs.gateway.js %>/frontend/sv-wc-payment-gateway-frontend.min.js.map', // input sourcemap from CoffeeScript compilation
				sourceMapName: '<%= dirs.gateway.js %>/frontend/sv-wc-payment-gateway-frontend.min.map'
			},
			files : [{
				src: [ '<%= dirs.gateway.js %>/js/frontend/sv-wc-payment-gateway-frontend.min.js' ], // uglify JS from CoffeeScript compilation
				dest: '<%= dirs.gateway.js %>/frontend/sv-wc-payment-gateway-frontend.min.js'
			}]
		}
	}

	return config;
};

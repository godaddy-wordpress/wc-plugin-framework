/* jshint node:true */
module.exports = function( grunt ) {
	'use strict';
	var config = {};

	config.uglify = {
		// Add Uglify task with legacy rules
		fromCoffee: {
			options : {
				sourceMap: true,
				sourceMapIncludeSources: true,
				sourceMapIn: '<%= dirs.gateway.js %>/frontend/sv-wc-payment-gateway-frontend.min.js.map', // input sourcemap from CoffeeScript compilation
				sourceMapName: '<%= dirs.gateway.js %>/frontend/sv-wc-payment-gateway-frontend.min.map'
			},
			files : [{
				src: [ '<%= dirs.gateway.js %>/js/frontend/sv-wc-payment-gateway-frontend.min.js' ], // uglify JS from CoffeeScript compilation
				dest: '<%= dirs.gateway.js %>/frontend/sv-wc-payment-gateway-frontend.min.js'
			}],
		},
		fromJs: {
			options: {
				sourceMap: true,
				compress: true
			},
			files: [{
				expand: true,
				src: [ '<%= dirs.gateway.js %>/frontend/*.js', '!<%= dirs.gateway.js %>/frontend/*.min.js', '!<%= dirs.gateway.js %>/js/frontend/*.min.js' ], // uglify non-minified ES6 JS
				dest: '<%= dirs.gateway.js %>/frontend',
				cwd: '.',
				rename: function (dst, src) {
					return src.replace('.js', '.min.js');
				}
			}]
		}
	}

	return config;
};

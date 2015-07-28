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
				sourceMapIn: 'woocommerce/payment-gateway/assets/js/frontend/sv-wc-payment-gateway-frontend.min.js.map', // input sourcemap from CoffeeScript compilation
				sourceMapName: 'woocommerce/payment-gateway/assets/js/frontend/sv-wc-payment-gateway-frontend.min.map'
			},
			files : [{
				src: [ 'woocommerce/payment-gateway/assets/js/frontend/sv-wc-payment-gateway-frontend.min.js' ], // uglify JS from CoffeeScript compilation
				dest: 'woocommerce/payment-gateway/assets/js/frontend/sv-wc-payment-gateway-frontend.min.js'
			}]
		}
	}

	return config;
};

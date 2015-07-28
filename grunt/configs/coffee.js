/* jshint node:true */
module.exports = function( grunt ) {
	'use strict';

	var config = {};

	// Compile CoffeeScript
	config.coffee = {
		compile: {
			options: {
				sourceMap: true
			},
			files: [
				{
					expand: true,
					cwd: 'woocommerce/payment-gateway/assets/js/admin/',
					dest: 'woocommerce/payment-gateway/assets/js/admin/',
					src: '*.coffee',
					ext: '.min.js'
				},
				{
					expand: true,
					cwd: 'woocommerce/payment-gateway/assets/js/frontend/',
					dest: 'woocommerce/payment-gateway/assets/js/frontend/',
					src: '*.coffee',
					ext: '.min.js'
				}
			]
		}
	};

	return config;
};

/* jshint node:true */
module.exports = function( grunt ) {
	'use strict';

	var config = {};

	// Compile all .scss files.
	config.sass = {
		compile: {
			options: {
				style: 'compressed',
				sourcemap: true
			},
			files: [
				{
					expand: true,
					cwd: 'woocommerce/payment-gateway/assets/css/admin/',
					dest: 'woocommerce/payment-gateway/assets/css/admin/',
					src: ['*.scss', '!_*.scss'],
					ext: '.min.css'
				},
				{
					expand: true,
					cwd: 'woocommerce/payment-gateway/assets/css/frontend/',
					dest: 'woocommerce/payment-gateway/assets/css/frontend/',
					src: ['*.scss', '!_*.scss'],
					ext: '.min.css'
				}
			]
		}
	};

	return config;
};

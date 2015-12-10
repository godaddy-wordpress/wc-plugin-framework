/* jshint node:true */
module.exports = function( grunt ) {
	'use strict';

	var config = {};

	// Delete source map from the CoffeeScript compilation
	config.clean = {
		options: {
			force: true
		},
		clean: [
			// Delete map files
			'<%= dirs.gateway.js %>/frontend/*.min.js.map'
		]
	};

	return config;
};

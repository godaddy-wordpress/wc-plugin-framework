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
					cwd: '<%= dirs.general.js %>/admin/',
					dest: '<%= dirs.general.js %>/admin/',
					src: '*.coffee',
					ext: '.min.js'
				}
			]
		}
	};

	return config;
};

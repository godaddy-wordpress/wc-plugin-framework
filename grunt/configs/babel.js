/* jshint node:true */
module.exports = function( grunt ) {
	'use strict';
	var config = {};

	config.babel = {
		babel: {
			options : {
				sourceMap: true,
				presets: ['@babel/preset-env']
			},
			files : [
				{
					expand: true,
					cwd: '<%= dirs.gateway.js %>/frontend/',
					dest: '<%= dirs.gateway.js %>/frontend/',
					src: ['*.js', '!*.min.js'],
					ext: '.min.js'
				}
			]
		}
	}

	return config;
};

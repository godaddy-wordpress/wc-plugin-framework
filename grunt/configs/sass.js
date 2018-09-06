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
					cwd: '<%= dirs.general.css %>/admin/',
					dest: '<%= dirs.general.css %>/admin/',
					src: ['*.scss', '!_*.scss'],
					ext: '.min.css'
				},
				{
					expand: true,
					cwd: '<%= dirs.gateway.css %>/admin/',
					dest: '<%= dirs.gateway.css %>/admin/',
					src: ['*.scss', '!_*.scss'],
					ext: '.min.css'
				},
				{
					expand: true,
					cwd: '<%= dirs.gateway.css %>/frontend/',
					dest: '<%= dirs.gateway.css %>/frontend/',
					src: ['*.scss', '!_*.scss'],
					ext: '.min.css'
				}
			]
		}
	};

	return config;
};

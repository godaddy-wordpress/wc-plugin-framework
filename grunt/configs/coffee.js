/* jshint node:true */
module.exports = function () {
	'use strict';

	// Compile CoffeeScript
	return {
		coffee: {
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
		}
	};
};

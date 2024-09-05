/* jshint node:true */
module.exports = function () {
	'use strict';

	// The potomo task compiles PO files to MO files
	return {
		potomo: {
			framework: {
				options: {
					poDel: false,
				},
				files: [{
					expand: true,
					cwd: '<%= dirs.lang %>',
					src: ['*.po'],
					dest: '<%= dirs.lang %>',
					ext: '.mo',
					nonull: true
				}]
			}
		}
	};
};

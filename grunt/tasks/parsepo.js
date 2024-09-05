/* jshint node:true */
module.exports = async function (grunt) {
	'use strict';

	const fs = await import('fs'),
		gp = await import('gettext-parser');

	// Parse and adjust PO headers.
	grunt.registerTask('parsepo', 'Custom parse PO task.', function () {

		let files = grunt.file.expand(grunt.config('dirs.lang') + '/*.po');

		if (!files.length) {
			return;
		}

		files.forEach(function (file) {

			let input = fs.readFileSync(file),
				po = gp.po.parse(input);

			// Set PO file headers to reflect the current version number.
			po.headers['project-id-version'] = grunt.config('pkg.title') + ' ' + grunt.config('pkg.version');

			fs.writeFileSync(file, gp.po.compile(po));
		});

	});

};

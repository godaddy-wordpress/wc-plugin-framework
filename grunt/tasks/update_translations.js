/* jshint node:true */
module.exports = function( grunt ) {
	'use strict';

	// Register custom update_translations task
	grunt.registerMultiTask( 'update_translations', function() {

		var target  = this.target,
				options = this.options();

		this.files.forEach( function ( f, i ) {
			f.src.forEach( function ( filepath ) {

				var locale   = filepath.substring( filepath.lastIndexOf( '-' ) + 1, filepath.lastIndexOf( '.' ) ),
						msgmerge = 'msgmerge ' + filepath + ' ' + options.potFile + ' -U --backup=off',
						msgfmt   = 'msgfmt -o ' + filepath.replace( '.po', '.mo' ) + ' ' + filepath;

				grunt.config( 'shell.msgmerge-' + target + '-' + locale + '.command', msgmerge );
				grunt.config( 'shell.msgfmt-'   + target + '-' + locale + '.command', msgfmt );

				grunt.task.run( 'shell:msgmerge-' + target + '-' + locale );
				grunt.task.run( 'shell:msgfmt-' + target + '-' + locale );
			});
		});
	});

};

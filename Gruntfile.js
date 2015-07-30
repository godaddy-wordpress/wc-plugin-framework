/* jshint node:true */
<<<<<<< HEAD
module.exports = function( grunt ) {
	'use strict';

	// load all grunt tasks matching the `grunt-*` pattern
	require( 'load-grunt-tasks' )( grunt );

	// Show elapsed time
	require( 'time-grunt' )( grunt );

	var _    = require( 'underscore' );
	var path = require( 'path' );

	// Set plugin slug option
	//grunt.option( 'plugin-slug', path.basename( process.cwd() ) );

	var gruntConfig = {};

	// options
	gruntConfig.options = {};

	// Set folder templates
	gruntConfig.dirs = {
		css: 'assets/css',
		js: 'assets/js',
		images: 'assets/images',
		fonts: 'assets/fonts',
		build: 'build'
	};

	function loadConfig( filepath ) {
		var object = {};
		var key;

		filepath = path.normalize( path.resolve( process.cwd(), filepath ) + '/' )

		var files = grunt.file.glob.sync( '*', { cwd: filepath } );

		files.forEach( function( option ) {
			key = option.replace(/\.js$/,'');
			object = _.extend( object, require( filepath + option )( grunt ) );
		});

		return object;
	};

	// load task configs
	gruntConfig = _.extend( gruntConfig, loadConfig( './grunt/configs/' ) );

	// Init Grunt
	grunt.initConfig( gruntConfig );

	// Register Tasks
	grunt.registerTask( 'default', [
		'coffee',
		'uglify',
		'sass',
		'clean'
	] );
=======
module.exports = function(grunt) {

	// Project configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		// The makepot task extracts gettext messages from source
		// code and generates the POT file
		makepot: {
			'framework': {
				options: {
					cwd: 'woocommerce',
					domainPath: 'i18n/languages',
					exclude: [],
					potFilename: 'sv-wc-plugin-framework.pot',
					mainFile: 'index.php',
					potHeaders: {
						'report-msgid-bugs-to': 'https://support.woothemes.com/hc/',
						'project-id-version': 'SkyVerge WooCommerce Plugin Framework <%= pkg.version %>',
					},
					processPot: function( pot ) {
						delete pot.headers['x-generator'];
						return pot;
					}, // jshint ignore:line
					type: 'wp-plugin',
					updateTimestamp: false
				}
			}
		},

		// The update_translations task updates any existing translations with
		// messages from the POT file and compiles them to MO files.
		update_translations: {
			'framework': {
				options: {
					potFile: 'woocommerce/i18n/languages/sv-wc-plugin-framework.pot',
				},
				expand: true,
				src: [ 'woocommerce/i18n/languages/**.po' ],
			}
		},

	});

	// Load NPM tasks
	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-shell' );

	// Register custom update_translations task
	grunt.registerMultiTask('update_translations', function() {

		var target  = this.target,
				options = this.options();

		this.files.forEach(function (f, i) {
			f.src.forEach(function (filepath) {

				var locale   = filepath.substring( filepath.lastIndexOf( '-' ) + 1, filepath.lastIndexOf('.') ),
						msgmerge = 'msgmerge ' + filepath + ' ' + options.potFile + ' -U --backup=off';
						msgfmt   = 'msgfmt -o ' + filepath.replace( '.po', '.mo' ) + ' ' + filepath;

				grunt.config( 'shell.msgmerge-' + target + '-' + locale + '.command', msgmerge );
				grunt.config( 'shell.msgfmt-'   + target + '-' + locale + '.command', msgfmt );

				grunt.task.run( 'shell:msgmerge-' + target + '-' + locale );
				grunt.task.run( 'shell:msgfmt-' + target + '-' + locale );
			});
		});

	});

	// Default task(s).
	grunt.registerTask('default', ['makepot', 'update_translations']);

>>>>>>> translations
};

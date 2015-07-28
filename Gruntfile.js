/* jshint node:true */
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
};

/* jshint node:true */
module.exports = function( grunt ) {
  'use strict';

  var fs = require('fs'),
      gp = require('gettext-parser'),
      util = grunt.option( 'util' );

  // Parse and adjust PO headers.
  grunt.registerTask( 'parsepo', 'Custom parse PO task.', function () {

    var files = grunt.file.expand( grunt.config( 'dirs.lang' ) + '/*.po' );

    if ( ! files.length ) {
      return;
    }

    files.forEach( function (file) {

      var input = fs.readFileSync( file ),
          po    = gp.po.parse( input );

      // Set PO file headers to reflect the current version number.
      po.headers['project-id-version'] = grunt.config( 'pkg.title' ) + ' ' + grunt.config( 'pkg.version' );

      fs.writeFileSync( file, gp.po.compile( po ) );
    } );

  } );

};

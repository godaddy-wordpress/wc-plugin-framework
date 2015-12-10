/* jshint node:true */
module.exports = function( grunt ) {
  'use strict';

  var config = {};

  // The potomo task compiles PO files to MO files
  config.potomo = {
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
  };

  return config;
};

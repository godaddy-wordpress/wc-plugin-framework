/* jshint node:true */
module.exports = function( grunt ) {
  'use strict';

  var config = {};

  // The update_translations task updates any existing translations with
  // messages from the POT file and compiles them to MO files.
  config.shell = {

    transifex_push: {
      command: 'tx push -s -t'
    },

    transifex_pull: {
      command: 'tx pull'
    },

  };

  return config;
};

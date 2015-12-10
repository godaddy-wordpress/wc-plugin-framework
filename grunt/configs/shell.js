/* jshint node:true */
module.exports = function( grunt ) {
  'use strict';

  var config = {};

  // The update_translations task updates any existing translations with
  // messages from the POT file and compiles them to MO files.
  config.shell = {

    tx_push: {
      options: {
        failOnError: false,
      },
      command: 'tx push -s --skip'
    },

    tx_pull: {
      options: {
        failOnError: false,
      },
      command: 'tx pull -a --skip '
    },

  };

  return config;
};

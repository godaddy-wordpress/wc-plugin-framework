<?php

const ABSPATH = 'foo/bar';

define('PLUGIN_ROOT_DIR', dirname(__DIR__));

require_once PLUGIN_ROOT_DIR.'/vendor/autoload.php';
require_once PLUGIN_ROOT_DIR.'/woocommerce/class-sv-wc-plugin.php';
require_once PLUGIN_ROOT_DIR.'/woocommerce/class-sv-wc-helper.php';
require_once PLUGIN_ROOT_DIR.'/woocommerce/class-sv-wc-plugin-exception.php';

WP_Mock::setUsePatchwork(true);
WP_Mock::bootstrap();

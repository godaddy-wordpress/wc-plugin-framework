<?php

const ABSPATH = 'foo/bar';

define('PLUGIN_ROOT_DIR', dirname(__DIR__));

require_once PLUGIN_ROOT_DIR.'/vendor/autoload.php';

WP_Mock::setUsePatchwork(true);
WP_Mock::bootstrap();

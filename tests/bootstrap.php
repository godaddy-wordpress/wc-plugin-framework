<?php

const ABSPATH = 'foo/bar';

define('PLUGIN_ROOT_DIR', dirname(__DIR__));

require_once PLUGIN_ROOT_DIR.'/vendor/autoload.php';

WP_Mock::setUsePatchwork(true);
WP_Mock::bootstrap();

if (! class_exists('WP_Error')) {
	require_once __DIR__ . '/Stubs/WP_Error.php';
}

if (! class_exists('WC_Subscriptions_Product')) {
	require_once __DIR__ . '/Stubs/WC_Subscriptions_Product.php';
}

if (! class_exists('WCS_ATT_Scheme')) {
	require_once __DIR__ . '/Stubs/WCS_ATT_Scheme.php';
}

if (! class_exists('WCS_ATT_Product_Schemes')) {
	require_once __DIR__ . '/Stubs/WCS_ATT_Product_Schemes.php';
}

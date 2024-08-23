<?php

const ABSPATH = 'foo/bar';

define('PLUGIN_ROOT_DIR', dirname(__DIR__));

require_once PLUGIN_ROOT_DIR.'/vendor/autoload.php';
require_once PLUGIN_ROOT_DIR.'/woocommerce/class-sv-wc-plugin.php';
require_once PLUGIN_ROOT_DIR.'/woocommerce/class-sv-wc-plugin-exception.php';
require_once PLUGIN_ROOT_DIR.'/woocommerce/Enums/Traits/EnumTrait.php';
require_once PLUGIN_ROOT_DIR.'/woocommerce/Enums/PaymentFormContext.php';
require_once PLUGIN_ROOT_DIR.'/woocommerce/Helpers/ArrayHelper.php';
require_once PLUGIN_ROOT_DIR.'/woocommerce/Traits/CanConvertToArrayTrait.php';
require_once PLUGIN_ROOT_DIR.'/woocommerce/Traits/IsSingletonTrait.php';

WP_Mock::setUsePatchwork(true);
WP_Mock::bootstrap();

require_once PLUGIN_ROOT_DIR.'/woocommerce/class-sv-wc-helper.php';

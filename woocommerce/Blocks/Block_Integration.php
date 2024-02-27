<?php
/**
 * WooCommerce Payment Gateway Framework
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the plugin to newer
 * versions in the future. If you wish to customize the plugin for your
 * needs please refer to http://www.skyverge.com
 *
 * @package   SkyVerge/WooCommerce/Payment-Gateway/Classes
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2023, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_12_1\Blocks;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use SkyVerge\WooCommerce\PluginFramework\v5_12_1\Blocks\Traits\Block_Integration_Trait;
use SkyVerge\WooCommerce\PluginFramework\v5_12_1\Payment_Gateway\Blocks\Gateway_Checkout_Block_Integration;
use SkyVerge\WooCommerce\PluginFramework\v5_12_1\SV_WC_Plugin;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_12_1\\Blocks\\Block_Integration' ) ) :

/**
 * Base class for handling support for WooCommerce blocks, like Cart or Checkout blocks.
 *
 * This is the base integration class that can be used by non-gateway plugins.
 * For gateways, {@see Gateway_Checkout_Block_Integration}.
 *
 * @since 5.12.0
 */
#[\AllowDynamicProperties]
abstract class Block_Integration implements IntegrationInterface {


	use Block_Integration_Trait;


	/** @var SV_WC_Plugin the current plugin */
	protected SV_WC_Plugin $plugin;

	/** @var string implementations should specify the block supported (e.g. 'cart' or 'checkout') */
	protected string $block_name;


	/**
	 * Block integration constructor.
	 *
	 * @since 5.12.0
	 *
	 * @param SV_WC_Plugin $plugin
	 */
	public function __construct( SV_WC_Plugin $plugin ) {

		$this->plugin = $plugin;

		$this->add_hooks();
	}


	/**
	 * Adds hooks.
	 *
	 * @since 5.12.0
	 *
	 * @return void
	 */
	protected function add_hooks() : void {

		// AJAX endpoint hooks for front-end logging
		$this->add_ajax_logging();
	}


}

endif;

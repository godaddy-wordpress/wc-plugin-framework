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
 * @package   SkyVerge/WooCommerce/Payment-Gateway/External_Checkout
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2023, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_11_9\Payment_Gateway\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use SkyVerge\WooCommerce\PluginFramework\v5_11_9\SV_WC_Payment_Gateway;
use SkyVerge\WooCommerce\PluginFramework\v5_11_9\SV_WC_Payment_Gateway_Plugin;
use SkyVerge\WooCommerce\PluginFramework\v5_11_9\Blocks\Traits\Block_Integration_Trait;

if ( ! class_exists( '\SkyVerge\WooCommerce\PluginFramework\v5_11_9\Payment_Gateway\Blocks\Gateway_Checkout_Block_Integration' ) ) :

/**
 * Base class for handling support for the WooCommerce Checkout block in gateways.
 *
 * For support in non-gateways, {@see Block_Integration}.
 *
 * @since 5.12.0
 */
abstract class Gateway_Checkout_Block_Integration extends AbstractPaymentMethodType
{

	use Block_Integration_Trait;


	/** @var SV_WC_Payment_Gateway_Plugin instance of the current plugin */
	protected SV_WC_Payment_Gateway_Plugin $plugin;

	/** @var SV_WC_Payment_Gateway gateway handling integration */
	protected SV_WC_Payment_Gateway $gateway;

	/** @var string supported block name */
	protected $block_name = 'checkout';


	/**
	 * Block integration constructor.
	 *
	 * @since 5.12.0
	 *
	 * @param SV_WC_Payment_Gateway_Plugin $plugin
	 * @param SV_WC_Payment_Gateway $gateway
	 */
	public function __construct( SV_WC_Payment_Gateway_Plugin $plugin, SV_WC_Payment_Gateway $gateway ) {

		$this->plugin   = $plugin;
		$this->gateway  = $gateway;
		$this->settings = $gateway->settings;
	}


	/**
	 * Gets the integration name.
	 *
	 * @return string
	 */
	public function get_name() {

		return $this->gateway->get_id();
	}


	/**
	 * Determines if the payment method is active in the checkout block context.
	 *
	 * @since 5.12.0
	 *
	 * @return bool
	 */
	public function is_active() {

		return $this->get_setting( 'enabled' ) === 'yes';
	}


	/**
	 * Gets the payment method script handles.
	 *
	 * Defaults to {@see get_script_handles()} but concrete implementations may override this.
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles() {

		return $this->get_script_handles();
	}


	/**
	 * Gets the payment method data.
	 *
	 * @since 5.12.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_script_data()
	{
		return [
			'title'       => $this->gateway->method_title,
			'description' => $this->gateway->method_description,
			'supports'    => $this->gateway->supports,
		];
	}


}

endif;

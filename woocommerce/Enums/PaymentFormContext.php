<?php
/**
 * WooCommerce Plugin Framework
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
 * @package   SkyVerge/WooCommerce/Plugin/Classes
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2024, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_9\Enums;

use SkyVerge\WooCommerce\PluginFramework\v5_15_9\Enums\Traits\EnumTrait;

/**
 * @since 5.13.0
 */
class PaymentFormContext
{
	use EnumTrait;

	/** @var string primary checkout page */
	public const Checkout = 'checkout';

	/** @var string separate payment page after the normal checkout flow */
	public const CheckoutPayPage = 'checkout_pay_page';

	/** @var string payment page usually accessed via a manual order ("Customer payment page" link in admin UI) */
	public const CustomerPayPage = 'customer_pay_page';
}

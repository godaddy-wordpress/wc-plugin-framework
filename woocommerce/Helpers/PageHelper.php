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
 * @copyright Copyright (c) 2013-2025, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_9\Helpers;

class PageHelper
{
	/**
	 * Determines whether the current page is the WooCommerce "Analytics" page.
	 *
	 * @since 5.15.4
	 */
	public static function isWooCommerceAnalyticsPage() : bool
	{
		if (! $controller = static::getWooCommercePageController()) {
			return false;
		}

		$pageData = $controller->get_current_page();

		return ArrayHelper::get($pageData, 'id') === 'woocommerce-analytics' ||
			ArrayHelper::get($pageData, 'parent') === 'woocommerce-analytics';
	}

	/**
	 * @codeCoverageIgnore
	 */
	protected static function getWooCommercePageController() : ?\Automattic\WooCommerce\Admin\PageController
	{
		if (! class_exists(\Automattic\WooCommerce\Admin\PageController::class)) {
			return null;
		}

		return \Automattic\WooCommerce\Admin\PageController::get_instance();
	}
}

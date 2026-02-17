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
 * @package   SkyVerge/WooCommerce/Helpers
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2026, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v6_0_2\Helpers;

class ScriptHelper
{
	/**
	 * Adds inline JavaScript.
	 *
	 * @since 6.0.1
	 *
	 * @param string $handle Handle name
	 * @param string $javaScriptString The JavaScript code to add inline
	 * @return bool True if successfully added
	 */
	public static function addInlineScript(string $handle, string $javaScriptString) : bool
	{
		if (did_action('wp_print_footer_scripts')) {
			_doing_it_wrong(__METHOD__, 'Inline scripts should be added before the wp_print_footer_scripts action.', '6.0.1');
		}

		if (! wp_script_is($handle, 'registered')) {
			wp_register_script($handle, false, [], false, true);
		}

		if (! wp_script_is($handle, 'enqueued')) {
			wp_enqueue_script($handle);
		}

		return wp_add_inline_script($handle, $javaScriptString);
	}

	/**
	 * Adds inline jQuery.
	 * This calls {@see static::addInlineScript()} but with automatic jQuery wrapping.
	 *
	 * @since 6.0.1
	 *
	 * @param string $handle Handle name
	 * @param string $javaScriptString The JavaScript code to add inline
	 * @return bool True if successfully added
	 */
	public static function addInlinejQuery(string $handle, string $javaScriptString) : bool
	{
		return static::addInlineScript(
			$handle,
			'jQuery(function($) { '.$javaScriptString.' });'
		);
	}
}

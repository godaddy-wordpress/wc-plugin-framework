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
 * @copyright Copyright (c) 2013-2020, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_6_1\Settings_API;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_6_1\\Settings_API\\Control' ) ) :

/**
 * The base control object.
 *
 * @since x.y.z
 */
class Control {


	/** @var string the text control type */
	const TYPE_TEXT = 'text';

	/** @var string the textarea control type */
	const TYPE_TEXTAREA = 'textarea';

	/** @var string the number control type */
	const TYPE_NUMBER = 'number';

	/** @var string the email control type */
	const TYPE_EMAIL = 'email';

	/** @var string the password control type */
	const TYPE_PASSWORD = 'password';

	/** @var string the date control type */
	const TYPE_DATE = 'date';

	/** @var string the checkbox control type */
	const TYPE_CHECKBOX = 'checkbox';

	/** @var string the radio control type */
	const TYPE_RADIO = 'radio';

	/** @var string the select control type */
	const TYPE_SELECT = 'select';

	/** @var string the file control type */
	const TYPE_FILE = 'file';

	/** @var string the color control type */
	const TYPE_COLOR = 'color';

	/** @var string the range control type */
	const TYPE_RANGE = 'range';


}

endif;

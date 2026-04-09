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
 * @copyright Copyright (c) 2013-2026, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\Contracts;

use WP_Error;

/**
 * Contract for adapting an ability's output before returning it as a REST response.
 *
 * Implement this interface to transform or reshape the ability's execute callback
 * result into the format expected by the REST API consumer.
 *
 * @since 6.2.0
 */
interface RestOutputAdapterContract
{
    /**
     * Transforms the ability's execute callback result into REST response data.
     *
     * @since 6.2.0
     *
     * @param mixed $result the raw result from the ability's execute callback
     * @return mixed|WP_Error the adapted output for the REST response; WP_Error on failure
     */
    public function adapt($result);
}

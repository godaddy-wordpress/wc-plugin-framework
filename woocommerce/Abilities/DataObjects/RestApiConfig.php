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

namespace SkyVerge\WooCommerce\PluginFramework\v6_1_0\Abilities\DataObjects;

/**
 * Data object for REST API endpoint configuration.
 *
 * When an ability provides a RestApiConfig, the framework auto-registers
 * a WP_REST_Controller-based endpoint for it.
 *
 * @since 6.1.0
 */
class RestApiConfig
{
	/** @var string REST API namespace (e.g. "wc/v5") */
	public string $namespace;

	/** @var string the API route path */
	public string $apiRoute;

	/** @var string HTTP method */
	public string $method;

	public function __construct(
		string $namespace,
		string $apiRoute,
		string $method
	)
	{
		$this->namespace = $namespace;
		$this->apiRoute = $apiRoute;
		$this->method = $method;
	}
}

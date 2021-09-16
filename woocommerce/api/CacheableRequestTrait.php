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
 * @package   SkyVerge/WooCommerce/API/Response
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2021, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_10\API;

trait CacheableRequestTrait {

	/** @var int the cache lifetime for the request, in seconds, defaults to 0 (unlimited) */
	protected $cache_lifetime = 0;

	/** @var bool whether to force a fresh request regardless if a cached response is available */
	protected $force_refresh = false;


	/**
	 * Sets the cache lifetime for this request.
	 *
	 * @since 5.10.10
	 *
	 * @param int $lifetime cache lifetime, in seconds
	 * @return CacheableRequestTrait $this
	 */
	public function set_cache_lifetime( int $lifetime ) {

		$this->cache_lifetime = $lifetime;

		return $this;
	}


	/**
	 * Gets the cache lifetime for this request.
	 *
	 * @since 5.10.10
	 *
	 * @return int
	 */
	public function get_cache_lifetime() : int {

		return $this->cache_lifetime;
	}


	/**
	 * Sets whether a fresh request should be attempted, regardless if a cached response is available.
	 *
	 * @since 5.10.10
	 *
	 * @param bool $force whether to force a fresh request, or not
	 * @return CacheableRequestTrait $this
	 */
	public function set_force_refresh( bool $force ) {

		$this->force_refresh = $force;

		return $this;
	}


	/**
	 * Determines whether a fresh request should be attempted.
	 *
	 * @since 5.10.10
	 *
	 * @return bool
	 */
	public function should_refresh() : bool {

		return $this->force_refresh;
	}

}

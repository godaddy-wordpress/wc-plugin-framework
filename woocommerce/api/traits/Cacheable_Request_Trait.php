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
 * @copyright Copyright (c) 2013-2022, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_12\API\Traits;

defined( 'ABSPATH' ) or exit;

if ( ! trait_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_12\\API\\Traits\\Cacheable_Request_Trait' ) ) :

/**
 * This trait can be used to add response caching support to API requests.
 *
 * It is intended to be used by a class implementing the SV_WC_API_Request interface. Caching itself is handled
 * by the Abstract_Cacheable_API_Base class, which the API handler should abstract in order to support caching.
 *
 * Adding `use Cacheable_Request_Trait;` to a request class will declare caching support for that request class.
 * It's also possible to customize the cache lifetime by setting it in the request constructor.
 */
trait Cacheable_Request_Trait {


	/** @var int the cache lifetime for the request, in seconds, defaults to 86400 (24 hours) */
	protected $cache_lifetime = 86400;

	/** @var bool whether to force a fresh request regardless if a cached response is available */
	protected $force_refresh = false;

	/** @var bool whether to the current request should be cached or not */
	protected $should_cache = true;


	/**
	 * Sets the cache lifetime for this request.
	 *
	 * @since 5.10.10
	 *
	 * @param int $lifetime cache lifetime, in seconds. Set to 0 for unlimited
	 * @return self
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
	 * @param bool $value whether to force a fresh request, or not
	 * @return self
	 */
	public function set_force_refresh( bool $value ) {

		$this->force_refresh = $value;

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


	/**
	 * Sets whether the request's response should be stored in cache.
	 *
	 * @since 5.10.10
	 *
	 * @param bool $value whether to cache the request, or not
	 * @return self
	 */
	public function set_should_cache( bool $value ) {

		$this->should_cache = $value;

		return $this;
	}


	/**
	 * Determines whether the request's response should be stored in cache.
	 *
	 * @since 5.10.10
	 *
	 * @return bool
	 */
	public function should_cache() : bool {

		return $this->should_cache;
	}


	/**
	 * Bypasses caching for this request completely.
	 *
	 * When called, sets the `force_refresh` flag to true and `should_cache` flag to false
	 *
	 * @since 5.10.10
	 *
	 * @return self
	 */
	public function bypass_cache() {

		$this->set_force_refresh( true );
		$this->set_should_cache( false );

		return $this;
	}

}


endif;

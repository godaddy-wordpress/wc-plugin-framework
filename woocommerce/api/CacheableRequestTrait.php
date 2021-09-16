<?php

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
	 * @return $this
	 */
	public function set_cache_lifetime( int $lifetime ) : self {

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
	 * @return $this
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

<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_10;

use SkyVerge\WooCommerce\PluginFramework\v5_10_10\API\CacheableRequestTrait;

abstract class Abstract_Cacheable_API_Base extends SV_WC_API_Base
{


	/** @var bool whether the response was loaded from cache */
	protected $response_loaded_from_cache = false;


	/**
	 * Simple wrapper for wp_remote_request() so child classes can override this
	 * and provide their own transport mechanism if needed, e.g. a custom
	 * cURL implementation
	 *
	 * @since 2.2.0
	 *
	 * @param string $request_uri
	 * @param string $request_args
	 * @return array|\WP_Error
	 */
	protected function do_remote_request( $request_uri, $request_args ) {

		if ( $this->is_request_cacheable() && ! $this->get_request()->should_refresh() && $response = $this->load_response_from_cache() ) {

			$this->response_loaded_from_cache = true;

			return $response;
		}

		return parent::do_remote_request( $request_uri, $request_args );
	}


	/**
	 * Handle and parse the response
	 *
	 * @since 2.2.0
	 * @param array|\WP_Error $response response data
	 * @throws SV_WC_API_Exception network issues, timeouts, API errors, etc
	 * @return SV_WC_API_Request|object request class instance that implements SV_WC_API_Request
	 */
	protected function handle_response( $response ) {

		parent::handle_response( $response );

		// cache the response
		if ( ! $this->is_response_loaded_from_cache() && $this->is_request_cacheable() ) {

			$this->save_response_to_cache( $response );
		}

		return $this->response; // this param is set by the parent method
	}


	/**
	 * Reset the API response members to their
	 *
	 * @since 1.0.0
	 */
	protected function reset_response() {

		$this->response_loaded_from_cache = false;

		parent::reset_response();
	}


	/**
	 * Gets the request transient key for the current plugin and request data.
	 *
	 * Request transients can be disabled by using the filter below.
	 *
	 * @since 5.10.10
	 *
	 * @return string transient key
	 */
	protected function get_request_transient_key() : string {

		// ex: wc_<plugin_id>_<md5 hash of request uri, request data and cache lifetime>
		return sprintf( 'wc_%s_api_response_%s', $this->get_plugin()->get_id(), md5( implode( '_', [
			$this->get_request_uri(),
			$this->get_request_body(),
			$this->get_request_cache_lifetime(),
		] ) ) );
	}


	/**
	 * Checks whether the current request is cacheable.
	 *
	 * @since 5.10.10
	 *
	 * @return bool
	 */
	protected function is_request_cacheable() : bool {

		if ( ! in_array( CacheableRequestTrait::class, class_uses( $this->get_request() ), true ) ) {
			return false;
		}

		/**
		 * Filters whether the API request is cacheable.
		 *
		 * Allows actors to disable API request caching when a request is normally cacheable. This may be useful
		 * primarily for debugging situations.
		 *
		 * Note: this filter is only applied if the request is originally cacheable, in order to prevent issues when
		 * a non-cacheable request is accidentally flagged as cacheable.
		 *
		 * @since 5.10.10
		 * @param bool $is_cacheable whether the request is cacheable
		 * @param SV_WC_API_Request $request the request instance
		 */
		return (bool) apply_filters( 'wc_plugin_' . $this->get_plugin()->get_id() . '_api_request_is_cacheable', true, $this->get_request() );
	}


	/**
	 * Gets the cache lifetime for the current request.
	 *
	 * @since 5.10.10
	 *
	 * @return int
	 */
	protected function get_request_cache_lifetime() : int {

		/**
		 * Filters API request cache lifetime.
		 *
		 * Allows actors to override cache lifetime for cacheable API requests. This may be useful for debugging
		 * API requests by temporarily setting short cache timeouts.
		 *
		 * @since 5.10.10
		 * @param int $lifetime cache lifetime in seconds, 0 = unlimited
		 * @param SV_WC_API_Request $request the request instance
		 */
		return (int) apply_filters( 'wc_plugin_' . $this->get_plugin()->get_id() . '_api_request_cache_lifetime' , $this->get_request()->get_cache_lifetime(), $this->get_request() );
	}



	/**
	 * Determine whether the response was loaded from cache or not.
	 *
	 * @since 5.10.10
	 * @return bool
	 */
	protected function is_response_loaded_from_cache() : bool {

		return $this->response_loaded_from_cache;
	}


	/**
	 * Loads the response for the current request from the cache, if available.
	 *
	 * @since 5.10.10
	 *
	 * @return array|null
	 */
	protected function load_response_from_cache() {

		return get_transient( $this->get_request_transient_key() );
	}


	/**
	 * Saves the response to cache.
	 *
	 * @since 5.10.10
	 *
	 * @param array $response
	 */
	protected function save_response_to_cache( array $response ) {

		set_transient( $this->get_request_transient_key(), $response, $this->get_request_cache_lifetime());
	}


}

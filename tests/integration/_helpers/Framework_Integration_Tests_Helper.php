<?php

trait Framework_Integration_Tests_Helper {


	/** @var Plugin  */
	private $plugin;


	/**
	 * Gets the singleton instance of the test plugin.
	 *
	 * @return Plugin
	 */
	protected function get_plugin() {

		if ( null === $this->plugin ) {
			$this->plugin = sv_wc_test_plugin();
		}

		return $this->plugin;
	}


}
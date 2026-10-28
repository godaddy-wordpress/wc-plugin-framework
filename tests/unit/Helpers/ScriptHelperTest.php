<?php

namespace SkyVerge\WooCommerce\PluginFramework\v6_2_4\Tests\Unit\Helpers;

use SkyVerge\WooCommerce\PluginFramework\v6_2_4\Helpers\ScriptHelper;
use SkyVerge\WooCommerce\PluginFramework\v6_2_4\Tests\TestCase;
use WP_Mock;

/**
 * @coversDefaultClass \SkyVerge\WooCommerce\PluginFramework\v6_2_4\Helpers\ScriptHelper
 */
final class ScriptHelperTest extends TestCase
{
	/**
	 * Mocks the WP functions called by addInlineScript after the guard (script already
	 * registered and enqueued, so register/enqueue branches are skipped).
	 */
	private function mockScriptFunctions() : void
	{
		WP_Mock::userFunction('wp_script_is', ['return' => true]);
		WP_Mock::userFunction('wp_add_inline_script', ['return' => true]);
	}

	/**
	 * @covers ::addInlineScript
	 */
	public function testNoWarningOnFrontendBeforeFooterScriptsFire() : void
	{
		WP_Mock::userFunction('is_admin', ['return' => false]);
		WP_Mock::userFunction('did_action', [
			'return' => static function (string $hook) : int {
				return 0; // neither hook has fired
			},
		]);
		$this->mockScriptFunctions();

		ScriptHelper::addInlineScript('test-handle', 'console.log(1);');
		$this->addToAssertionCount(1);
	}

	/**
	 * @covers ::addInlineScript
	 */
	public function testWarningOnFrontendAfterFooterScriptsFire() : void
	{
		WP_Mock::userFunction('is_admin', ['return' => false]);
		WP_Mock::userFunction('did_action', [
			'return' => static function (string $hook) : int {
				return $hook === 'wp_print_footer_scripts' ? 1 : 0;
			},
		]);
		WP_Mock::userFunction('_doing_it_wrong', ['times' => 1]);
		$this->mockScriptFunctions();

		ScriptHelper::addInlineScript('test-handle', 'console.log(1);');
		$this->addToAssertionCount(1);
	}

	/**
	 * In admin, WP 6.9+'s _wp_get_iframed_editor_assets() calls wp_print_footer_scripts()
	 * on throwaway WP_Scripts instances, bumping its counter before admin_footer fires.
	 * addInlineScript must not warn when wp_print_footer_scripts has fired but
	 * admin_print_footer_scripts has not — this is the key regression the fix addresses.
	 *
	 * @covers ::addInlineScript
	 */
	public function testNoWarningInAdminWhenOnlyWpPrintFooterScriptsFired() : void
	{
		WP_Mock::userFunction('is_admin', ['return' => true]);
		WP_Mock::userFunction('did_action', [
			'return' => static function (string $hook) : int {
				// Simulates the WP 6.9+ iframed-assets scenario: wp_print_footer_scripts
				// counter is elevated but admin_print_footer_scripts has not fired yet.
				return $hook === 'wp_print_footer_scripts' ? 1 : 0;
			},
		]);
		$this->mockScriptFunctions();

		// _doing_it_wrong is intentionally not mocked; any call would fail the test.
		ScriptHelper::addInlineScript('test-handle', 'console.log(1);');
		$this->addToAssertionCount(1);
	}

	/**
	 * In admin, the warning fires only after admin_print_footer_scripts has run,
	 * not merely because wp_print_footer_scripts fired (which the iframed-assets
	 * collector triggers early).
	 *
	 * @covers ::addInlineScript
	 */
	public function testWarningInAdminAfterAdminFooterScriptsFire() : void
	{
		WP_Mock::userFunction('is_admin', ['return' => true]);
		WP_Mock::userFunction('did_action', [
			'return' => static function (string $hook) : int {
				return $hook === 'admin_print_footer_scripts' ? 1 : 0;
			},
		]);
		WP_Mock::userFunction('_doing_it_wrong', ['times' => 1]);
		$this->mockScriptFunctions();

		ScriptHelper::addInlineScript('test-handle', 'console.log(1);');
		$this->addToAssertionCount(1);
	}
}

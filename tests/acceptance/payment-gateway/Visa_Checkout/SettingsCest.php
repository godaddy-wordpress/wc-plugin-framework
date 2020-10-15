<?php

namespace SkyVerge\WooCommerce\PluginFramework\Tests\Payment_Gateway\Visa_Checkout;

use AcceptanceTester;

class SettingsCest {

    public function _before( AcceptanceTester $I ) {

    	$I->loginAsAdmin();
    }

	public function try_save_settings( AcceptanceTester $I ) {

		$I->haveVisaCheckoutActivated( 'gateway_test_plugin' );

		$I->amOnVisaCheckoutSettingsPage();

		$I->wantTo( 'See the Visa Checkout settings' );
		$I->see( 'Accept Visa Checkout' );

		$I->checkVisaCheckoutEnableSetting();
		$I->fillVisaCheckoutApiKeyField( '123456' );

		$I->saveVisaCheckoutSettings();

		$I->see( 'Your settings have been saved.' );

		$I->seeVisaCheckoutEnableSettingIsChecked();
		$I->seeVisaCheckoutApiKeyField( '123456' );
    }


}

<?php


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends \Codeception\Actor {

    use _generated\AcceptanceTesterActions;


	/** Visa Checkout settings screen *****************************************/


	/**
	 * @since 5.10.0-dev.1
	 */
	public function haveVisaCheckoutActivated( $plugin_id ) {

		$this->haveMuPlugin(
			'activate-visa-checkout.php',
			"add_filter( 'wc_payment_gateway_{$plugin_id}_activate_visa_checkout', '__return_true' );"
		);
	}


	/**
	 * @since 5.10.0-dev.1
	 */
	public function amOnVisaCheckoutSettingsPage() {

		$this->amOnAdminPage( 'admin.php?page=wc-settings&tab=checkout&section=visa-checkout' );
	}


	/**
	 * @since 5.10.0-dev.1
	 */
	public function checkVisaCheckoutEnableSetting() {

		$this->checkOption( '[name="sv_wc_visa_checkout_enabled"]' );
	}


	/**
	 * @since 5.10.0-dev.1
	 *
	 * @param string $api_key the desired value for the API Key field
	 */
	public function fillVisaCheckoutApiKeyField( string $api_key ) {

		$this->fillField( '[name="sv_wc_visa_checkout_api_key"]', $api_key );
	}


	/**
	 * @since 5.10.0-dev.1
	 */
	public function saveVisaCheckoutSettings() {

		$this->click( '[name="save"][type="submit"]' );
	}


	/**
	 * @since 5.10.0-dev.1
	 */
	public function seeVisaCheckoutEnableSettingIsChecked() {

		$this->seeCheckboxIsChecked( '[name="sv_wc_visa_checkout_enabled"]' );
	}


	/**
	 * @since 5.10.0-dev.1
	 *
	 * @param string $api_key the expected value for the API Key field
	 */
	public function seeVisaCheckoutApiKeyField( string $api_key ) {

		$this->seeInField( '[name="sv_wc_visa_checkout_api_key"]', $api_key );
	}


}

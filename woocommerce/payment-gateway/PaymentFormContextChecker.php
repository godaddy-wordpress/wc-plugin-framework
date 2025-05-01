<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_9\Payment_Gateway;

use SkyVerge\WooCommerce\PluginFramework\v5_15_9\Enums\PaymentFormContext;
use SkyVerge\WooCommerce\PluginFramework\v5_15_9\SV_WC_Helper;

/**
 * Helper class for setting and checking the page context that a payment form for a given gateway is rendered on.
 *
 * @since 5.13.0
 */
class PaymentFormContextChecker
{
	/** @var string ID of the gateway in use */
	protected string $gatewayId;

	public function __construct(string $gatewayId)
	{
		$this->gatewayId = $gatewayId;
	}

	/**
	 * Gets the name of the session key where we store context data.
	 *
	 * @since 5.13.0
	 * @return string
	 */
	protected function getContextSessionKeyName() : string
	{
		return "wc_{$this->gatewayId}_payment_form_context";
	}

	/**
	 * Sets the context of the current page, if one is available.
	 *
	 * @since 5.13.0
	 * @return void
	 */
	public function maybeSetContext() : void
	{
		if ($context = $this->getCurrentPaymentFormContext()) {
			WC()->session->set(
				$this->getContextSessionKeyName(),
				$context
			);
		}
	}

	/**
	 * Gets the context of the current payment form page.
	 *
	 * @since 5.13.0
	 * @return string|null
	 */
	protected function getCurrentPaymentFormContext() : ?string
	{
		if (SV_WC_Helper::isCheckoutPayPage()) {
			return isset($_GET['pay_for_order']) ? PaymentFormContext::CustomerPayPage : PaymentFormContext::CheckoutPayPage;
		}

		if (is_checkout()) {
			return PaymentFormContext::Checkout;
		}

		return null;
	}

	/**
	 * Gets the context stored in the session data.
	 *
	 * @since 5.13.0
	 * @return string|null
	 */
	protected function getStoredPaymentFormContext() : ?string
	{
		$storedContext = WC()->session->get($this->getContextSessionKeyName());

		return PaymentFormContext::tryFrom($storedContext);
	}

	/**
	 * @since 5.13.0
	 */
	public function currentContextRequiresTermsAndConditionsAcceptance() : bool
	{
		return PaymentFormContext::CustomerPayPage === $this->getStoredPaymentFormContext() && wc_terms_and_conditions_checkbox_enabled();
	}
}

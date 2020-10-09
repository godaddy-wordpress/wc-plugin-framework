jQuery( document ).ready( ( $ ) => {

	"use strict"

	/**
	 * Google Pay handler.
	 *
	 * @since 5.9.0-dev.1
	 *
	 * @type {SV_WC_Google_Pay_Handler_v5_8_1} object
	 */
	window.SV_WC_Google_Pay_Handler_v5_8_1 = class SV_WC_Google_Pay_Handler_v5_8_1 {

		/**
		 * Handler constructor.
		 *
		 * @since 5.9.0-dev.1
		 *
		 * @param {Object} params The plugin ID
		 * @param {string} params.plugin_id The plugin ID
		 * @param {string} params.merchant_id The merchant ID
		 * @param {string} params.gateway_id The gateway ID
		 * @param {string} params.gateway_id_dasherized The gateway ID dasherized
		 * @param {string} params.ajax_url The AJAX URL
		 * @param {string} params.process_nonce Nonce for the process AJAX action
		 * @param {string} params.button_style The button style
		 * @param {string[]} params.card_types The supported card types
		 * @param {string} params.generic_error The generic error message
		 */
		constructor( params ) {

			let {
				plugin_id,
				merchant_id,
				gateway_id,
				gateway_id_dasherized,
				ajax_url,
				process_nonce,
				button_style,
				card_types,
				generic_error
			} = params;

			this.gatewayID = gateway_id;
			this.ajaxURL = ajax_url;
			this.processNonce = process_nonce;

			/**
			 * Card networks supported by your site and your gateway
			 *
			 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#CardParameters|CardParameters}
			 */
			const allowedCardNetworks = card_types;

			/**
			 * Define the version of the Google Pay API referenced when creating your configuration
			 *
			 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#PaymentDataRequest|apiVersion in PaymentDataRequest}
			 */
			this.baseRequest = {
				apiVersion: 2,
				apiVersionMinor: 0
			};

			/**
			 * Card authentication methods supported by your site and your gateway
			 *
			 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#CardParameters|CardParameters}
			 *
			 * @todo confirm your processor supports Android device tokens for your supported card networks
			 */
			const allowedCardAuthMethods = ["PAN_ONLY", "CRYPTOGRAM_3DS"];

			/**
			 * Identify your gateway and your site's gateway merchant identifier
			 *
			 * The Google Pay API response will return an encrypted payment method capable
			 * of being charged by a supported gateway after payer authorization
			 *
			 * @todo check with your gateway on the parameters to pass
			 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#gateway|PaymentMethodTokenizationSpecification}
			 */
			const tokenizationSpecification = {
				type: 'PAYMENT_GATEWAY',
				parameters: {
					'gateway': plugin_id,
					'gatewayMerchantId': merchant_id
				}
			};

			/**
			 * Describe your site's support for the CARD payment method and its required fields
			 *
			 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#CardParameters|CardParameters}
			 */
			this.baseCardPaymentMethod = {
				type: 'CARD',
				parameters: {
					allowedAuthMethods: allowedCardAuthMethods,
					allowedCardNetworks: allowedCardNetworks
				}
			};

			/**
			 * Describe your site's support for the CARD payment method including optional fields
			 *
			 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#CardParameters|CardParameters}
			 */
			this.cardPaymentMethod = Object.assign(
				{},
				this.baseCardPaymentMethod,
				{
					tokenizationSpecification: tokenizationSpecification
				}
			);

			/**
			 * An initialized google.payments.api.PaymentsClient object or null if not yet set
			 *
			 * @see {@link getGooglePaymentsClient}
			 */
			this.paymentsClient = null;
		}

		/**
		 * Configure your site's support for payment methods supported by the Google Pay
		 * API.
		 *
		 * Each member of allowedPaymentMethods should contain only the required fields,
		 * allowing reuse of this base request when determining a viewer's ability
		 * to pay and later requesting a supported payment method
		 *
		 * @returns {object} Google Pay API version, payment methods supported by the site
		 */
		getGoogleIsReadyToPayRequest() {

			return Object.assign(
				{},
				this.baseRequest,
				{
					allowedPaymentMethods: [this.baseCardPaymentMethod]
				}
			);
		}

		/**
		 * Provide Google Pay API with a payment amount, currency, and amount status
		 *
		 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#TransactionInfo|TransactionInfo}
		 * @returns {object} transaction info, suitable for use as transactionInfo property of PaymentDataRequest
		 */
		getGoogleTransactionInfo() {

			return {
				countryCode: 'US',
				currencyCode: 'USD',
				totalPriceStatus: 'FINAL',
				// set to cart total
				totalPrice: '1.00'
			};
		}

		/**
		 * Configure support for the Google Pay API
		 *
		 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#PaymentDataRequest|PaymentDataRequest}
		 * @returns {object} PaymentDataRequest fields
		 */
		getGooglePaymentDataRequest() {

			const paymentDataRequest = Object.assign({}, this.baseRequest);
			paymentDataRequest.allowedPaymentMethods = [this.cardPaymentMethod];
			paymentDataRequest.transactionInfo = this.getGoogleTransactionInfo();
			paymentDataRequest.merchantInfo = {
				// @todo a merchant ID is available for a production environment after approval by Google
				// See {@link https://developers.google.com/pay/api/web/guides/test-and-deploy/integration-checklist|Integration checklist}
				// merchantId: '12345678901234567890',
				merchantName: 'Example Merchant'
			};
			return paymentDataRequest;
		}

		/**
		 * Return an active PaymentsClient or initialize
		 *
		 * @see {@link https://developers.google.com/pay/api/web/reference/client#PaymentsClient|PaymentsClient constructor}
		 * @returns {google.payments.api.PaymentsClient} Google Pay API client
		 */
		getGooglePaymentsClient() {

			if (this.paymentsClient === null) {
				this.paymentsClient = new google.payments.api.PaymentsClient({environment: 'TEST'});
			}

			return this.paymentsClient;
		}

		/**
		 * Add a Google Pay purchase button alongside an existing checkout button
		 *
		 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#ButtonOptions|Button options}
		 * @see {@link https://developers.google.com/pay/api/web/guides/brand-guidelines|Google Pay brand guidelines}
		 */
		addGooglePayButton() {

			const paymentsClient = this.getGooglePaymentsClient();
			const button = paymentsClient.createButton({
				onClick: () => this.onGooglePaymentButtonClicked()
			});
			document.getElementById('sv-wc-google-pay-button-container').appendChild(button);
		}

		/**
		 * Prefetch payment data to improve performance
		 *
		 * @see {@link https://developers.google.com/pay/api/web/reference/client#prefetchPaymentData|prefetchPaymentData()}
		 */
		prefetchGooglePaymentData() {

			const paymentDataRequest = this.getGooglePaymentDataRequest();
			// transactionInfo must be set but does not affect cache
			paymentDataRequest.transactionInfo = {
				totalPriceStatus: 'NOT_CURRENTLY_KNOWN',
				currencyCode: 'USD'
			};
			const paymentsClient = this.getGooglePaymentsClient();
			paymentsClient.prefetchPaymentData(paymentDataRequest);
		}

		/**
		 * Process payment data returned by the Google Pay API
		 *
		 * @param {object} paymentData response from Google Pay API after user approves payment
		 * @see {@link https://developers.google.com/pay/api/web/reference/response-objects#PaymentData|PaymentData object reference}
		 */
		processPayment(paymentData) {

			// show returned data in developer console for debugging
			console.log(paymentData);

			// pass payment token to your gateway to process payment
			const data = {
				action: `wc_${this.gatewayID}_google_pay_process_payment`,
				nonce: this.processNonce,
				paymentMethod: JSON.stringify( paymentData.paymentMethodData )
			}

			$.post(this.ajaxURL, data, (response) => {

				console.log(response);

				if (response.success) {
					// @todo: handle success
				} else {
					// @todo: handle error
				}
			});
		}

		/**
		 * Show Google Pay payment sheet when Google Pay payment button is clicked
		 */
		onGooglePaymentButtonClicked() {

			const paymentDataRequest = this.getGooglePaymentDataRequest();
			paymentDataRequest.transactionInfo = this.getGoogleTransactionInfo();

			const paymentsClient = this.getGooglePaymentsClient();
			paymentsClient.loadPaymentData(paymentDataRequest)
				.then( (paymentData) => {
					// handle the response
					this.processPayment(paymentData);
				})
				.catch( (err) => {
					// show error in developer console for debugging
					console.error(err);
				});
		}

		/**
		 * Initialize Google PaymentsClient after Google-hosted JavaScript has loaded
		 *
		 * Display a Google Pay payment button after confirmation of the viewer's
		 * ability to pay.
		 */
		init() {

			const paymentsClient = this.getGooglePaymentsClient();
			paymentsClient.isReadyToPay( this.getGoogleIsReadyToPayRequest() )
				.then( ( response ) => {
					if (response.result) {
						this.addGooglePayButton();
						// @todo prefetch payment data to improve performance after confirming site functionality
						// this.prefetchGooglePaymentData();
					}
				})
				.catch( (err) => {
					// show error in developer console for debugging
					console.error(err);
				});
		}
	}

	$( document.body ).trigger( 'sv_wc_google_pay_handler_v5_8_1_loaded' );

} );

function e(e,t,n,a){Object.defineProperty(e,t,{get:n,set:a,enumerable:!0,configurable:!0})}var t="undefined"!=typeof globalThis?globalThis:"undefined"!=typeof self?self:"undefined"!=typeof window?window:"undefined"!=typeof global?global:{},n={},a={},o=t.parcelRequireb301;null==o&&((o=function(e){if(e in n)return n[e].exports;if(e in a){var t=a[e];delete a[e];var o={id:e,exports:{}};return n[e]=o,t.call(o.exports,o,o.exports),o.exports}var i=Error("Cannot find module '"+e+"'");throw i.code="MODULE_NOT_FOUND",i}).register=function(e,t){a[e]=t},t.parcelRequireb301=o);var i=o.register;i("guLUH",function(t,n){e(t.exports,"_",function(){return a});function a(e,t){if(!(e instanceof t))throw TypeError("Cannot call a class as a function")}}),i("90XvN",function(t,n){function a(e,t){for(var n=0;n<t.length;n++){var a=t[n];a.enumerable=a.enumerable||!1,a.configurable=!0,"value"in a&&(a.writable=!0),Object.defineProperty(e,a.key,a)}}function o(e,t,n){return t&&a(e.prototype,t),n&&a(e,n),e}e(t.exports,"_",function(){return o})});var r=o("guLUH"),s=o("90XvN");jQuery(function(e){window.SV_WC_Google_Pay_Handler_v5_12_3=function(){function t(e){(0,r._)(this,t);var n=e.plugin_id,a=e.merchant_id,o=e.merchant_name,i=e.gateway_id,s=(e.gateway_id_dasherized,e.environment),c=e.ajax_url,l=e.recalculate_totals_nonce,u=e.process_nonce,d=e.button_style,h=e.card_types,m=e.available_countries,g=e.currency_code,y=e.needs_shipping,p=e.generic_error;this.gatewayID=i,this.merchantID=a,this.merchantName=o,this.environment=s,this.ajaxURL=c,this.recalculateTotalsNonce=l,this.processNonce=u,this.buttonStyle=d,this.availableCountries=m,this.currencyCode=g,this.needsShipping=y,this.genericError=p,e.product_id&&(this.productID=e.product_id),this.baseRequest={apiVersion:2,apiVersionMinor:0};var f={type:"PAYMENT_GATEWAY",parameters:{gateway:n,gatewayMerchantId:this.merchantID}};this.baseCardPaymentMethod={type:"CARD",parameters:{allowedAuthMethods:["PAN_ONLY","CRYPTOGRAM_3DS"],allowedCardNetworks:h,billingAddressRequired:!0,billingAddressParameters:{format:"FULL",phoneNumberRequired:!0}}},this.cardPaymentMethod=Object.assign({},this.baseCardPaymentMethod,{tokenizationSpecification:f}),this.paymentsClient=null}return(0,s._)(t,[{key:"getGoogleIsReadyToPayRequest",value:function(){return Object.assign({},this.baseRequest,{allowedPaymentMethods:[this.baseCardPaymentMethod]})}},{key:"getGooglePaymentDataRequest",value:function(e){var t=this;return this.getGoogleTransactionInfo(function(n){var a=Object.assign({},t.baseRequest);a.allowedPaymentMethods=[t.cardPaymentMethod],a.transactionInfo=n,a.merchantInfo={merchantId:t.merchantID,merchantName:t.merchantName},a.emailRequired=!0,a.callbackIntents=["PAYMENT_AUTHORIZATION"],t.needsShipping&&(a.callbackIntents=["SHIPPING_ADDRESS","SHIPPING_OPTION","PAYMENT_AUTHORIZATION"],a.shippingAddressRequired=!0,a.shippingAddressParameters=t.getGoogleShippingAddressParameters(),a.shippingOptionRequired=!0),e(a)})}},{key:"getGooglePaymentsClient",value:function(){var e=this;if(null===this.paymentsClient){var t={environment:this.environment,merchantInfo:{merchantName:this.merchantName,merchantId:this.merchantID},paymentDataCallbacks:{onPaymentAuthorized:function(t){return e.onPaymentAuthorized(t)}}};this.needsShipping&&(t.paymentDataCallbacks.onPaymentDataChanged=function(t){return e.onPaymentDataChanged(t)}),this.paymentsClient=new google.payments.api.PaymentsClient(t)}return this.paymentsClient}},{key:"onPaymentAuthorized",value:function(e){var t=this;return this.blockUI(),new Promise(function(n,a){try{t.processPayment(e,n)}catch(e){a({transactionState:"ERROR",error:{intent:"PAYMENT_AUTHORIZATION",message:"Payment could not be processed",reason:"PAYMENT_DATA_INVALID"}})}t.unblockUI()})}},{key:"onPaymentDataChanged",value:function(e){var t=this;return this.blockUI(),new Promise(function(n,a){try{var o=e.shippingAddress,i=e.shippingOptionData,r="";"SHIPPING_OPTION"==e.callbackTrigger&&(r=i.id),t.getUpdatedTotals(o,r,function(e){0==e.newShippingOptionParameters.shippingOptions.length&&(e={error:t.getGoogleUnserviceableAddressError()}),n(e)})}catch(e){t.failPayment("Could not load updated totals or process payment data request update. "+e)}t.unblockUI()})}},{key:"getGoogleTransactionInfo",value:function(t){var n=this,a={action:"wc_".concat(this.gatewayID,"_google_pay_get_transaction_info")};this.productID&&(a.productID=this.productID),e.post(this.ajaxURL,a,function(e){e.success?t(JSON.parse(e.data)):n.failPayment("Could not build transaction info. "+e.data.message)})}},{key:"getUpdatedTotals",value:function(t,n,a){var o=this,i={action:"wc_".concat(this.gatewayID,"_google_pay_recalculate_totals"),nonce:this.recalculateTotalsNonce,shippingAddress:t,shippingMethod:n};this.productID&&(i.productID=this.productID),e.post(this.ajaxURL,i,function(e){e.success?a(JSON.parse(e.data)):o.failPayment("Could not recalculate totals. "+e.data.message)})}},{key:"getGoogleShippingAddressParameters",value:function(){return{allowedCountryCodes:this.availableCountries}}},{key:"getGoogleUnserviceableAddressError",value:function(){return{reason:"SHIPPING_ADDRESS_UNSERVICEABLE",message:"Cannot ship to the selected address",intent:"SHIPPING_ADDRESS"}}},{key:"addGooglePayButton",value:function(){var e=this,t=this.getGooglePaymentsClient().createButton({onClick:function(t){return e.onGooglePaymentButtonClicked(t)},buttonColor:this.buttonStyle,buttonSizeMode:"fill"});document.getElementById("sv-wc-google-pay-button-container").appendChild(t)}},{key:"prefetchGooglePaymentData",value:function(){var e=this;this.getGooglePaymentDataRequest(function(t){t.transactionInfo={totalPriceStatus:"NOT_CURRENTLY_KNOWN",currencyCode:e.currencyCode},e.getGooglePaymentsClient().prefetchPaymentData(t)})}},{key:"processPayment",value:function(t,n){var a=this,o={action:"wc_".concat(this.gatewayID,"_google_pay_process_payment"),nonce:this.processNonce,paymentData:JSON.stringify(t)};return this.productID&&!this.needsShipping&&(o.productID=this.productID),e.post(this.ajaxURL,o,function(e){e.success?(n({transactionState:"SUCCESS"}),window.location=e.data.redirect):(n({transactionState:"ERROR",error:{intent:"SHIPPING_ADDRESS",message:"Invalid data",reason:"PAYMENT_DATA_INVALID"}}),a.failPayment("Payment could not be processed. "+e.data.message))})}},{key:"onGooglePaymentButtonClicked",value:function(e){var t=this;e.preventDefault(),this.blockUI(),this.getGooglePaymentDataRequest(function(e){var n=t.getGooglePaymentsClient();try{n.loadPaymentData(e)}catch(e){t.failPayment("Could not load payment data. "+e)}t.unblockUI()})}},{key:"init",value:function(){if(e("form.cart").length)this.initProductPage();else if(e("form.woocommerce-cart-form").length)this.initCartPage();else{if(!e("form.woocommerce-checkout").length)return;this.initCheckoutPage()}this.initGooglePay()}},{key:"initGooglePay",value:function(){var e=this;this.getGooglePaymentsClient().isReadyToPay(this.getGoogleIsReadyToPayRequest()).then(function(t){t.result&&(e.addGooglePayButton(),e.prefetchGooglePaymentData())}).catch(function(t){e.failPayment("Google Pay is not ready. "+t)})}},{key:"initProductPage",value:function(){this.uiElement=e("form.cart")}},{key:"initCartPage",value:function(){var t=this;this.uiElement=e("form.woocommerce-cart-form").parents("div.woocommerce"),e(document.body).on("updated_cart_totals",function(){t.initGooglePay()})}},{key:"initCheckoutPage",value:function(){this.uiElement=e("form.woocommerce-checkout")}},{key:"failPayment",value:function(e){console.error("[Google Pay] "+e),this.unblockUI(),this.renderErrors([this.genericError])}},{key:"renderErrors",value:function(t){e(".woocommerce-error, .woocommerce-message").remove(),this.uiElement.prepend('<ul class="woocommerce-error"><li>'+t.join("</li><li>")+"</li></ul>"),this.uiElement.removeClass("processing").unblock(),e("html, body").animate({scrollTop:this.uiElement.offset().top-100},1e3)}},{key:"blockUI",value:function(){this.uiElement.block({message:null,overlayCSS:{background:"#fff",opacity:.6}})}},{key:"unblockUI",value:function(){this.uiElement.unblock()}}]),t}(),e(document.body).trigger("sv_wc_google_pay_handler_v5_12_3_loaded")});
//# sourceMappingURL=sv-wc-payment-gateway-google-pay.js.map

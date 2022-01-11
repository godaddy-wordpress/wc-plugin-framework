parcelRequire=function(e,r,t,n){var i,o="function"==typeof parcelRequire&&parcelRequire,u="function"==typeof require&&require;function f(t,n){if(!r[t]){if(!e[t]){var i="function"==typeof parcelRequire&&parcelRequire;if(!n&&i)return i(t,!0);if(o)return o(t,!0);if(u&&"string"==typeof t)return u(t);var c=new Error("Cannot find module '"+t+"'");throw c.code="MODULE_NOT_FOUND",c}p.resolve=function(r){return e[t][1][r]||r},p.cache={};var l=r[t]=new f.Module(t);e[t][0].call(l.exports,p,l,l.exports,this)}return r[t].exports;function p(e){return f(p.resolve(e))}}f.isParcelRequire=!0,f.Module=function(e){this.id=e,this.bundle=f,this.exports={}},f.modules=e,f.cache=r,f.parent=o,f.register=function(r,t){e[r]=[function(e,r){r.exports=t},{}]};for(var c=0;c<t.length;c++)try{f(t[c])}catch(e){i||(i=e)}if(t.length){var l=f(t[t.length-1]);"object"==typeof exports&&"undefined"!=typeof module?module.exports=l:"function"==typeof define&&define.amd?define(function(){return l}):n&&(this[n]=l)}if(parcelRequire=f,i)throw i;return f}({"O8A1":[function(require,module,exports) {
function e(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function t(e,t){for(var a=0;a<t.length;a++){var n=t[a];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(e,n.key,n)}}function a(e,a,n){return a&&t(e.prototype,a),n&&t(e,n),e}jQuery(function(t){"use strict";window.SV_WC_Google_Pay_Handler_v5_10_11=function(){function n(t){e(this,n);var a=t.plugin_id,o=t.merchant_id,i=t.merchant_name,r=t.gateway_id,s=(t.gateway_id_dasherized,t.ajax_url),c=t.recalculate_totals_nonce,l=t.process_nonce,u=t.button_style,d=t.card_types,h=t.available_countries,m=t.currency_code,y=t.needs_shipping,g=t.generic_error;this.gatewayID=r,this.merchantID=o,this.merchantName=i,this.ajaxURL=s,this.recalculateTotalsNonce=c,this.processNonce=l,this.buttonStyle=u,this.availableCountries=h,this.currencyCode=m,this.needsShipping=y,this.genericError=g,t.product_id&&(this.productID=t.product_id);var p=d;this.baseRequest={apiVersion:2,apiVersionMinor:0};var f={type:"PAYMENT_GATEWAY",parameters:{gateway:a,gatewayMerchantId:this.merchantID}};this.baseCardPaymentMethod={type:"CARD",parameters:{allowedAuthMethods:["PAN_ONLY","CRYPTOGRAM_3DS"],allowedCardNetworks:p,billingAddressRequired:!0,billingAddressParameters:{format:"FULL",phoneNumberRequired:!0}}},this.cardPaymentMethod=Object.assign({},this.baseCardPaymentMethod,{tokenizationSpecification:f}),this.paymentsClient=null}return a(n,[{key:"getGoogleIsReadyToPayRequest",value:function(){return Object.assign({},this.baseRequest,{allowedPaymentMethods:[this.baseCardPaymentMethod]})}},{key:"getGooglePaymentDataRequest",value:function(e){var t=this;return this.getGoogleTransactionInfo(function(a){var n=Object.assign({},t.baseRequest);n.allowedPaymentMethods=[t.cardPaymentMethod],n.transactionInfo=a,n.merchantInfo={merchantId:t.merchantID,merchantName:t.merchantName},n.emailRequired=!0,n.callbackIntents=["PAYMENT_AUTHORIZATION"],t.needsShipping&&(n.callbackIntents=["SHIPPING_ADDRESS","SHIPPING_OPTION","PAYMENT_AUTHORIZATION"],n.shippingAddressRequired=!0,n.shippingAddressParameters=t.getGoogleShippingAddressParameters(),n.shippingOptionRequired=!0),e(n)})}},{key:"getGooglePaymentsClient",value:function(){var e=this;if(null===this.paymentsClient){var t={merchantInfo:{merchantName:this.merchantName,merchantId:this.merchantID},paymentDataCallbacks:{onPaymentAuthorized:function(t){return e.onPaymentAuthorized(t)}}};this.needsShipping&&(t.paymentDataCallbacks.onPaymentDataChanged=function(t){return e.onPaymentDataChanged(t)}),this.paymentsClient=new google.payments.api.PaymentsClient(t)}return this.paymentsClient}},{key:"onPaymentAuthorized",value:function(e){var t=this;return this.blockUI(),new Promise(function(a,n){try{t.processPayment(e,a)}catch(o){n({transactionState:"ERROR",error:{intent:"PAYMENT_AUTHORIZATION",message:"Payment could not be processed",reason:"PAYMENT_DATA_INVALID"}})}t.unblockUI()})}},{key:"onPaymentDataChanged",value:function(e){var t=this;return this.blockUI(),new Promise(function(a,n){try{var o=e.shippingAddress,i=e.shippingOptionData,r="";"SHIPPING_OPTION"==e.callbackTrigger&&(r=i.id),t.getUpdatedTotals(o,r,function(e){0==e.newShippingOptionParameters.shippingOptions.length&&(e={error:t.getGoogleUnserviceableAddressError()}),a(e)})}catch(s){t.failPayment("Could not load updated totals or process payment data request update. "+s)}t.unblockUI()})}},{key:"getGoogleTransactionInfo",value:function(e){var a=this,n={action:"wc_".concat(this.gatewayID,"_google_pay_get_transaction_info")};this.productID&&(n.productID=this.productID),t.post(this.ajaxURL,n,function(t){t.success?e(JSON.parse(t.data)):a.failPayment("Could not build transaction info. "+t.data.message)})}},{key:"getUpdatedTotals",value:function(e,a,n){var o=this,i={action:"wc_".concat(this.gatewayID,"_google_pay_recalculate_totals"),nonce:this.recalculateTotalsNonce,shippingAddress:e,shippingMethod:a};this.productID&&(i.productID=this.productID),t.post(this.ajaxURL,i,function(e){e.success?n(JSON.parse(e.data)):o.failPayment("Could not recalculate totals. "+e.data.message)})}},{key:"getGoogleShippingAddressParameters",value:function(){return{allowedCountryCodes:this.availableCountries}}},{key:"getGoogleUnserviceableAddressError",value:function(){return{reason:"SHIPPING_ADDRESS_UNSERVICEABLE",message:"Cannot ship to the selected address",intent:"SHIPPING_ADDRESS"}}},{key:"addGooglePayButton",value:function(){var e=this,t=this.getGooglePaymentsClient().createButton({onClick:function(t){return e.onGooglePaymentButtonClicked(t)},buttonColor:this.buttonStyle,buttonSizeMode:"fill"});document.getElementById("sv-wc-google-pay-button-container").appendChild(t)}},{key:"prefetchGooglePaymentData",value:function(){var e=this;this.getGooglePaymentDataRequest(function(t){t.transactionInfo={totalPriceStatus:"NOT_CURRENTLY_KNOWN",currencyCode:e.currencyCode},e.getGooglePaymentsClient().prefetchPaymentData(t)})}},{key:"processPayment",value:function(e,a){var n=this,o={action:"wc_".concat(this.gatewayID,"_google_pay_process_payment"),nonce:this.processNonce,paymentData:JSON.stringify(e)};return this.productID&&!this.needsShipping&&(o.productID=this.productID),t.post(this.ajaxURL,o,function(e){e.success?(a({transactionState:"SUCCESS"}),window.location=e.data.redirect):(a({transactionState:"ERROR",error:{intent:"SHIPPING_ADDRESS",message:"Invalid data",reason:"PAYMENT_DATA_INVALID"}}),n.failPayment("Payment could not be processed. "+e.data.message))})}},{key:"onGooglePaymentButtonClicked",value:function(e){var t=this;e.preventDefault(),this.blockUI(),this.getGooglePaymentDataRequest(function(e){var a=t.getGooglePaymentsClient();try{a.loadPaymentData(e)}catch(n){t.failPayment("Could not load payment data. "+n)}t.unblockUI()})}},{key:"init",value:function(){if(t("form.cart").length)this.initProductPage();else if(t("form.woocommerce-cart-form").length)this.initCartPage();else{if(!t("form.woocommerce-checkout").length)return;this.initCheckoutPage()}this.initGooglePay()}},{key:"initGooglePay",value:function(){var e=this;this.getGooglePaymentsClient().isReadyToPay(this.getGoogleIsReadyToPayRequest()).then(function(t){t.result&&(e.addGooglePayButton(),e.prefetchGooglePaymentData())}).catch(function(t){e.failPayment("Google Pay is not ready. "+t)})}},{key:"initProductPage",value:function(){this.uiElement=t("form.cart")}},{key:"initCartPage",value:function(){var e=this;this.uiElement=t("form.woocommerce-cart-form").parents("div.woocommerce"),t(document.body).on("updated_cart_totals",function(){e.initGooglePay()})}},{key:"initCheckoutPage",value:function(){this.uiElement=t("form.woocommerce-checkout")}},{key:"failPayment",value:function(e){console.error("[Google Pay] "+e),this.unblockUI(),this.renderErrors([this.genericError])}},{key:"renderErrors",value:function(e){t(".woocommerce-error, .woocommerce-message").remove(),this.uiElement.prepend('<ul class="woocommerce-error"><li>'+e.join("</li><li>")+"</li></ul>"),this.uiElement.removeClass("processing").unblock(),t("html, body").animate({scrollTop:this.uiElement.offset().top-100},1e3)}},{key:"blockUI",value:function(){this.uiElement.block({message:null,overlayCSS:{background:"#fff",opacity:.6}})}},{key:"unblockUI",value:function(){this.uiElement.unblock()}}]),n}(),t(document.body).trigger("sv_wc_google_pay_handler_v5_10_11_loaded")});
},{}]},{},["O8A1"], null)
//# sourceMappingURL=../frontend/sv-wc-payment-gateway-google-pay.js.map

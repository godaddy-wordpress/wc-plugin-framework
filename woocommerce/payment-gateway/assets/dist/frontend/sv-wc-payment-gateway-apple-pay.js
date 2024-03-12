parcelRequire=function(e,r,t,n){var i,o="function"==typeof parcelRequire&&parcelRequire,u="function"==typeof require&&require;function f(t,n){if(!r[t]){if(!e[t]){var i="function"==typeof parcelRequire&&parcelRequire;if(!n&&i)return i(t,!0);if(o)return o(t,!0);if(u&&"string"==typeof t)return u(t);var c=new Error("Cannot find module '"+t+"'");throw c.code="MODULE_NOT_FOUND",c}p.resolve=function(r){return e[t][1][r]||r},p.cache={};var l=r[t]=new f.Module(t);e[t][0].call(l.exports,p,l,l.exports,this)}return r[t].exports;function p(e){return f(p.resolve(e))}}f.isParcelRequire=!0,f.Module=function(e){this.id=e,this.bundle=f,this.exports={}},f.modules=e,f.cache=r,f.parent=o,f.register=function(r,t){e[r]=[function(e,r){r.exports=t},{}]};for(var c=0;c<t.length;c++)try{f(t[c])}catch(e){i||(i=e)}if(t.length){var l=f(t[t.length-1]);"object"==typeof exports&&"undefined"!=typeof module?module.exports=l:"function"==typeof define&&define.amd?define(function(){return l}):n&&(this[n]=l)}if(parcelRequire=f,i)throw i;return f}({"L57g":[function(require,module,exports) {
function e(t){return(e="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(t)}function t(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function n(e,t){for(var n=0;n<t.length;n++){var i=t[n];i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,o(i.key),i)}}function i(e,t,i){return t&&n(e.prototype,t),i&&n(e,i),Object.defineProperty(e,"prototype",{writable:!1}),e}function o(t){var n=a(t,"string");return"symbol"===e(n)?n:String(n)}function a(t,n){if("object"!==e(t)||null===t)return t;var i=t[Symbol.toPrimitive];if(void 0!==i){var o=i.call(t,n||"default");if("object"!==e(o))return o;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===n?String:Number)(t)}(function(){jQuery(function(e){"use strict";return window.SV_WC_Apple_Pay_Handler_v5_12_2=function(){function n(e){t(this,n),this.init_product_page=this.init_product_page.bind(this),this.init_cart_page=this.init_cart_page.bind(this),this.init_checkout_page=this.init_checkout_page.bind(this),this.on_validate_merchant=this.on_validate_merchant.bind(this),this.validate_merchant=this.validate_merchant.bind(this),this.on_payment_method_selected=this.on_payment_method_selected.bind(this),this.on_shipping_contact_selected=this.on_shipping_contact_selected.bind(this),this.on_shipping_method_selected=this.on_shipping_method_selected.bind(this),this.on_payment_authorized=this.on_payment_authorized.bind(this),this.process_authorization=this.process_authorization.bind(this),this.on_cancel_payment=this.on_cancel_payment.bind(this),this.reset_payment_request=this.reset_payment_request.bind(this),this.get_payment_request=this.get_payment_request.bind(this),this.gateway_id=e.gateway_id,this.gateway_slug=e.gateway_slug,this.merchant_id=e.merchant_id,this.ajax_url=e.ajax_url,this.validate_nonce=e.validate_nonce,this.recalculate_totals_nonce=e.recalculate_totals_nonce,this.process_nonce=e.process_nonce,this.payment_request=e.payment_request,this.generic_error=e.generic_error,this.wrapper=".sv-wc-external-checkout",this.container=".buttons-container",this.button=".sv-wc-apple-pay-button"}return i(n,[{key:"is_available",value:function(){return!!window.ApplePaySession&&ApplePaySession.canMakePaymentsWithActiveCard(this.merchant_id).then(function(e){return e})}},{key:"init",value:function(){var t=this;if(1===e(this.container).children().length&&e(this.wrapper).hide(),this.is_available()&&(e("form.cart").length?this.init_product_page():e("form.woocommerce-cart-form").length?this.init_cart_page():e("form.woocommerce-checkout").length&&this.init_checkout_page(),this.ui_element))return this.payment_request&&(e(this.button).show(),e(this.wrapper).show()),e(document.body).on("click",".sv-wc-apple-pay-button",function(e){var n;e.preventDefault(),t.block_ui();try{return t.session=t.get_new_session(t.payment_request),t.session.onvalidatemerchant=function(e){return t.on_validate_merchant(e)},t.session.onpaymentmethodselected=function(e){return t.on_payment_method_selected(e)},t.session.onshippingcontactselected=function(e){return t.on_shipping_contact_selected(e)},t.session.onshippingmethodselected=function(e){return t.on_shipping_method_selected(e)},t.session.onpaymentauthorized=function(e){return t.on_payment_authorized(e)},t.session.oncancel=function(e){return t.on_cancel_payment(e)},t.session.begin()}catch(i){return n=i,t.fail_payment(n)}})}},{key:"init_product_page",value:function(){return this.ui_element=e("form.cart")}},{key:"init_cart_page",value:function(){var t=this;return this.ui_element=e("form.woocommerce-cart-form").parents("div.woocommerce"),e(document.body).on("updated_cart_totals",function(){return t.reset_payment_request()})}},{key:"init_checkout_page",value:function(){var t=this;return this.ui_element=e("form.woocommerce-checkout"),e(document.body).on("updated_checkout",function(){return t.reset_payment_request()})}},{key:"get_new_session",value:function(e){return new ApplePaySession(this.get_sdk_version(),e)}},{key:"get_sdk_version",value:function(){return 2}},{key:"on_validate_merchant",value:function(e){var t=this;return this.validate_merchant(e.validationURL).then(function(e){return e=JSON.parse(e),t.session.completeMerchantValidation(e)},function(e){return t.session.abort(),t.fail_payment("Merchant could no be validated. "+e.message)})}},{key:"validate_merchant",value:function(t){var n=this;return new Promise(function(i,o){var a;return a={action:"wc_".concat(n.gateway_id,"_apple_pay_validate_merchant"),nonce:n.validate_nonce,merchant_id:n.merchant_id,url:t},e.post(n.ajax_url,a,function(e){return e.success?i(e.data):o(e.data)})})}},{key:"on_payment_method_selected",value:function(t){var n=this;return new Promise(function(t,i){var o;return o={action:"wc_".concat(n.gateway_id,"_apple_pay_recalculate_totals"),nonce:n.recalculate_totals_nonce},e.post(n.ajax_url,o,function(e){return e.success?(o=e.data,t(n.session.completePaymentMethodSelection(o.total,o.line_items))):(console.error("[Apple Pay] Error selecting a shipping contact. "+e.data.message),i(n.session.completePaymentMethodSelection(n.payment_request.total,n.payment_request.lineItems)))})})}},{key:"on_shipping_contact_selected",value:function(t){var n=this;return new Promise(function(i,o){var a;return a={action:"wc_".concat(n.gateway_id,"_apple_pay_recalculate_totals"),nonce:n.recalculate_totals_nonce,contact:t.shippingContact},e.post(n.ajax_url,a,function(e){return e.success?(a=e.data,i(n.session.completeShippingContactSelection(ApplePaySession.STATUS_SUCCESS,a.shipping_methods,a.total,a.line_items))):(console.error("[Apple Pay] Error selecting a shipping contact. "+e.data.message),o(n.session.completeShippingContactSelection(ApplePaySession.STATUS_FAILURE,[],n.payment_request.total,n.payment_request.lineItems)))})})}},{key:"on_shipping_method_selected",value:function(t){var n=this;return new Promise(function(i,o){var a;return a={action:"wc_".concat(n.gateway_id,"_apple_pay_recalculate_totals"),nonce:n.recalculate_totals_nonce,method:t.shippingMethod.identifier},e.post(n.ajax_url,a,function(e){return e.success?(a=e.data,i(n.session.completeShippingMethodSelection(ApplePaySession.STATUS_SUCCESS,a.total,a.line_items))):(console.error("[Apple Pay] Error selecting a shipping method. "+e.data.message),o(n.session.completeShippingMethodSelection(ApplePaySession.STATUS_FAILURE,n.payment_request.total,n.payment_request.lineItems)))})})}},{key:"on_payment_authorized",value:function(e){var t=this;return this.process_authorization(e.payment).then(function(e){return t.set_payment_status(!0),t.complete_purchase(e)},function(e){return t.set_payment_status(!1),t.fail_payment("Payment could no be processed. "+e.message)})}},{key:"process_authorization",value:function(t){var n=this;return new Promise(function(i,o){var a;return a={action:"wc_".concat(n.gateway_id,"_apple_pay_process_payment"),nonce:n.process_nonce,payment:JSON.stringify(t)},e.post(n.ajax_url,a,function(e){return e.success?i(e.data):o(e.data)})})}},{key:"on_cancel_payment",value:function(e){return this.unblock_ui()}},{key:"complete_purchase",value:function(e){return window.location=e.redirect}},{key:"fail_payment",value:function(e){return console.error("[Apple Pay] "+e),this.unblock_ui(),this.render_errors([this.generic_error])}},{key:"set_payment_status",value:function(e){var t;return t=e?ApplePaySession.STATUS_SUCCESS:ApplePaySession.STATUS_FAILURE,this.session.completePayment(t)}},{key:"reset_payment_request",value:function(){var t=this,n=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};return this.block_ui(),this.get_payment_request(n).then(function(n){return e(t.button).show(),e(t.wrapper).show(),t.payment_request=JSON.parse(n),t.unblock_ui()},function(n){return console.error("[Apple Pay] Could not build payment request. "+n.message),e(t.button).hide(),1===e(t.container).children().length&&e(t.wrapper).hide(),t.unblock_ui()})}},{key:"get_payment_request",value:function(t){var n=this;return new Promise(function(i,o){var a;return a={action:"wc_".concat(n.gateway_id,"_apple_pay_get_payment_request")},e.extend(t,a),e.post(n.ajax_url,t,function(e){return e.success?i(e.data):o(e.data)})})}},{key:"render_errors",value:function(t){return e(".woocommerce-error, .woocommerce-message").remove(),this.ui_element.prepend('<ul class="woocommerce-error"><li>'+t.join("</li><li>")+"</li></ul>"),this.ui_element.removeClass("processing").unblock(),e("html, body").animate({scrollTop:this.ui_element.offset().top-100},1e3)}},{key:"block_ui",value:function(){return this.ui_element.block({message:null,overlayCSS:{background:"#fff",opacity:.6}})}},{key:"unblock_ui",value:function(){return this.ui_element.unblock()}}]),n}(),e(document.body).trigger("sv_wc_apple_pay_handler_v5_12_2_loaded")})}).call(this);
},{}]},{},["L57g"], null)
//# sourceMappingURL=../frontend/sv-wc-payment-gateway-apple-pay.js.map
parcelRequire=function(e,r,t,n){var i,o="function"==typeof parcelRequire&&parcelRequire,u="function"==typeof require&&require;function f(t,n){if(!r[t]){if(!e[t]){var i="function"==typeof parcelRequire&&parcelRequire;if(!n&&i)return i(t,!0);if(o)return o(t,!0);if(u&&"string"==typeof t)return u(t);var c=new Error("Cannot find module '"+t+"'");throw c.code="MODULE_NOT_FOUND",c}p.resolve=function(r){return e[t][1][r]||r},p.cache={};var l=r[t]=new f.Module(t);e[t][0].call(l.exports,p,l,l.exports,this)}return r[t].exports;function p(e){return f(p.resolve(e))}}f.isParcelRequire=!0,f.Module=function(e){this.id=e,this.bundle=f,this.exports={}},f.modules=e,f.cache=r,f.parent=o,f.register=function(r,t){e[r]=[function(e,r){r.exports=t},{}]};for(var c=0;c<t.length;c++)try{f(t[c])}catch(e){i||(i=e)}if(t.length){var l=f(t[t.length-1]);"object"==typeof exports&&"undefined"!=typeof module?module.exports=l:"function"==typeof define&&define.amd?define(function(){return l}):n&&(this[n]=l)}if(parcelRequire=f,i)throw i;return f}({"L57g":[function(require,module,exports) {
function e(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function t(e,t){for(var n=0;n<t.length;n++){var i=t[n];i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,i.key,i)}}function n(e,n,i){return n&&t(e.prototype,n),i&&t(e,i),e}(function(){jQuery(function(t){"use strict";return window.SV_WC_Apple_Pay_Handler_v5_10_11=function(){function i(t){e(this,i),this.init_product_page=this.init_product_page.bind(this),this.init_cart_page=this.init_cart_page.bind(this),this.init_checkout_page=this.init_checkout_page.bind(this),this.on_validate_merchant=this.on_validate_merchant.bind(this),this.validate_merchant=this.validate_merchant.bind(this),this.on_payment_method_selected=this.on_payment_method_selected.bind(this),this.on_shipping_contact_selected=this.on_shipping_contact_selected.bind(this),this.on_shipping_method_selected=this.on_shipping_method_selected.bind(this),this.on_payment_authorized=this.on_payment_authorized.bind(this),this.process_authorization=this.process_authorization.bind(this),this.on_cancel_payment=this.on_cancel_payment.bind(this),this.reset_payment_request=this.reset_payment_request.bind(this),this.get_payment_request=this.get_payment_request.bind(this),this.gateway_id=t.gateway_id,this.gateway_slug=t.gateway_slug,this.merchant_id=t.merchant_id,this.ajax_url=t.ajax_url,this.validate_nonce=t.validate_nonce,this.recalculate_totals_nonce=t.recalculate_totals_nonce,this.process_nonce=t.process_nonce,this.payment_request=t.payment_request,this.generic_error=t.generic_error,this.wrapper=".sv-wc-external-checkout",this.container=".buttons-container",this.button=".sv-wc-apple-pay-button"}return n(i,[{key:"is_available",value:function(){return!!window.ApplePaySession&&ApplePaySession.canMakePaymentsWithActiveCard(this.merchant_id).then(function(e){return e})}},{key:"init",value:function(){var e=this;if(1===t(this.container).children().length&&t(this.wrapper).hide(),this.is_available()&&(t("form.cart").length?this.init_product_page():t("form.woocommerce-cart-form").length?this.init_cart_page():t("form.woocommerce-checkout").length&&this.init_checkout_page(),this.ui_element))return this.payment_request&&(t(this.button).show(),t(this.wrapper).show()),t(document.body).on("click",".sv-wc-apple-pay-button",function(t){var n;t.preventDefault(),e.block_ui();try{return e.session=e.get_new_session(e.payment_request),e.session.onvalidatemerchant=function(t){return e.on_validate_merchant(t)},e.session.onpaymentmethodselected=function(t){return e.on_payment_method_selected(t)},e.session.onshippingcontactselected=function(t){return e.on_shipping_contact_selected(t)},e.session.onshippingmethodselected=function(t){return e.on_shipping_method_selected(t)},e.session.onpaymentauthorized=function(t){return e.on_payment_authorized(t)},e.session.oncancel=function(t){return e.on_cancel_payment(t)},e.session.begin()}catch(i){return n=i,e.fail_payment(n)}})}},{key:"init_product_page",value:function(){return this.ui_element=t("form.cart")}},{key:"init_cart_page",value:function(){var e=this;return this.ui_element=t("form.woocommerce-cart-form").parents("div.woocommerce"),t(document.body).on("updated_cart_totals",function(){return e.reset_payment_request()})}},{key:"init_checkout_page",value:function(){var e=this;return this.ui_element=t("form.woocommerce-checkout"),t(document.body).on("updated_checkout",function(){return e.reset_payment_request()})}},{key:"get_new_session",value:function(e){return new ApplePaySession(this.get_sdk_version(),e)}},{key:"get_sdk_version",value:function(){return 2}},{key:"on_validate_merchant",value:function(e){var t=this;return this.validate_merchant(e.validationURL).then(function(e){return e=JSON.parse(e),t.session.completeMerchantValidation(e)},function(e){return t.session.abort(),t.fail_payment("Merchant could no be validated. "+e.message)})}},{key:"validate_merchant",value:function(e){var n=this;return new Promise(function(i,a){var o;return o={action:"wc_".concat(n.gateway_id,"_apple_pay_validate_merchant"),nonce:n.validate_nonce,merchant_id:n.merchant_id,url:e},t.post(n.ajax_url,o,function(e){return e.success?i(e.data):a(e.data)})})}},{key:"on_payment_method_selected",value:function(e){var n=this;return new Promise(function(e,i){var a;return a={action:"wc_".concat(n.gateway_id,"_apple_pay_recalculate_totals"),nonce:n.recalculate_totals_nonce},t.post(n.ajax_url,a,function(t){return t.success?(a=t.data,e(n.session.completePaymentMethodSelection(a.total,a.line_items))):(console.error("[Apple Pay] Error selecting a shipping contact. "+t.data.message),i(n.session.completePaymentMethodSelection(n.payment_request.total,n.payment_request.lineItems)))})})}},{key:"on_shipping_contact_selected",value:function(e){var n=this;return new Promise(function(i,a){var o;return o={action:"wc_".concat(n.gateway_id,"_apple_pay_recalculate_totals"),nonce:n.recalculate_totals_nonce,contact:e.shippingContact},t.post(n.ajax_url,o,function(e){return e.success?(o=e.data,i(n.session.completeShippingContactSelection(ApplePaySession.STATUS_SUCCESS,o.shipping_methods,o.total,o.line_items))):(console.error("[Apple Pay] Error selecting a shipping contact. "+e.data.message),a(n.session.completeShippingContactSelection(ApplePaySession.STATUS_FAILURE,[],n.payment_request.total,n.payment_request.lineItems)))})})}},{key:"on_shipping_method_selected",value:function(e){var n=this;return new Promise(function(i,a){var o;return o={action:"wc_".concat(n.gateway_id,"_apple_pay_recalculate_totals"),nonce:n.recalculate_totals_nonce,method:e.shippingMethod.identifier},t.post(n.ajax_url,o,function(e){return e.success?(o=e.data,i(n.session.completeShippingMethodSelection(ApplePaySession.STATUS_SUCCESS,o.total,o.line_items))):(console.error("[Apple Pay] Error selecting a shipping method. "+e.data.message),a(n.session.completeShippingMethodSelection(ApplePaySession.STATUS_FAILURE,n.payment_request.total,n.payment_request.lineItems)))})})}},{key:"on_payment_authorized",value:function(e){var t=this;return this.process_authorization(e.payment).then(function(e){return t.set_payment_status(!0),t.complete_purchase(e)},function(e){return t.set_payment_status(!1),t.fail_payment("Payment could no be processed. "+e.message)})}},{key:"process_authorization",value:function(e){var n=this;return new Promise(function(i,a){var o;return o={action:"wc_".concat(n.gateway_id,"_apple_pay_process_payment"),nonce:n.process_nonce,payment:JSON.stringify(e)},t.post(n.ajax_url,o,function(e){return e.success?i(e.data):a(e.data)})})}},{key:"on_cancel_payment",value:function(e){return this.unblock_ui()}},{key:"complete_purchase",value:function(e){return window.location=e.redirect}},{key:"fail_payment",value:function(e){return console.error("[Apple Pay] "+e),this.unblock_ui(),this.render_errors([this.generic_error])}},{key:"set_payment_status",value:function(e){var t;return t=e?ApplePaySession.STATUS_SUCCESS:ApplePaySession.STATUS_FAILURE,this.session.completePayment(t)}},{key:"reset_payment_request",value:function(){var e=this,n=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};return this.block_ui(),this.get_payment_request(n).then(function(n){return t(e.button).show(),t(e.wrapper).show(),e.payment_request=JSON.parse(n),e.unblock_ui()},function(n){return console.error("[Apple Pay] Could not build payment request. "+n.message),t(e.button).hide(),1===t(e.container).children().length&&t(e.wrapper).hide(),e.unblock_ui()})}},{key:"get_payment_request",value:function(e){var n=this;return new Promise(function(i,a){var o;return o={action:"wc_".concat(n.gateway_id,"_apple_pay_get_payment_request")},t.extend(e,o),t.post(n.ajax_url,e,function(e){return e.success?i(e.data):a(e.data)})})}},{key:"render_errors",value:function(e){return t(".woocommerce-error, .woocommerce-message").remove(),this.ui_element.prepend('<ul class="woocommerce-error"><li>'+e.join("</li><li>")+"</li></ul>"),this.ui_element.removeClass("processing").unblock(),t("html, body").animate({scrollTop:this.ui_element.offset().top-100},1e3)}},{key:"block_ui",value:function(){return this.ui_element.block({message:null,overlayCSS:{background:"#fff",opacity:.6}})}},{key:"unblock_ui",value:function(){return this.ui_element.unblock()}}]),i}(),t(document.body).trigger("sv_wc_apple_pay_handler_v5_10_11_loaded")})}).call(this);
},{}]},{},["L57g"], null)
//# sourceMappingURL=../frontend/sv-wc-payment-gateway-apple-pay.js.map

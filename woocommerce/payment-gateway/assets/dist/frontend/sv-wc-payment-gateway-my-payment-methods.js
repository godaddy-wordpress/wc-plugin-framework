parcelRequire=function(e,r,t,n){var i,o="function"==typeof parcelRequire&&parcelRequire,u="function"==typeof require&&require;function f(t,n){if(!r[t]){if(!e[t]){var i="function"==typeof parcelRequire&&parcelRequire;if(!n&&i)return i(t,!0);if(o)return o(t,!0);if(u&&"string"==typeof t)return u(t);var c=new Error("Cannot find module '"+t+"'");throw c.code="MODULE_NOT_FOUND",c}p.resolve=function(r){return e[t][1][r]||r},p.cache={};var l=r[t]=new f.Module(t);e[t][0].call(l.exports,p,l,l.exports,this)}return r[t].exports;function p(e){return f(p.resolve(e))}}f.isParcelRequire=!0,f.Module=function(e){this.id=e,this.bundle=f,this.exports={}},f.modules=e,f.cache=r,f.parent=o,f.register=function(r,t){e[r]=[function(e,r){r.exports=t},{}]};for(var c=0;c<t.length;c++)try{f(t[c])}catch(e){i||(i=e)}if(t.length){var l=f(t[t.length-1]);"object"==typeof exports&&"undefined"!=typeof module?module.exports=l:"function"==typeof define&&define.amd?define(function(){return l}):n&&(this[n]=l)}if(parcelRequire=f,i)throw i;return f}({"nDDW":[function(require,module,exports) {
function e(t){return(e="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(t)}function t(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function n(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,i(o.key),o)}}function o(e,t,o){return t&&n(e.prototype,t),o&&n(e,o),Object.defineProperty(e,"prototype",{writable:!1}),e}function i(t){var n=a(t,"string");return"symbol"===e(n)?n:String(n)}function a(t,n){if("object"!==e(t)||null===t)return t;var o=t[Symbol.toPrimitive];if(void 0!==o){var i=o.call(t,n||"default");if("object"!==e(i))return i;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===n?String:Number)(t)}(function(){jQuery(function(e){"use strict";return window.SV_WC_Payment_Methods_Handler_v5_11_5=function(){function n(o){var i=this;t(this,n),this.replace_method_column=this.replace_method_column.bind(this),this.remove_duplicate_default_marks=this.remove_duplicate_default_marks.bind(this),this.edit_method=this.edit_method.bind(this),this.save_method=this.save_method.bind(this),this.cancel_edit=this.cancel_edit.bind(this),this.id=o.id,this.slug=o.slug,this.i18n=o.i18n,this.ajax_url=o.ajax_url,this.ajax_nonce=o.ajax_nonce,this.replace_method_column(),this.remove_duplicate_default_marks(),e(".woocommerce-MyAccount-paymentMethods").on("click",".woocommerce-PaymentMethod--actions .button.edit",function(e){return i.edit_method(e)}),e(".woocommerce-MyAccount-paymentMethods").on("click",".woocommerce-PaymentMethod--actions .button.save",function(e){return i.save_method(e)}),e(".woocommerce-MyAccount-paymentMethods").on("click",".woocommerce-PaymentMethod--actions .cancel-edit",function(e){return i.cancel_edit(e)}),e(".woocommerce-MyAccount-paymentMethods").on("click",".woocommerce-PaymentMethod--actions .button.delete",function(t){if(0!==e(t.currentTarget).parents("tr").find("input[name=plugin-id][value=".concat(i.slug,"]")).length)return confirm(i.i18n.delete_ays)?void 0:t.preventDefault()}),e('.button[href*="add-payment-method"]').click(function(t){if(e(this).hasClass("disabled"))return t.preventDefault()})}return o(n,[{key:"replace_method_column",value:function(){var t=this;return e(".woocommerce-MyAccount-paymentMethods").find("tr").each(function(n,o){var i;if(0!==e(o).find("input[name=plugin-id][value=".concat(t.slug,"]")).length)return e(o).find("th.woocommerce-PaymentMethod--title").remove(),(i=e(o).find("td.woocommerce-PaymentMethod--title")).children().length>0&&e(o).find("td.woocommerce-PaymentMethod--method").html(i.html()),e(o).find("td.woocommerce-PaymentMethod--title").remove()})}},{key:"remove_duplicate_default_marks",value:function(){return e(".woocommerce-MyAccount-paymentMethods").find("tr").each(function(t,n){return e(n).find("td.woocommerce-PaymentMethod--default").find("mark.default:not(:first-child)").remove()})}},{key:"edit_method",value:function(t){var n,o;if(t.preventDefault(),0!==(o=(n=e(t.currentTarget)).parents("tr")).find("input[name=plugin-id][value=".concat(this.slug,"]")).length)return o.find("div.view").hide(),o.find("div.edit").show(),o.addClass("editing"),n.text(this.i18n.cancel_button).removeClass("edit").addClass("cancel-edit").removeClass("button"),this.enable_editing_ui()}},{key:"save_method",value:function(t){var n,o,i,a=this;if(t.preventDefault(),n=e(t.currentTarget),0!==(i=n.parents("tr")).find("input[name=plugin-id][value=".concat(this.slug,"]")).length)return this.block_ui(),i.next(".error").remove(),o={action:"wc_".concat(this.id,"_save_payment_method"),nonce:this.ajax_nonce,token_id:i.find("input[name=token-id]").val(),data:i.find("input[name]").serialize()},e.post(this.ajax_url,o).done(function(e){return e.success?(null!=e.data.title&&i.find(".woocommerce-PaymentMethod--method").html(e.data.title),null!=e.data.nonce&&(a.ajax_nonce=e.data.nonce),n.siblings(".cancel-edit").removeClass("cancel-edit").addClass("edit").text(a.i18n.edit_button).addClass("button"),a.disable_editing_ui()):a.display_error(i,e.data)}).fail(function(e,t,n){return a.display_error(i,n)}).always(function(){return a.unblock_ui()})}},{key:"cancel_edit",value:function(t){var n,o;if(t.preventDefault(),0!==(o=(n=e(t.currentTarget)).parents("tr")).find("input[name=plugin-id][value=".concat(this.slug,"]")).length)return o.find("div.view").show(),o.find("div.edit").hide(),o.removeClass("editing"),n.removeClass("cancel-edit").addClass("edit").text(this.i18n.edit_button).addClass("button"),this.disable_editing_ui()}},{key:"enable_editing_ui",value:function(){return e(".woocommerce-MyAccount-paymentMethods").addClass("editing"),e('.button[href*="add-payment-method"]').addClass("disabled")}},{key:"disable_editing_ui",value:function(){return e(".woocommerce-MyAccount-paymentMethods").removeClass("editing"),e('.button[href*="add-payment-method"]').removeClass("disabled")}},{key:"block_ui",value:function(){return e(".woocommerce-MyAccount-paymentMethods").parent("div").block({message:null,overlayCSS:{background:"#fff",opacity:.6}})}},{key:"unblock_ui",value:function(){return e(".woocommerce-MyAccount-paymentMethods").parent("div").unblock()}},{key:"display_error",value:function(t,n){var o,i=arguments.length>2&&void 0!==arguments[2]?arguments[2]:"";return console.error(n),i||(i=this.i18n.save_error),o=e(".woocommerce-MyAccount-paymentMethods thead tr th").length,e('<tr class="error"><td colspan="'+o+'">'+i+"</td></tr>").insertAfter(t).find("td").delay(8e3).slideUp(200)}}]),n}(),e(document.body).trigger("sv_wc_payment_methods_handler_v5_11_5_loaded")})}).call(this);
},{}]},{},["nDDW"], null)
//# sourceMappingURL=../frontend/sv-wc-payment-gateway-my-payment-methods.js.map

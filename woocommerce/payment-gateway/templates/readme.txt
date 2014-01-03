WooCommerce Payment Gateway Framework

This source file is subject to the GNU General Public License v3.0
that is bundled with this package in the file license.txt.
It is also available through the world-wide-web at this URL:
http://www.gnu.org/licenses/gpl-3.0.html
If you did not receive a copy of the license and are unable to
obtain it through the world-wide-web, please send an email
to license@skyverge.com so we can send you a copy immediately.

Copyright (c) 2013-2014, SkyVerge, Inc.
http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0

The templates contained within this directory are not true WordPress templates,
but more akin to "meta" templates.  They are to be used as a base for quickly
creating gateway-specific templates for gateway implementations.  They can be
made ready for use by copying into the appropriate directory structure within
the plugin, and performing the following renames/text substitutions:

* For credit card gateways that collect payment details during checkout, rename
  credit-card/checkout/gateway-id-payment-fields.php.txt substituting the
  actual gateway id
* Remove any non-supported feature blocks, ie if tokenization is not supported,
  remove all text between statements %%IF SUPPORTS TOKENIZATION%% and
  %%ENDIF SUPPORTS TOKENIZATION%%, leaving any text following %%ELSEIF%%.  If
  tokenization is supported, follow the reverse procedure
* %%IF SUPPORTS TEST-PAYMENT-METHOD%% - Is a default test payment method
  supported?  If true, this one may require modifying the 'test' environment id
  as well as the test account number/csc, credit card defaults: 'test',
  '4111111111111111', '123'
* %%PLUGIN NAME%% - plugin name, ie 'WooCommerce Intuit QBMS'
* %%PLUGIN DOCS URL%% - plugin documentation url, ie 'http://docs.woothemes.com/document/intuit-qbms/'
* %%PLUGIN PACKAGE%% - plugin package name, ie 'WC-Intuit-QBMS'
* %%COPYRIGHT YEAR%% - copyright year, ie '2014'
* %%GATEWAY CLASS%% - gateway class name, ie 'WC_Gateway_Intuit_QBMS_Credit_Card'
* %%GATEWAY ID%% - gateway id, ie 'intuit_qbms'
* %%GATEWAY ID DASHERIZED%% - gateway id, ie 'intuit-qbms'
* %%TEXT DOMAIN%% - plugin text domain, ie 'WC_Intuit_QBMS::TEXT_DOMAIN'

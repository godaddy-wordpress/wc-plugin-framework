<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_9\Emails\Traits;

use Automattic\WooCommerce\Internal\Admin\EmailPreview\EmailPreview;
use WC_Product;

/**
 * Adds improved support for the experimental "Email Improvements" feature, by populating any missing properties
 * with default/placeholder values.
 *
 * {@see \Automattic\WooCommerce\Internal\Admin\EmailPreview\EmailPreview::set_email_type()}
 */
trait HasEmailPreviewValuesTrait
{
	/**
	 * Sets "Preview" values on the email object.
	 */
	abstract public function setPreviewValues() : void;

	/**
	 * Gets a random, real product from the site.
	 *
	 * For use in rendering examples for email templates.
	 * @return WC_Product|null
	 */
	protected function getRandomProduct() : ?WC_Product
	{
		$products = array_values(wc_get_products([
			'post_type'      => 'product',
			'posts_per_page' => 1,
			'orderby'        => 'rand',
		]));

		if (isset($products[0]) && $products[0] instanceof WC_Product) {
			return $products[0];
		}

		return null;
	}

	/**
	 * Gets a dummy (fake) product.
	 *
	 * For use in rendering examples for email templates.
	 * @return WC_Product
	 */
	protected function getDummyProduct() : WC_Product
	{
		if (class_exists(EmailPreview::class) && method_exists(EmailPreview::class, 'get_dummy_product_when_not_set')) {
			return EmailPreview::instance()->get_dummy_product_when_not_set(null);
		} else {
			// we should really never end up here!
			return new WC_Product();
		}
	}
}

<?php

namespace SkyVerge\WooCommerce\PluginFramework\v5_16_1\Emails\Traits;

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
}

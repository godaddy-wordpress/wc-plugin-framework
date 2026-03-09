<?php
/**
 * WooCommerce Plugin Framework
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the plugin to newer
 * versions in the future. If you wish to customize the plugin for your
 * needs please refer to http://www.skyverge.com
 *
 * @package   SkyVerge/WooCommerce/Plugin/Classes
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2026, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v6_1_0\Abilities\DataObjects;

use SkyVerge\WooCommerce\PluginFramework\v6_1_0\Traits\CanConvertToArrayTrait;

/**
 * Data object representing an ability category.
 *
 * Categories group related abilities together in the WordPress Abilities API.
 *
 * @since 6.1.0
 */
class AbilityCategory
{
	use CanConvertToArrayTrait;

	/** @var string unique identifier for the category */
	public string $slug;

	/** @var string human-readable label */
	public string $label;

	/** @var string description of the category */
	public string $description;

	/** @var array<string, mixed> optional additional metadata */
	public array $meta = [];

	public function __construct(
		string $slug,
		string $label,
		string $description,
		array $meta = []
	)
	{
		$this->slug = $slug;
		$this->label = $label;
		$this->description = $description;
		$this->meta = $meta;
	}

	/**
	 * {@inheritDoc}
	 *
	 * Returns the format expected by wp_register_ability_category(), excluding slug
	 * since that is passed as a positional argument.
	 *
	 * @link https://developer.wordpress.org/reference/functions/wp_register_ability_category/
	 *
	 * @since 6.1.0
	 */
	public function toArray() : array
	{
		return [
			'label'       => $this->label,
			'description' => $this->description,
			'meta'        => $this->meta,
		];
	}
}

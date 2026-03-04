<?php

namespace SkyVerge\WooCommerce\PluginFramework\v6_0_1\Abilities\DataObjects;

use SkyVerge\WooCommerce\PluginFramework\v6_0_1\Traits\CanConvertToArrayTrait;

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

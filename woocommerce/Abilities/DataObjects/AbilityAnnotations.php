<?php

namespace SkyVerge\WooCommerce\PluginFramework\v6_0_1\Abilities\DataObjects;

/**
 * Data object representing ability behavior annotations.
 *
 * These flags describe the nature of an ability's side effects, used by clients
 * to make informed decisions about execution (e.g. confirming destructive actions).
 *
 * @since 6.1.0
 */
class AbilityAnnotations
{
	/** @var bool whether the ability only reads data and has no side effects */
	public bool $readonly;

	/** @var bool whether the ability may permanently delete or alter data */
	public bool $destructive;

	/** @var bool whether repeated calls with the same input produce the same result */
	public bool $idempotent;

	public function __construct(
		bool $readonly = false,
		bool $destructive = false,
		bool $idempotent = false
	)
	{
		$this->readonly = $readonly;
		$this->destructive = $destructive;
		$this->idempotent = $idempotent;
	}

	/**
	 * Returns the array format expected by WordPress.
	 *
	 * @since 6.1.0
	 */
	public function toArray() : array
	{
		return [
			'readonly'    => $this->readonly,
			'destructive' => $this->destructive,
			'idempotent'  => $this->idempotent,
		];
	}
}

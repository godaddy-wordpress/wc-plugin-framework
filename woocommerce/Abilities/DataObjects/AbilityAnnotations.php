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

namespace SkyVerge\WooCommerce\PluginFramework\v6_1_2\Abilities\DataObjects;

use SkyVerge\WooCommerce\PluginFramework\v6_1_2\Traits\CanConvertToArrayTrait;

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
	use CanConvertToArrayTrait;

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
}

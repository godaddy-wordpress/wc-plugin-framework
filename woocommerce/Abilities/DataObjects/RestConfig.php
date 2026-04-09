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

namespace SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\DataObjects;

use SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\Contracts\RestInputAdapterContract;
use SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\Contracts\RestOutputAdapterContract;

/**
 * Data object for configuring automatic REST API endpoint registration for an ability.
 *
 * When an {@see Ability} carries a non-null RestConfig, the framework automatically
 * registers a WordPress REST API route that executes the ability.
 *
 * @since 6.2.0
 */
class RestConfig
{
    /** @var string REST route path, e.g. '/teams' or '/teams/(?P<team_id>\d+)' */
    public string $path;

    /** @var ?string REST namespace prefix. Null = auto-derived from ability name. */
    public ?string $namespace;

    /** @var string REST namespace version segment, e.g. 'v1' */
    public string $version;

    /** @var ?string HTTP method. Null = inferred from AbilityAnnotations. */
    public ?string $method;

    /** @var ?class-string<RestInputAdapterContract> class that transforms REST input before ability execution */
    public ?string $inputAdapter;

    /** @var ?class-string<RestOutputAdapterContract> class that transforms ability output before REST response */
    public ?string $outputAdapter;

    /**
     * Constructor.
     *
     * @param string $path REST route path
     * @param ?string $namespace REST namespace prefix, or null to auto-derive from ability name
     * @param string $version namespace version segment
     * @param ?string $method HTTP method, or null to infer from annotations
     * @param ?class-string<RestInputAdapterContract> $inputAdapter
     * @param ?class-string<RestOutputAdapterContract> $outputAdapter
     */
    public function __construct(
        string $path,
        ?string $namespace = null,
        string $version = 'v1',
        ?string $method = null,
        ?string $inputAdapter = null,
        ?string $outputAdapter = null
    )
    {
        $this->path = $path;
        $this->namespace = $namespace;
        $this->version = $version;
        $this->method = $method;
        $this->inputAdapter = $inputAdapter;
        $this->outputAdapter = $outputAdapter;
    }
}

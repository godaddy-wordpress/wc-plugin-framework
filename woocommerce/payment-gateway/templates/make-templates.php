<?php
/**
 * WooCommerce Intuit QBMS
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
 * Do not edit or add to this file if you wish to upgrade WooCommerce Intuit QBMS to newer
 * versions in the future. If you wish to customize WooCommerce Intuit QBMS for your
 * needs please refer to http
 *
 * @package   SkyVerge/WooCommerce/Payment-Gateway/Scripts
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2014, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

/**
 * A simple, domain specific LL parser to convert payment gateway framework
 * template templates into plugin templates by substituting variables and
 * handling control structures.
 */

// the template templates
$filenames = array(
	'assets/js/frontend/wc-plugin-id.coffee.txt'              => null,
	'check/checkout/gateway-id-checks-payment-fields.php.txt' => null,
	'check/myaccount/gateway-id-checks-my-accounts.php.txt'   => null,
	'credit-card/checkout/gateway-id-payment-fields.php.txt'  => null,
	'credit-card/myaccount/gateway-id-my-cards.php.txt'       => null,
	'wc-gateway-plugin-id-template.php.txt'                   => null,
);

$variables = array();
$ifs       = array();

foreach ( array_keys( $filenames ) as $filename ) {

	if ( file_exists( $filename ) ) {

		$file_content = file( $filename );

		list( $_variables, $_ifs, $controls ) = parse_file( $file_content );

		// we want the set of variables and if conditions across all template files
		$variables = array_merge( $variables, $_variables );
		$ifs       = array_merge( $ifs, $_ifs );

		// save the control structure of the file
		$filenames[ $filename ] = $controls;

	}

}

if ( count( $argv ) > 1 ) {
	// load from settings file

	$settings_file_name = $argv[1];

	if ( ! file_exists( $settings_file_name ) ) {
		die( "'$settings_file_name' not found" );
	}

	$settings_file_content = parse_settings_file( $settings_file_name );

	$missing_variables = $missing_conditions = array();

	// load up any variables from the settings file
	foreach ( array_keys( $variables ) as $variable_name ) {

		if ( ! isset( $settings_file_content[ $variable_name ] ) ) {
			$missing_variables[] = $variable_name;
		}

		$variables[ $variable_name ] = $settings_file_content[ $variable_name ];

	}

	// load up any if conditions from the settings file
	foreach ( array_keys( $ifs ) as $condition ) {

		if ( ! isset( $settings_file_content[ $condition ] ) ) {
			$missing_conditions[] = $condition;
		}

		$ifs[ $condition ] = ( 1 == $settings_file_content[ $condition ] || 'y' == strtolower( $settings_file_content[ $condition ] ) || 'yes' == strtolower( $settings_file_content[ $condition ] ) )? true : false;

	}

	if ( count( $missing_variables ) > 0 || count( $missing_conditions ) > 0 ) {

		if ( count( $missing_variables ) > 0 ) {
			echo "Error: missing variables: " . implode( ', ', $missing_variables );
		}
		if ( count( $missing_conditions ) > 0 ) {
			echo "Error: missing if conditions: " . implode( ', ', $missing_conditions );
		}

		exit(1);
	}

} else {
	// prompt user for input

	// prompt user for variables
	foreach ( array_keys( $variables ) as $variable_name ) {

		echo $variable_name . ": ";
		$value = trim( fgets( STDIN ) );

		$variables[ $variable_name ] = $value;

	}

	// prompt for supports
	foreach ( array_keys( $ifs ) as $condition ) {

		echo $condition . " (y/n): ";
		$value = trim( fgets( STDIN ) );

		if ( 1 == $value || 'y' == strtolower( $value ) || 'yes' == strtolower( $value ) )
			$value = true;
		else
			$value = false;

		$ifs[ $condition ] = $value;
	}

	// save the entered settings
	write_settings_file( $variables, $ifs );

}

foreach ( $filenames as $filename => $structure ) {

	if ( $structure ) {

		$file_content = file( $filename );
		$output_content = array();

		// handle any ifs
		$file_content = traverse( $structure, $ifs, $file_content );

		// output the final result
		foreach ( $file_content as $line ) {

			if ( ! preg_match( '/^%%([a-zA-Z \-]+)%%$/', $line ) ) {

				foreach ( $variables as $name => $value ) {

					$line = str_replace( '%%' . $name . '%%', $value, $line );

				}

				$output_content[] = $line;

			}

		}

		// fix file names
		$filename = str_replace( 'plugin-id',         str_replace( '_', '-', $variables['PLUGIN ID'] ),         $filename );
		$filename = str_replace( 'gateway-id-checks', str_replace( '_', '-', $variables['GATEWAY ID CHECKS'] ), $filename );
		$filename = str_replace( 'gateway-id',        str_replace( '_', '-', $variables['GATEWAY ID'] ),        $filename );
		$filename = str_replace( '.txt', '', $filename );

		file_put_contents( $filename, $output_content );

	}

}

/**
 * Parse the given file and pull out the variables, if conditions, and control
 * structure
 */
function parse_file( $contents ) {

	// dictionaries of discovered variables and control structures
	$variables = array();
	$ifs       = array();

	$controls  = new stdclass();
	$controls->type = 'root';
	$controls->parent = null;
	$controls->start_line = 0;
	$controls->depth = 0;
	$controls->children = array();

	$current = $controls;

	foreach ( $contents as $number => $line ) {

		if ( preg_match_all( '/%%([a-zA-Z \-]+)%%/', $line, $matches ) ) {

			for ( $i = 0, $ix = count( $matches[1] ); $i < $ix; $i++ ) {

				$pieces = explode( ' ', $matches[1][ $i ] );

				switch ( $pieces[0] ) {

					case 'IF':
						// remove the 'IF'
						array_shift( $pieces );

						$ifs[ implode( ' ', $pieces ) ] = true;

						// create a node to represent this switch statement
						$node = new stdclass();
						$node->type = 'IF';
						$node->start_line = $number;
						$node->condition = implode( ' ', $pieces );
						$node->children = array();
						$node->parent = $current;
						$node->depth = $node->parent->depth + 1;

						$current->children[] = $node;
						$current = $node;

					break;

					case 'ELSEIF':
						// handle an elseif
						$current->else_line = $number;
					break;

					case 'ENDIF':
						// finished this switch statement, return to the parent level
						$current->end_line = $number;
						$current = $current->parent;
					break;

					default:
						// variable
						$variables[ $matches[1][ $i ] ] = true;
					break;

				}

			}

		}

	}

	return array( $variables, $ifs, $controls );

}


function traverse( $node, $ifs, $file ) {

	// perform an iterative pre-order traversal
	$stack = array();

	while ( ! empty( $stack ) || $node ) {

		if ( $node ) {

			$node->visited = true;

			if ( 'IF' == $node->type ) {

				$remove_start = $remove_end = -1;

				if ( $ifs[ $node->condition ] ) {
					// condition is true

					if ( isset( $node->else_line ) && $node->else_line ) {

						// remove the else block
						$remove_start = $node->else_line;
						$remove_end   = $node->end_line;

					}

				} else {
					// conditon is false

					$remove_start = $node->start_line;
					$remove_end   = $node->end_line;

					if ( isset( $node->else_line ) && $node->else_line ) {

						// preserve the else block
						$remove_end = $node->else_line;

					}

				}

				if ( -1 != $remove_start ) {

					for ( $line = $remove_start; $line <= $remove_end; $line++ ) {

						$file[ $line ] = '%%REMOVE%%';

					}

				}

			}

			$next  = null;
			$later = array();

			foreach ( $node->children as $child ) {

				if ( ! isset( $child->visited ) || ! $child->visited ) {

					if ( ! $next ) {

						// next child to visit, starting from the left
						$next = $child;

					} else {

						// later children to visit, in reverse order
						array_unshift( $later, $child );

					}

				}

			}

			// add the child nodes to visit later
			$stack = array_merge( $stack, $later );

			// move on to the next node
			$node = $next;

		} else {

			$node = array_pop( $stack );

		}

	}

	return $file;
}


function write_settings_file( $variables, $ifs ) {

	$contents[] = "#\n";
	$contents[] = "# Plugin Framework Template Settings File Created " . date( 'Y-m-d H:i:s' ) . "\n";
	$contents[] = "#\n";

	$contents[] = "\n# Variables:\n\n";

	foreach ( $variables as $name => $value ) {

		$contents[] = "$name: $value\n";

	}

	$contents[] = "\n# If statements:\n\n";

	foreach ( $ifs as $condition => $value ) {

		$contents[] = "$condition: " . ( $value ? 'y' : 'n' ) . "\n";

	}

	file_put_contents( 'template-settings.txt', $contents );

}


/**
 * Parses the given settings file and returns the name-value pairs
 *
 * @param string $filename settings filename
 * @return array name-value pairs from the settings file
 */
function parse_settings_file( $filename ) {

	$settings = array();
	$file = file( $filename );

	foreach ( $file as $line ) {

		$line = trim( $line );

		if ( $line && '#' != $line{0} ) {

			$pos = strpos( $line, ':' );
			$name = substr( $line, 0, $pos );
			$value = substr( $line, $pos + 1 );

			$name = trim( $name );
			$value = trim( $value );

			$settings[ $name ] = $value;

		}

	}

	return $settings;

}

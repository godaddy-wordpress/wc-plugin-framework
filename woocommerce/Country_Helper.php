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
 * @copyright Copyright (c) 2013-2022, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_10_12;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\v5_10_12\\Country_Helper' ) ) :


/**
 * SkyVerge Country Helper Class
 *
 * The purpose of this class is to centralize country-related utility
 * functions that are commonly used in SkyVerge plugins
 *
 * @since 5.4.3
 */
class Country_Helper {


	/** @var array ISO 3166-alpha2 => ISO 3166-alpha3  */
	static public $alpha3 = [
		'AF' => 'AFG', 'AL' => 'ALB', 'DZ' => 'DZA', 'AD' => 'AND', 'AO' => 'AGO',
		'AG' => 'ATG', 'AR' => 'ARG', 'AM' => 'ARM', 'AU' => 'AUS', 'AT' => 'AUT',
		'AZ' => 'AZE', 'BS' => 'BHS', 'BH' => 'BHR', 'BD' => 'BGD', 'BB' => 'BRB',
		'BY' => 'BLR', 'BE' => 'BEL', 'BZ' => 'BLZ', 'BJ' => 'BEN', 'BT' => 'BTN',
		'BO' => 'BOL', 'BA' => 'BIH', 'BW' => 'BWA', 'BR' => 'BRA', 'BN' => 'BRN',
		'BG' => 'BGR', 'BF' => 'BFA', 'BI' => 'BDI', 'KH' => 'KHM', 'CM' => 'CMR',
		'CA' => 'CAN', 'CV' => 'CPV', 'CF' => 'CAF', 'TD' => 'TCD', 'CL' => 'CHL',
		'CN' => 'CHN', 'CO' => 'COL', 'KM' => 'COM', 'CD' => 'COD', 'CG' => 'COG',
		'CR' => 'CRI', 'CI' => 'CIV', 'HR' => 'HRV', 'CU' => 'CUB', 'CY' => 'CYP',
		'CZ' => 'CZE', 'DK' => 'DNK', 'DJ' => 'DJI', 'DM' => 'DMA', 'DO' => 'DOM',
		'EC' => 'ECU', 'EG' => 'EGY', 'SV' => 'SLV', 'GQ' => 'GNQ', 'ER' => 'ERI',
		'EE' => 'EST', 'ET' => 'ETH', 'FJ' => 'FJI', 'FI' => 'FIN', 'FR' => 'FRA',
		'GA' => 'GAB', 'GM' => 'GMB', 'GE' => 'GEO', 'DE' => 'DEU', 'GH' => 'GHA',
		'GR' => 'GRC', 'GD' => 'GRD', 'GT' => 'GTM', 'GN' => 'GIN', 'GW' => 'GNB',
		'GY' => 'GUY', 'HT' => 'HTI', 'HN' => 'HND', 'HU' => 'HUN', 'IS' => 'ISL',
		'IN' => 'IND', 'ID' => 'IDN', 'IR' => 'IRN', 'IQ' => 'IRQ', 'IE' => 'IRL',
		'IL' => 'ISR', 'IT' => 'ITA', 'JM' => 'JAM', 'JP' => 'JPN', 'JO' => 'JOR',
		'KZ' => 'KAZ', 'KE' => 'KEN', 'KI' => 'KIR', 'KP' => 'PRK', 'KR' => 'KOR',
		'KW' => 'KWT', 'KG' => 'KGZ', 'LA' => 'LAO', 'LV' => 'LVA', 'LB' => 'LBN',
		'LS' => 'LSO', 'LR' => 'LBR', 'LY' => 'LBY', 'LI' => 'LIE', 'LT' => 'LTU',
		'LU' => 'LUX', 'MK' => 'MKD', 'MG' => 'MDG', 'MW' => 'MWI', 'MY' => 'MYS',
		'MV' => 'MDV', 'ML' => 'MLI', 'MT' => 'MLT', 'MH' => 'MHL', 'MR' => 'MRT',
		'MU' => 'MUS', 'MX' => 'MEX', 'FM' => 'FSM', 'MD' => 'MDA', 'MC' => 'MCO',
		'MN' => 'MNG', 'ME' => 'MNE', 'MA' => 'MAR', 'MZ' => 'MOZ', 'MM' => 'MMR',
		'NA' => 'NAM', 'NR' => 'NRU', 'NP' => 'NPL', 'NL' => 'NLD', 'NZ' => 'NZL',
		'NI' => 'NIC', 'NE' => 'NER', 'NG' => 'NGA', 'NO' => 'NOR', 'OM' => 'OMN',
		'PK' => 'PAK', 'PW' => 'PLW', 'PA' => 'PAN', 'PG' => 'PNG', 'PY' => 'PRY',
		'PE' => 'PER', 'PH' => 'PHL', 'PL' => 'POL', 'PT' => 'PRT', 'QA' => 'QAT',
		'RO' => 'ROU', 'RU' => 'RUS', 'RW' => 'RWA', 'KN' => 'KNA', 'LC' => 'LCA',
		'VC' => 'VCT', 'WS' => 'WSM', 'SM' => 'SMR', 'ST' => 'STP', 'SA' => 'SAU',
		'SN' => 'SEN', 'RS' => 'SRB', 'SC' => 'SYC', 'SL' => 'SLE', 'SG' => 'SGP',
		'SK' => 'SVK', 'SI' => 'SVN', 'SB' => 'SLB', 'SO' => 'SOM', 'ZA' => 'ZAF',
		'ES' => 'ESP', 'LK' => 'LKA', 'SD' => 'SDN', 'SR' => 'SUR', 'SZ' => 'SWZ',
		'SE' => 'SWE', 'CH' => 'CHE', 'SY' => 'SYR', 'TJ' => 'TJK', 'TZ' => 'TZA',
		'TH' => 'THA', 'TL' => 'TLS', 'TG' => 'TGO', 'TO' => 'TON', 'TT' => 'TTO',
		'TN' => 'TUN', 'TR' => 'TUR', 'TM' => 'TKM', 'TV' => 'TUV', 'UG' => 'UGA',
		'UA' => 'UKR', 'AE' => 'ARE', 'GB' => 'GBR', 'US' => 'USA', 'UY' => 'URY',
		'UZ' => 'UZB', 'VU' => 'VUT', 'VA' => 'VAT', 'VE' => 'VEN', 'VN' => 'VNM',
		'YE' => 'YEM', 'ZM' => 'ZMB', 'ZW' => 'ZWE', 'TW' => 'TWN', 'CX' => 'CXR',
		'CC' => 'CCK', 'HM' => 'HMD', 'NF' => 'NFK', 'NC' => 'NCL', 'PF' => 'PYF',
		'YT' => 'MYT', 'GP' => 'GLP', 'PM' => 'SPM', 'WF' => 'WLF', 'TF' => 'ATF',
		'BV' => 'BVT', 'CK' => 'COK', 'NU' => 'NIU', 'TK' => 'TKL', 'GG' => 'GGY',
		'IM' => 'IMN', 'JE' => 'JEY', 'AI' => 'AIA', 'BM' => 'BMU', 'IO' => 'IOT',
		'VG' => 'VGB', 'KY' => 'CYM', 'FK' => 'FLK', 'GI' => 'GIB', 'MS' => 'MSR',
		'PN' => 'PCN', 'SH' => 'SHN', 'GS' => 'SGS', 'TC' => 'TCA', 'MP' => 'MNP',
		'PR' => 'PRI', 'AS' => 'ASM', 'UM' => 'UMI', 'GU' => 'GUM', 'VI' => 'VIR',
		'HK' => 'HKG', 'MO' => 'MAC', 'FO' => 'FRO', 'GL' => 'GRL', 'GF' => 'GUF',
		'MQ' => 'MTQ', 'RE' => 'REU', 'AX' => 'ALA', 'AW' => 'ABW', 'AN' => 'ANT',
		'SJ' => 'SJM', 'AC' => 'ASC', 'TA' => 'TAA', 'AQ' => 'ATA', 'CW' => 'CUW',
	];

	/** @var array ISO 3166-alpha2 => ISO 3166-numeric  */
	static public $numeric = [
		'AF' => '004', 'AX' => '248', 'AL' => '008', 'DZ' => '012', 'AS' => '016',
		'AD' => '020', 'AO' => '024', 'AI' => '660', 'AQ' => '010', 'AG' => '028',
		'AR' => '032', 'AM' => '051', 'AW' => '533', 'AU' => '036', 'AT' => '040',
		'AZ' => '031', 'BS' => '044', 'BH' => '048', 'BD' => '050', 'BB' => '052',
		'BY' => '112', 'BE' => '056', 'BZ' => '084', 'BJ' => '204', 'BM' => '060',
		'BT' => '064', 'BO' => '068', 'BQ' => '535', 'BA' => '070', 'BW' => '072',
		'BV' => '074', 'BR' => '076', 'IO' => '086', 'BN' => '096', 'BG' => '100',
		'BF' => '854', 'BI' => '108', 'KH' => '116', 'CM' => '120', 'CA' => '124',
		'CV' => '132', 'KY' => '136', 'CF' => '140', 'TD' => '148', 'CL' => '152',
		'CN' => '156', 'CX' => '162', 'CC' => '166', 'CO' => '170', 'KM' => '174',
		'CG' => '178', 'CD' => '180', 'CK' => '184', 'CR' => '188', 'CI' => '384',
		'HR' => '191', 'CU' => '192', 'CW' => '531', 'CY' => '196', 'CZ' => '203',
		'DK' => '208', 'DJ' => '262', 'DM' => '212', 'DO' => '214', 'EC' => '218',
		'EG' => '818', 'SV' => '222', 'GQ' => '226', 'ER' => '232', 'EE' => '233',
		'ET' => '231', 'FK' => '238', 'FO' => '234', 'FJ' => '242', 'FI' => '246',
		'FR' => '250', 'GF' => '254', 'PF' => '258', 'TF' => '260', 'GA' => '266',
		'GM' => '270', 'GE' => '268', 'DE' => '276', 'GH' => '288', 'GI' => '292',
		'GR' => '300', 'GL' => '304', 'GD' => '308', 'GP' => '312', 'GU' => '316',
		'GT' => '320', 'GG' => '831', 'GN' => '324', 'GW' => '624', 'GY' => '328',
		'HT' => '332', 'HM' => '334', 'VA' => '336', 'HN' => '340', 'HK' => '344',
		'HU' => '348', 'IS' => '352', 'IN' => '356', 'ID' => '360', 'IR' => '364',
		'IQ' => '368', 'IE' => '372', 'IM' => '833', 'IL' => '376', 'IT' => '380',
		'JM' => '388', 'JP' => '392', 'JE' => '832', 'JO' => '400', 'KZ' => '398',
		'KE' => '404', 'KI' => '296', 'KP' => '408', 'KR' => '410', 'KW' => '414',
		'KG' => '417', 'LA' => '418', 'LV' => '428', 'LB' => '422', 'LS' => '426',
		'LR' => '430', 'LY' => '434', 'LI' => '438', 'LT' => '440', 'LU' => '442',
		'MO' => '446', 'MK' => '807', 'MG' => '450', 'MW' => '454', 'MY' => '458',
		'MV' => '462', 'ML' => '466', 'MT' => '470', 'MH' => '584', 'MQ' => '474',
		'MR' => '478', 'MU' => '480', 'YT' => '175', 'MX' => '484', 'FM' => '583',
		'MD' => '498', 'MC' => '492', 'MN' => '496', 'ME' => '499', 'MS' => '500',
		'MA' => '504', 'MZ' => '508', 'MM' => '104', 'NA' => '516', 'NR' => '520',
		'NP' => '524', 'NL' => '528', 'NC' => '540', 'NZ' => '554', 'NI' => '558',
		'NE' => '562', 'NG' => '566', 'NU' => '570', 'NF' => '574', 'MP' => '580',
		'NO' => '578', 'OM' => '512', 'PK' => '586', 'PW' => '585', 'PS' => '275',
		'PA' => '591', 'PG' => '598', 'PY' => '600', 'PE' => '604', 'PH' => '608',
		'PN' => '612', 'PL' => '616', 'PT' => '620', 'PR' => '630', 'QA' => '634',
		'RE' => '638', 'RO' => '642', 'RU' => '643', 'RW' => '646', 'BL' => '652',
		'SH' => '654', 'KN' => '659', 'LC' => '662', 'MF' => '663', 'PM' => '666',
		'VC' => '670', 'WS' => '882', 'SM' => '674', 'ST' => '678', 'SA' => '682',
		'SN' => '686', 'RS' => '688', 'SC' => '690', 'SL' => '694', 'SG' => '702',
		'SX' => '534', 'SK' => '703', 'SI' => '705', 'SB' => '090', 'SO' => '706',
		'ZA' => '710', 'GS' => '239', 'SS' => '728', 'ES' => '724', 'LK' => '144',
		'SD' => '729', 'SR' => '740', 'SJ' => '744', 'SZ' => '748', 'SE' => '752',
		'CH' => '756', 'SY' => '760', 'TW' => '158', 'TJ' => '762', 'TZ' => '834',
		'TH' => '764', 'TL' => '626', 'TG' => '768', 'TK' => '772', 'TO' => '776',
		'TT' => '780', 'TN' => '788', 'TR' => '792', 'TM' => '795', 'TC' => '796',
		'TV' => '798', 'UG' => '800', 'UA' => '804', 'AE' => '784', 'GB' => '826',
		'US' => '840', 'UM' => '581', 'UY' => '858', 'UZ' => '860', 'VU' => '548',
		'VE' => '862', 'VN' => '704', 'VG' => '092', 'VI' => '850', 'WF' => '876',
		'EH' => '732', 'YE' => '887', 'ZM' => '894', 'ZW' => '716',
	];

	/** @var array ISO 3166-alpha2 => phone calling code(s) */
	static public $calling_codes = [
		'BD' => '+880',
		'BE' => '+32',
		'BF' => '+226',
		'BG' => '+359',
		'BA' => '+387',
		'BB' => '+1246',
		'WF' => '+681',
		'BL' => '+590',
		'BM' => '+1441',
		'BN' => '+673',
		'BO' => '+591',
		'BH' => '+973',
		'BI' => '+257',
		'BJ' => '+229',
		'BT' => '+975',
		'JM' => '+1876',
		'BV' => '',
		'BW' => '+267',
		'WS' => '+685',
		'BQ' => '+599',
		'BR' => '+55',
		'BS' => '+1242',
		'JE' => '+441534',
		'BY' => '+375',
		'BZ' => '+501',
		'RU' => '+7',
		'RW' => '+250',
		'RS' => '+381',
		'TL' => '+670',
		'RE' => '+262',
		'TM' => '+993',
		'TJ' => '+992',
		'RO' => '+40',
		'TK' => '+690',
		'GW' => '+245',
		'GU' => '+1671',
		'GT' => '+502',
		'GS' => '',
		'GR' => '+30',
		'GQ' => '+240',
		'GP' => '+590',
		'JP' => '+81',
		'GY' => '+592',
		'GG' => '+441481',
		'GF' => '+594',
		'GE' => '+995',
		'GD' => '+1473',
		'GB' => '+44',
		'GA' => '+241',
		'SV' => '+503',
		'GN' => '+224',
		'GM' => '+220',
		'GL' => '+299',
		'GI' => '+350',
		'GH' => '+233',
		'OM' => '+968',
		'TN' => '+216',
		'JO' => '+962',
		'HR' => '+385',
		'HT' => '+509',
		'HU' => '+36',
		'HK' => '+852',
		'HN' => '+504',
		'HM' => '',
		'VE' => '+58',
		'PR' => [
			'+1787',
			'+1939',
		],
		'PS' => '+970',
		'PW' => '+680',
		'PT' => '+351',
		'SJ' => '+47',
		'PY' => '+595',
		'IQ' => '+964',
		'PA' => '+507',
		'PF' => '+689',
		'PG' => '+675',
		'PE' => '+51',
		'PK' => '+92',
		'PH' => '+63',
		'PN' => '+870',
		'PL' => '+48',
		'PM' => '+508',
		'ZM' => '+260',
		'EH' => '+212',
		'EE' => '+372',
		'EG' => '+20',
		'ZA' => '+27',
		'EC' => '+593',
		'IT' => '+39',
		'VN' => '+84',
		'SB' => '+677',
		'ET' => '+251',
		'SO' => '+252',
		'ZW' => '+263',
		'SA' => '+966',
		'ES' => '+34',
		'ER' => '+291',
		'ME' => '+382',
		'MD' => '+373',
		'MG' => '+261',
		'MF' => '+590',
		'MA' => '+212',
		'MC' => '+377',
		'UZ' => '+998',
		'MM' => '+95',
		'ML' => '+223',
		'MO' => '+853',
		'MN' => '+976',
		'MH' => '+692',
		'MK' => '+389',
		'MU' => '+230',
		'MT' => '+356',
		'MW' => '+265',
		'MV' => '+960',
		'MQ' => '+596',
		'MP' => '+1670',
		'MS' => '+1664',
		'MR' => '+222',
		'IM' => '+441624',
		'UG' => '+256',
		'TZ' => '+255',
		'MY' => '+60',
		'MX' => '+52',
		'IL' => '+972',
		'FR' => '+33',
		'IO' => '+246',
		'SH' => '+290',
		'FI' => '+358',
		'FJ' => '+679',
		'FK' => '+500',
		'FM' => '+691',
		'FO' => '+298',
		'NI' => '+505',
		'NL' => '+31',
		'NO' => '+47',
		'NA' => '+264',
		'VU' => '+678',
		'NC' => '+687',
		'NE' => '+227',
		'NF' => '+672',
		'NG' => '+234',
		'NZ' => '+64',
		'NP' => '+977',
		'NR' => '+674',
		'NU' => '+683',
		'CK' => '+682',
		'XK' => '',
		'CI' => '+225',
		'CH' => '+41',
		'CO' => '+57',
		'CN' => '+86',
		'CM' => '+237',
		'CL' => '+56',
		'CC' => '+61',
		'CA' => '+1',
		'CG' => '+242',
		'CF' => '+236',
		'CD' => '+243',
		'CZ' => '+420',
		'CY' => '+357',
		'CX' => '+61',
		'CR' => '+506',
		'CW' => '+599',
		'CV' => '+238',
		'CU' => '+53',
		'SZ' => '+268',
		'SY' => '+963',
		'SX' => '+599',
		'KG' => '+996',
		'KE' => '+254',
		'SS' => '+211',
		'SR' => '+597',
		'KI' => '+686',
		'KH' => '+855',
		'KN' => '+1869',
		'KM' => '+269',
		'ST' => '+239',
		'SK' => '+421',
		'KR' => '+82',
		'SI' => '+386',
		'KP' => '+850',
		'KW' => '+965',
		'SN' => '+221',
		'SM' => '+378',
		'SL' => '+232',
		'SC' => '+248',
		'KZ' => '+7',
		'KY' => '+1345',
		'SG' => '+65',
		'SE' => '+46',
		'SD' => '+249',
		'DO' => [
			'+1809',
			'+1829',
			'+1849',
		],
		'DM' => '+1767',
		'DJ' => '+253',
		'DK' => '+45',
		'VG' => '+1284',
		'DE' => '+49',
		'YE' => '+967',
		'DZ' => '+213',
		'US' => '+1',
		'UY' => '+598',
		'YT' => '+262',
		'UM' => '+1',
		'LB' => '+961',
		'LC' => '+1758',
		'LA' => '+856',
		'TV' => '+688',
		'TW' => '+886',
		'TT' => '+1868',
		'TR' => '+90',
		'LK' => '+94',
		'LI' => '+423',
		'LV' => '+371',
		'TO' => '+676',
		'LT' => '+370',
		'LU' => '+352',
		'LR' => '+231',
		'LS' => '+266',
		'TH' => '+66',
		'TF' => '',
		'TG' => '+228',
		'TD' => '+235',
		'TC' => '+1649',
		'LY' => '+218',
		'VA' => '+379',
		'VC' => '+1784',
		'AE' => '+971',
		'AD' => '+376',
		'AG' => '+1268',
		'AF' => '+93',
		'AI' => '+1264',
		'VI' => '+1340',
		'IS' => '+354',
		'IR' => '+98',
		'AM' => '+374',
		'AL' => '+355',
		'AO' => '+244',
		'AQ' => '',
		'AS' => '+1684',
		'AR' => '+54',
		'AU' => '+61',
		'AT' => '+43',
		'AW' => '+297',
		'IN' => '+91',
		'AX' => '+35818',
		'AZ' => '+994',
		'IE' => '+353',
		'ID' => '+62',
		'UA' => '+380',
		'QA' => '+974',
		'MZ' => '+258',
	];


	/** @var array flipped calling codes */
	protected static $flipped_calling_codes;


	/**
	 * Convert a 2-character country code into its 3-character equivalent, or
	 * vice-versa, e.g.
	 *
	 * 1) given USA, returns US
	 * 2) given US, returns USA
	 *
	 * @since 5.4.3
	 *
	 * @param string $code ISO-3166-alpha-2 or ISO-3166-alpha-3 country code
	 * @return string country code
	 */
	public static function convert_alpha_country_code( $code ) {

		$countries = 3 === strlen( $code ) ? array_flip( self::$alpha3 ) : self::$alpha3;

		return isset( $countries[ $code ] ) ? $countries[ $code ] : $code;
	}


	/**
	 * Converts an ISO 3166-alpha2 country code to an ISO 3166-alpha3 country code.
	 *
	 * @since 5.4.3
	 *
	 * @param string $alpha2_code ISO 3166-alpha2 country code
	 * @return string ISO 3166-alpha3 country code
	 */
	public static function alpha2_to_alpha3( $alpha2_code ) {

		return isset( self::$alpha3[ $alpha2_code ] ) ? self::$alpha3[ $alpha2_code ] : '';
	}


	/**
	 * Converts an ISO 3166-alpha2 country code to an ISO 3166-numeric country code.
	 *
	 * @since 5.4.3
	 *
	 * @param string $alpha2_code ISO 3166-alpha2 country code
	 * @return string ISO 3166-numeric country code
	 */
	public static function alpha2_to_numeric( $alpha2_code ) {

		return isset( self::$numeric[ $alpha2_code ] ) ? self::$numeric[ $alpha2_code ] : '';
	}


	/**
	 * Converts an ISO 3166-alpha2 country code to a calling code.
	 *
	 * This conversion is available in WC 3.6+ so we'll call out to that when available.
	 *
	 * @since 5.4.3
	 *
	 * @param string $alpha2_code ISO 3166-alpha2 country code
	 * @return string calling code
	 */
	public static function alpha2_to_calling_code( $alpha2_code ) {

		// check not only for the right version, but if the helper is loaded & available
		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte( '3.6.0' ) && WC() && isset( WC()->countries ) && is_callable( [ WC()->countries, 'get_country_calling_code' ] ) ) {

			$calling_code = WC()->countries->get_country_calling_code( $alpha2_code );

		} else {

			$calling_code = isset( self::$calling_codes[ $alpha2_code ] ) ? self::$calling_codes[ $alpha2_code ] : '';

			// we can't really know _which_ code is to be used, so use the first
			$calling_code = is_array( $calling_code ) ? $calling_code[0] : $calling_code;
		}

		return $calling_code;
	}


	/**
	 * Converts an ISO 3166-alpha3 country code to an ISO 3166-alpha2 country code.
	 *
	 * @since 5.4.3
	 *
	 * @param string $alpha3_code ISO 3166-alpha3 country code
	 * @return string ISO 3166-alpha2 country code
	 */
	public static function alpha3_to_alpha2( $alpha3_code ) {

		$countries = array_flip( self::$alpha3 );

		return isset( $countries[ $alpha3_code ] ) ? $countries[ $alpha3_code ] : '';
	}


	/**
	 * Converts an ISO 3166-alpha3 country code to an ISO 3166-numeric country code.
	 *
	 * @since 5.4.3
	 *
	 * @param string $alpha3_code ISO 3166-alpha3 country code
	 * @return string ISO 3166-numeric country code
	 */
	public static function alpha3_to_numeric( $alpha3_code ) {
		return self::alpha2_to_numeric( self::alpha3_to_alpha2( $alpha3_code ) );
	}


	/**
	 * Converts an ISO 3166-alpha3 country code to a calling code.
	 *
	 * @since 5.4.3
	 *
	 * @param string $alpha3_code ISO 3166-alpha3 country code
	 * @return string calling code
	 */
	public static function alpha3_to_calling_code( $alpha3_code ) {
		return self::alpha2_to_calling_code( self::alpha3_to_alpha2( $alpha3_code ) );
	}


	/**
	 * Converts an ISO 3166-numeric country code to an ISO 3166-alpha2 code.
	 *
	 * @since 5.4.3
	 *
	 * @param string $numeric ISO 3166-numeric country code
	 * @return string ISO 3166-alpha2 country code
	 */
	public static function numeric_to_alpha2( $numeric ) {

		$codes = array_flip( self::$numeric );

		return isset( $codes[ $numeric ] ) ? $codes[ $numeric ] : '';
	}


	/**
	 * Converts an ISO 3166-numeric country code to an ISO 3166-alpha3 code.
	 *
	 * @since 5.4.3
	 *
	 * @param string $numeric ISO 3166-numeric country code
	 * @return string ISO 3166-alpha3 country code
	 */
	public static function numeric_to_alpha3( $numeric ) {
		return self::alpha2_to_alpha3( self::numeric_to_alpha2( $numeric ) );
	}


	/**
	 * Converts an ISO 3166-numeric country code to a calling code.
	 *
	 * @since 5.4.3
	 *
	 * @param string $numeric ISO 3166-numeric country code
	 * @return string calling code
	 */
	public static function numeric_to_calling_code( $numeric ) {
		return self::alpha2_to_calling_code( self::numeric_to_alpha2( $numeric ) );
	}


	/**
	 * Converts a country calling code to an ISO 3166-alpha2 code.
	 *
	 * @since 5.4.3
	 *
	 * @param string $calling_code country calling code (includes leading '+')
	 * @return string ISO 3166-alpha2 code
	 */
	public static function calling_code_to_alpha2( $calling_code ) {

		$flipped_calling_codes = self::get_flipped_calling_codes();

		return isset( $flipped_calling_codes[ $calling_code ] ) ? $flipped_calling_codes[ $calling_code ] : '';
	}


	/**
	 * Converts a country calling code to an ISO 3166-alpha3 code.
	 *
	 * @since 5.4.3
	 *
	 * @param string $calling_code country calling code (includes leading '+')
	 * @return string ISO 3166-alpha3 code
	 */
	public static function calling_code_to_alpha3( $calling_code ) {

		return self::alpha2_to_alpha3( self::calling_code_to_alpha2( $calling_code ) );
	}


	/**
	 * Converts a country calling code to an ISO 3166-numeric code.
	 *
	 * @since 5.4.3
	 *
	 * @param string $calling_code country calling code (includes leading '+')
	 * @return string ISO 3166-numeric code
	 */
	public static function calling_code_to_numeric( $calling_code ) {

		return self::alpha2_to_numeric( self::calling_code_to_alpha2( $calling_code ) );
	}


	/**
	 * Gets the flipped version of the calling codes array.
	 *
	 * Since array_flip will fail on the calling codes array due to
	 * having some arrays as values, this custom function is necessary.
	 *
	 * @since 5.4.3
	 *
	 * @return array
	 */
	public static function get_flipped_calling_codes() {

		if ( null === self::$flipped_calling_codes ) {

			$flipped_calling_codes = [];

			foreach ( self::$calling_codes as $alpha2 => $calling_code ) {

				if ( is_array( $calling_code ) ) {

					foreach ( $calling_code as $sub_code ) {

						$flipped_calling_codes[ $sub_code ] = $alpha2;
					}
				} else {

					$flipped_calling_codes[ $calling_code ] = $alpha2;
				}
			}

			self::$flipped_calling_codes = $flipped_calling_codes;
		}

		return self::$flipped_calling_codes;
	}


}


endif;

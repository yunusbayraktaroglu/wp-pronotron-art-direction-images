<?php

namespace WPPronotronArtDirectionImages\Utils;

use WPPronotronArtDirectionImages;

/**
 * Class DevelopmentUtils
 * 
 * Helper functions for development purposes
 * @package WPPronotronArtDirectionImages\Utils
 * 
 */
class OptionsUtils {

	private static \Constants $constants;

	/**
	 * Setup plugin constants
	 * @return void
	 */
	public static function setup_constants( \Constants $constants ){
		self::$constants = $constants;
	}

	/**
	 * Returns all plugin constants
	 * @return \Constants
	 */
	public static function get_constants(){
		return self::$constants;
	}

	/**
	 * Returns a single constant
	 * @return string|int|float|array
	 */
	public static function get_constant( string $constant ){
		return self::$constants->$constant;
	}

	/**
	 * Returns all registered images if exist
	 * @return array
	 */
	public static function get_registered_images(){

		/**
		 * $ratio_data = array(
		 * 		'type'			=> $type, // landscape or portrait
		 * 		'id'			=> "{$x}_{$y}",
		 * 		'media_id'		=> "{$x}/{$y}", // to use in "(orientation: portrait) and (max-aspect-ratio: 3/5)"
		 * 		'label' 		=> "{$label_name} {$x}:{$y}", // Human readable label
		 * 		'ratio'			=> array( $x, $y ),
		 * 		'ratio_size'	=> $x / $y,
		 * 		'registered' 	=> array(
		 * 			[ 'name' => 'landscape_5_3_0', 'factor' => 1.0 ] 
		 * 			[ 'name' => 'landscape_5_3_1', 'factor' => 0.55 ] 
		 * 		)
		 * )
		 */

		// $registered_ratios = array( $ratio_data )
		$registered_ratios = get_option( 'wp_pronotron_registered_images' );
		return $registered_ratios;

	}

	/**
	 * Updates registered images
	 * @return array
	 */
	public static function update_registered_images( $value ){
		update_option( 'wp_pronotron_registered_images', $value );
	}

}
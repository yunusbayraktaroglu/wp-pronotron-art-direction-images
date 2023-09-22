<?php

/**
 * Plugin Name:       Pronotron / Art Direction Images
 * Description:       Create & crop images with defined aspect ratios. Get 'picture' output with source's
 * Version:           1.0.0
 * GitHub Plugin URI: https://github.com/yunusbayraktaroglu/wp-pronotron-art-direction-images
 * Author:            Yunus Bayraktaroglu
 * Author URI:        https://pronotron.com/
 * Requires at least: 6.1
 * Requires PHP:      7.4
 * License:           GPL-3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       pronotron
 *
 * @package  WPPronotronArtDirectionImages
 * @dependency - Php Imagick
 * @dependency - GraphQL
 */

if ( ! defined( 'ABSPATH' ) ){
	exit;
}

/** Bootstrap Plugin */
if ( ! class_exists( 'WPPronotronArtDirectionImages' ) ){
	require_once __DIR__ . '/includes/WPPronotronArtDirectionImages.php';
}

/**
 * Instantiates main class
 * @return object
 */
if ( ! function_exists( 'pronotron_art_direction_images_init' ) ){

	function pronotron_art_direction_images_init(){
		return \WPPronotronArtDirectionImages::instance();
	}

}

pronotron_art_direction_images_init();
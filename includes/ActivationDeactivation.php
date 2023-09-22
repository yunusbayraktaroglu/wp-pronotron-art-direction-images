<?php

use WPPronotronArtDirectionImages\Utils\OptionsUtils;
/**
 * Pronotron / Art Direction Images activation - deactivation hooks.
 */
//register_activation_hook( WPPRONOTRON_BASENAME, 'wpp_art_direction_images_activation_callback' );
//register_deactivation_hook( WPPRONOTRON_BASENAME, 'wpp_art_direction_images_deactivation_callback' );

/** On activation */
function wpp_art_direction_images_activation_callback(){

	do_action( 'wp_pronotron_activate' );
	//update_option( 'wp_pronotron_version', WPPRONOTRON_VERSION );

}

/** On deactivation */
function wpp_art_direction_images_deactivation_callback(){

	do_action( 'wp_pronotron_deactivate' );
	//delete_option( 'wp_pronotron_version' );
	//delete_option( 'wp_pronotron_options' );
	//delete_option( 'wp_pronotron_registered_images' );
	
}
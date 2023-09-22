<?php

use WPPronotronArtDirectionImages\Utils\DevelopmentUtils;
use WPPronotronArtDirectionImages\Utils\ImageUtils;
use WPPronotronArtDirectionImages\Utils\OptionsUtils;

/**
 * Prints radio buttons on Crop Ratios screen for each registered image ratio with related image
 * 
 * @param int $post_id Attachment Id
 * @param array $urls {
 * 		'thumbnail' => url,
 * 		'landscape_5_3_1' => url,
 * 		'portrait_3_5_0' => url,
 *  	'portrait_3_5_1' => url,
 * 		'portrait_2_5_0' => url,
 *  	'portrait_2_5_1' => url,
 * }
 * @return html Output.
 */
function wp_pronotron_print_ratio_buttons( $post_id, $urls, $nonce ){

	/**
	 * @var array $registered_images {
	 * 		@var $registered_image {
	 * 			'id' => '5_3',
	 * 			'type' => 'landscape',
	 * 			'label' => 'landscape 5:3',
	 * 			'media_id' => '3/5',
	 *  		'registered' => {
	 * 				[ 'name' => 'landscape_5_3_0', 'factor' => 1.0 ],
	 * 				[ 'name' => 'landscape_5_3_1', 'factor' => 0.5 ],
	 * 				...
	 * 			},
	 * 			'ratio' => [ 5, 3 ],
	 * 			'media' => '(orientation: landscape)'
	 * 		}
	 * }
	 */
	$registered_images = OptionsUtils::get_registered_images();

	if ( ! $registered_images ){
		return new WP_Error( 'no_registered_ratio', __( 'Image ratios have not been registered yet.' ) );
	}

	//DevelopmentUtils::debug_log( $registered_images );

	foreach ( $registered_images as $ratio_data ){

		$label = $ratio_data[ 'label' ];
		$ratio = $ratio_data[ 'ratio' ];

		// Collect variation names of registered image ratio
		$value = esc_html( json_encode( array_column( $ratio_data[ 'registered' ], 'name' ) ) );
		
		// Get smallest ratio variation for display (ordered while registering)
		$smallest_image_index = array_key_last( $ratio_data[ 'registered' ] );
		$image_src = $urls[ $ratio_data[ 'registered' ][ $smallest_image_index ][ 'name' ] ];

		?>
		<span class="imgedit-label" onclick="imageEdit.ySetRatioSelection(<?= "$post_id, '$nonce', $ratio[0], $ratio[1]"; ?>);">
			<input type="radio" value="<?= $value; ?>" id="imgedit-target-<?= $label ?>" name="imgedit-target-<?= $post_id; ?>" />
			<label for="imgedit-target-<?= $label ?>">
				<?php _e( $label ); ?>
				<img src="<?= $image_src ?>">
			</label>
		</span>
		<?php

	}

}

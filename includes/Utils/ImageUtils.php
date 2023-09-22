<?php

namespace WPPronotronArtDirectionImages\Utils;

use WPPronotronArtDirectionImages\Utils\DevelopmentUtils;
use WPPronotronArtDirectionImages\Utils\OptionsUtils;

/**
 * Class ImageUtils
 * 
 * Helper functions for images
 * @package WPPronotronArtDirectionImages\Utils
 * 
 */
class ImageUtils {

    /**
     * Returns art directioned markup image
	 * 
	 * <picture>
	 * 		<source>...</source>
	 * 		<source>...</source>
	 * 		<img>...</img>
	 * </picture> 
     * 
     * @param int $image_id - Attachment id
	 * @param bool $create_webp - Adds webp next to .jpg
	 * @param bool $wrap_figure - Wraps in `<figure>`
	 * @param string $descriptor - "" or "dimension"
     * @return string - tagged image
	 * 
     */
	public static function get_art_direction_image( int $image_id, bool $create_webp = false, bool $wrap_figure = false, string $descriptor = "" ){

		$image_meta = wp_get_attachment_metadata( $image_id );

		/** Image might be deleted */
		if ( ! $image_meta ) return "";

		list( $sources, $base_img ) = self::create_sources( $image_meta, $descriptor );

		// Create <source> tags
		$webp_sources = '';
		$fallback_sources = '';

		foreach( $sources as $source ){

			$default_srcset = $source[ 'srcset' ];
			$webp_srcset = preg_replace( '/.(jpg|jpeg|png|gif)/i', '${0}.webp', $default_srcset );
			$media = $source[ 'media' ];
			$width = $source[ 'dimensions' ][ 0 ];
			$height = $source[ 'dimensions' ][ 1 ];
			
			$sizes = '';

			$fallback_sources .= sprintf(
				'<source srcset="%1$s" media="%2$s" width="%3$s" height="%4$s">',
				esc_attr( $default_srcset ),
				esc_attr( $media ),
				esc_attr( $width ),
				esc_attr( $height )
			);

			if ( $create_webp ){
				$webp_sources .= sprintf(
				   '<source srcset="%1$s" media="%2$s" width="%3$s" height="%4$s" %5$s>',
				   esc_attr( $webp_srcset ),
				   esc_attr( $media ),
				   esc_attr( $width ),
				   esc_attr( $height ),
				   'type="image/webp"'
			   );
		   }

		}

		// Create default <img> tag
        $image_alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );

		$default_image 	= sprintf(
			'<img loading="lazy" decoding="async" src="%1$s" alt="%2$s" width="%3$s" height="%4$s">',
			esc_attr( $base_img[ 'url' ] ),
			esc_attr( $image_alt ),
			esc_attr( $base_img[ 'dimensions' ][ 0 ] ),
			esc_attr( $base_img[ 'dimensions' ][ 1 ] ),
		);

		// Wrap in <picture> tag
		$picture = sprintf(
			'<picture class="art-direction-image">%1$s</picture>',
			$webp_sources . $fallback_sources . $default_image,
		);

		if ( $wrap_figure ){
			$picture = '<figure class="wp-block-image">' . $picture . '</figure>';
		}

		return $picture;
		//DevelopmentUtils::console_log( $base_img );
		//DevelopmentUtils::console_log( $sources );
		//DevelopmentUtils::console_log( $picture );

	}

	/**
	 * Create srcset for all registered custom ratios at once for an image
	 * @see https://web.dev/patterns/web-vitals-patterns/images/responsive-images/demo.html
	 * 
	 * @param array $image_attachment_id
	 * @param string $descriptor - "dimension" | ""
	 * @return array $sources [ 'ratio_id' => $ratio_data, .... ]
	 * @return array $base_img
	 */
	public static function create_sources( array $image_meta, string $descriptor = "" ){

		// Get image base upload url ( .../wp-content/uploads/ )
		$image_baseurl = self::get_img_base_url( $image_meta[ 'file' ] );

		// Get all registered custom ratios
		$registered_ratios = OptionsUtils::get_registered_images();

		// Create all sources by main ratio id's ( 5_2, 3_5, .. )
		$sources = array();

		foreach( $registered_ratios as $registered_ratio ){
			
			$srcset = '';
			$dimensions = array(); // Collect smallest ratio size to use in <source> tag
			$sub_count = count( $registered_ratio[ 'registered' ] );

			// Ratio variations
			// @example: "landscape_5_3_0" | "landscape_5_3_1" ...
			for ( $i = 0; $i < $sub_count; $i ++ ){

				/**
				 * $registered_ratio[ 'registered' ][ $i ]
				 * array(
				 * 		[name] => portrait_3_5_1
				 *  	[factor] => 0.5
				 * )
				 */
				$ratio_id = $registered_ratio[ 'registered' ][ $i ][ 'name' ];

				// Ratio creationg might be skipped for an image if the uploaded image have identical aspect ratio with a 
				// registered ratio. Base image can be used
				$ratio_meta = $image_meta[ 'sizes' ][ $ratio_id ] ?? $image_meta;

				// Can be a density descriptor (3x, 2x, ..) or dimension descriptor (720w, 1200w, ..)
				$desc = "dimension" === $descriptor ? ($ratio_meta['width'] . "w") : ($sub_count - $i . "x");

				// Fill srcset
				$srcset = "{$image_baseurl}{$ratio_meta['file']} {$desc}, " . $srcset;

				// Only collect smallest dimensions px values to use in <source width="" height="">
				if ( ! isset( $registered_ratio[ 'registered' ][ $i + 1 ] ) ){
					$dimensions[ 0 ] = $ratio_meta[ 'width' ];
					$dimensions[ 1 ] = $ratio_meta[ 'height' ];
				}

			}

			$sources[ $registered_ratio[ 'id' ] ] = array(
				'srcset' => rtrim( $srcset, ', ' ),
				'media' => $registered_ratio[ 'media' ],
				'dimensions' => $dimensions
			);

		}

		// Base image for <img> tag
		$base_img = array(
			'url' => "{$image_baseurl}{$image_meta['file']}",
			'dimensions' => array( $image_meta[ 'width' ], $image_meta[ 'height' ] )
		);

		return array( $sources, $base_img );

	}

	/**
	 * Create base url for uploaded img path
	 * @see https://github.com/WordPress/wordpress-develop/blob/6.3/src/wp-includes/media.php#L1280-L1469
	 */
	public static function get_img_base_url( string $full_image_url ){

		$image_basename = wp_basename( $full_image_url );
		$dirname = _wp_get_attachment_relative_path( $full_image_url );

		if ( $dirname ){
			$dirname = trailingslashit( $dirname );
		}

		$upload_dir    = wp_get_upload_dir();
		$image_baseurl = trailingslashit( $upload_dir[ 'baseurl' ] ) . $dirname;

		/*
		* If currently on HTTPS, prefer HTTPS URLs when we know they're supported by the domain
		* (which is to say, when they share the domain name of the current request).
		*/
		if ( 
			is_ssl() && ! str_starts_with( $image_baseurl, 'https' ) && parse_url( $image_baseurl, PHP_URL_HOST ) === $_SERVER['HTTP_HOST'] ){
			$image_baseurl = set_url_scheme( $image_baseurl, 'https' );
		}

		return $image_baseurl;

	}

	/**
	 * If 'only' defined in plugin options, we need to unset our custom image sizes from WP to
	 * avoid cropping them
	 */
	public static function unset_ratios(){

		$registered_ratios = OptionsUtils::get_registered_images();

		foreach( $registered_ratios as $registered_ratio ){

			/** Create ratio variations */
			foreach( $registered_ratio[ 'registered' ] as $ratio_variation ){

				remove_image_size( $ratio_variation[ 'name' ] );

			}

		}

	}

	/**
	 * We are registering custom image sizes to WP without constant px values, to avoid missing custom image sizes 
	 * in every image upload size scenario.
	 * That function recalculates px values of registered image ratios, based on uploaded/given image size.
	 * 
	 * @param array $source_sizes - uploaded/given image sizes
	 * @param int $source_sizes[0] as width
	 * @param int $source_sizes[1] as height
	 */
	public static function recalculate_ratio_px_sizes( array $source_sizes ){

		global $_wp_additional_image_sizes;

		/**
		 * Base ratios
		 * @example
		 * Portrait 2:5, Landscape 5:3, ...
		 */
		$registered_ratios = OptionsUtils::get_registered_images();

		foreach( $registered_ratios as $registered_ratio ){

			$x = $registered_ratio[ 'ratio' ][ 0 ];
			$y = $registered_ratio[ 'ratio' ][ 1 ];

			/**
			 * Calculate maximum dimensions depends on ratio and image source sizes
			 */
			$full_sizes = self::calculate_ratio_full_size( $registered_ratio[ 'type' ], $source_sizes, $x, $y );

			/** Create ratio variations */
			foreach( $registered_ratio[ 'registered' ] as $ratio_variation ){

				$ratio_name = $ratio_variation[ 'name' ];
				$ratio_factor = $ratio_variation[ 'factor' ];

				$_wp_additional_image_sizes[ $ratio_name ] = array(
					'width'  => floor( $full_sizes[ 0 ] * $ratio_factor ),
					'height' => floor( $full_sizes[ 1 ] * $ratio_factor ),
					'crop'   => true,
				);

			}

		}

		// DevelopmentUtils::debug_log( $_wp_additional_image_sizes );
		return $_wp_additional_image_sizes;

	}

	/**
	 * Calculates maximum dimensions of cropped image, 
	 * depends on image source sizes, ratio type and ratio values 
	 * 
	 * @param "landscape" | "portrait" $type Ratio type
	 * @param array $source_sizes Image source dimensions
	 * @param int $x X ratio
	 * @param int $y Y ratio
	 */
	public static function calculate_ratio_full_size( $type, $source_sizes, $x, $y ){

		$full_sizes = null;
		$source_width = $source_sizes[ 'width' ] ?? $source_sizes[ 0 ];
		$source_height = $source_sizes[ 'height' ] ?? $source_sizes[ 1 ];

		/** Width is base in landscape images */
		if ( "landscape" === $type ){
			$full_sizes = array( 
				$source_width, 
				$source_width / $x * $y 
			);
		}

		/** Height is base in portrait images */
		if ( "portrait" === $type ){
			$full_sizes = array( 
				$source_height / $y * $x, 
				$source_height 
			);
		}

		/**
		 * If target ratio height is bigger than source size
		 * Example: 5/3 source image, 5/4 ratio registered
		 */
		if ( $full_sizes[ 1 ] > $source_height ){
			$multiplier = $full_sizes[ 1 ] / $source_height;
			$full_sizes[ 0 ] /= $multiplier;
			$full_sizes[ 1 ] /= $multiplier;
		}

		return $full_sizes;

	}

	/**
     * Get aspect ratio like "19:6", with width and height
     * 
     * @param int $width
     * @param int $height 
     * @return string
     */
    public static function get_aspect_ratio( int $width, int $height ){
        
        $greatestCommonDivisor = static function( $width, $height ) use ( &$greatestCommonDivisor ) {
            return ( $width % $height ) ? $greatestCommonDivisor( $height, $width % $height ) : $height;
        };
    
        $divisor = $greatestCommonDivisor( $width, $height );
    
        return $width / $divisor . ':' . $height / $divisor;

    }

	
}
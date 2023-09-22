<?php

namespace WPPronotronArtDirectionImages\Module;

use WPPronotronArtDirectionImages\Module;
use WPPronotronArtDirectionImages\Utils\ImageUtils;
use WPPronotronArtDirectionImages\Utils\DevelopmentUtils;
use WPPronotronArtDirectionImages\Utils\OptionsUtils;

/**
 * Sample GraphQL query
	query ArtDirectionImage($id: ID!, $idType: MediaItemIdType!) {
		mediaItem(id: $id, idType: $idType) {
			artDirectioned( webp: true, figure: true, descriptor: "dimension" | "" )
		}
	}
 */

/**
 * Module Art Direction Images
 * 
 * - Define ratio based image sizes via admin UI
 * - Adds "Crop Image Ratios" button to default media modal, which opens a ratio modal for cropping
 * - Customize "core/image" gutenberg block to display <picture><source> tagged images
 * 
 * 
 * 
 * @package WPPronotronArtDirectionImages\Module
 */

class ArtDirectionImagesModule extends Module {

	public array $user_options = array();

    public function module_data(): array {
		return [
			'name' 			=> __( 'Art Direction Images', 'pronotron' ),
			'description' 	=> __( 'Define custom images sizes with orientations.', 'pronotron' ),
			'settings' => [
				[
					'id' => 'image_ratios',
					'description' => 'Control image ratios',
					'default' => [
						'landscape_ratios' => [
							[ 'x' => 5, 'y' => 3 ],
							[ 'x' => 5, 'y' => 4 ],
						],
						'portrait_ratios' => [
							[ 'x' => 3, 'y' => 5 ],
						]
					]
				],
				[
					'id' => 'ratio_variations',
					'description' => 'Control how many variations of ratios will be created',
					'default' => null
				],
				[
					'id' => 'upload_sizes',
					'description' => 'Control image upload size',
					'default' => [
						'active' => 0,
						'width' => 2000,
						'height' => 1200,
						'data' => [
							'force' => 0,
							'only' => 0
						]
					]
				]
			]
		];
    }

	public function init_module(): void {

		/**
		 * Extend rest api even without options to avoid GraphQL error
		 */
        $this->extend_rest_api();

		$options = $this->get_module_options();

		/**
		 * Do not continue if options have not been customized yet or image ratios is not defined
		 */
		if ( ! $options || ! isset( $options[ 'image_ratios' ] ) ){
			delete_option( 'wp_pronotron_registered_images' );
			return;
		}

		$this->user_options = $options;

		/**
		 * Instruct WP to delete existing images when a new crop is made.
		 */
		define( 'IMAGE_EDIT_OVERWRITE', true );

		$this->create_all_ratios();

		/**
		 * On image upload hook
		 */
		add_filter( 'wp_handle_upload_prefilter', [ $this, 'on_image_upload' ] ); 


		$this->add_submodule( 'core-image-block-customize' );
		$this->add_submodule( 'core-image-editor-customize' );


		// $registered_images = OptionsUtils::get_registered_images();
		// DevelopmentUtils::console_log( ['all options' => $options] );
		// DevelopmentUtils::console_log( ['all registered images' => $registered_images] );
		// DevelopmentUtils::console_log( wp_get_registered_image_subsizes() );
	}

    /**
     * Rest api extensions of module
	 * artDirectioned( webp: true, figure: false )
     * @return void
     */
    public function extend_rest_api(): void {

        add_action( 'graphql_register_types', function(){

			/** @intelephense-ignore-line */
            register_graphql_field( 'MediaItem', 'artDirectioned', [
                'type' => 'String',
				'args' => [
					'webp' => [ 'type' => 'Boolean' ],
					'figure' => [ 'type' => 'Boolean' ],
					'descriptor' => [ 'type' => 'String' ]
				],
                'description' => __( 'Art directioned media in <source> tag', 'pronotron' ),
                'resolve' => function( $post, $args ){
					$is_webp = $args[ 'webp' ] ?? false;
					$is_figure = $args[ 'figure' ] ?? false;
					$descriptor = $args[ 'descriptor' ] ?? "";
                    return ImageUtils::get_art_direction_image( $post->ID, $is_webp, $is_figure, $descriptor );
                }
            ] );

        });
		
    }

	/**
	 * Register all user defined image ratios to WP
	 */
	public function create_all_ratios(){

		$user_defined_ratios = $this->user_options[ 'image_ratios' ];

		$portrait_ratios = ! isset( $user_defined_ratios[ 'portrait_ratios' ] ) ? [] : $this->register_image_ratios( 
			$user_defined_ratios[ 'portrait_ratios' ], 
			"portrait" 
		);

		$landscape_ratios = ! isset( $user_defined_ratios[ 'landscape_ratios' ] ) ? [] : $this->register_image_ratios( 
			$user_defined_ratios[ 'landscape_ratios' ], 
			"landscape" 
		);

		$registered_all = array_merge( $portrait_ratios, $landscape_ratios );

		OptionsUtils::update_registered_images( $registered_all );

	}

	/**
	 * Register main ratio and variations
	 */
	public function register_image_ratios( $aspect_ratios, $type ){

		$registered_image_sizes = array();

		foreach ( $aspect_ratios as $ratio ){

			$x = $ratio[ 'x' ];
			$y = $ratio[ 'y' ];
			$label_name = ucfirst( $type );
			$base_name = "{$type}_{$x}_{$y}";

			$ratio_data = array(
				'type'			=> $type, // landscape or portrait
				'id'			=> "{$x}_{$y}",
				'media_id'		=> "{$x}/{$y}", // to use in "(orientation: portrait) and (max-aspect-ratio: 3/5)"
				'label' 		=> "{$label_name} {$x}:{$y}", // Human readable label
				'ratio'			=> array( $x, $y ),
				'ratio_size'	=> $x / $y,
				'registered' 	=> array(), // holds base same ratio and variations (mid, small, large ...) as 0, 1, 2, ...
			);

			/**
			 * Register variations of image ratios, 
			 * Default variation (1.0 multiplier) plus user defined ones
			 */
			$variations = array_merge( [ 1.0 ], $this->user_options[ 'ratio_variations' ] ?? [] );

			for ( $i = 0; $i < count( $variations ); $i++ ){

				$ratio_name = "{$base_name}_{$i}";

				/**
				 * Setting hardcoded px sizes of images is unnecesary, 
				 * because some images skips to create ratios if the size is smaller than defined
				 * Image sizes will be recalculated while uploading an image depends on uploaded image size
				 */
				add_image_size( $ratio_name, 1, 1, true );

				/**
				 * Holds base ratio and variations
				 * [ "landscape_3_5_0", "landscape_3_5_1", "landscape_3_5_2", ... ]
				 * [ "portrait_5_2_0", "landscape_5_2_1", ... ]
				 */
				$ratio_data[ 'registered' ][ $i ] = array(
					'name' => $ratio_name,
					'factor' => $variations[ $i ]
				);

			}

			array_push( $registered_image_sizes, $ratio_data );

		}

		/**
		 * - Correct order of ratios by type
		 * - Create media strings for <source> tags
		 */
		if ( "portrait" === $type ){
			usort( $registered_image_sizes, function( $first, $second ){
				return $first[ 'ratio_size' ] > $second[ 'ratio_size' ] ? -1 : 1;
			});

			// Portrait order: max -> min
			$order = array( 'max', 'min' );
			$registered_image_sizes = $this->create_media_strings( $registered_image_sizes, $type, $order );
		}

		if ( "landscape" === $type ){
			usort( $registered_image_sizes, function( $first, $second ){
				return $first[ 'ratio_size' ] < $second[ 'ratio_size' ] ? -1 : 1;
			});

			// Landscape order: min -> max
			$order = array( 'min', 'max' );
			$registered_image_sizes = $this->create_media_strings( $registered_image_sizes, $type, $order );
		}

		return $registered_image_sizes;

	}

	/**
	 * Creates media strings data for `<source (media="...")>` per `<picture>`
	 * 
	 * @example 
	 * (orientation: portrait) and (min-aspect-ratio: 3/5) and (max-aspect-ratio: 3/5)
	 */
	public function create_media_strings( $registered_image_sizes, $type, $order ){

		/** Create media strings */
		for ( $i = 0; $i < count( $registered_image_sizes ); $i++ ){

			$media = "(orientation: {$type})";

			if ( isset( $registered_image_sizes[ $i - 1 ] ) ){
				$media .= " and ({$order[ 0 ]}-aspect-ratio: {$registered_image_sizes[ $i - 1 ]['media_id']})";
			}

			if ( isset( $registered_image_sizes[ $i + 1 ] ) ){
				$media .= " and ({$order[ 1 ]}-aspect-ratio: {$registered_image_sizes[ $i ]['media_id']})";
			}

			$registered_image_sizes[ $i ][ 'media' ] = $media; 

		}

		return $registered_image_sizes;

	}

	/**
	 * On image upload event in WP, we are running couple of functions depends on user defined options
	 * Recalculates ratio px sizes depends on the size of uploaded image 
	 * to be able to create ratios in every scenario
	 * 
	 * @param image $file - Uploaded file
	 * @return image 
	 */
	public function on_image_upload( $file ){
		
		$image_sizes = getimagesize( $file[ 'tmp_name' ] );
		$image_size_options = $this->user_options[ 'upload_sizes' ] ?? false;

		if ( $image_size_options ){

			$filter_width = $image_size_options[ 'width' ];
			$filter_height = $image_size_options[ 'height' ];

			/**
			 * Allow upload of images if only image size matches with defined sizes in plugin options page
			 */
			if ( isset( $image_size_options[ 'data' ][ 'force' ] ) ){

				if ( $image_sizes[ 0 ] != $filter_width || $image_sizes[ 1 ] != $filter_height ){

					$file[ 'error' ] = "Image upload sizes are restricted to w:{$filter_width}px h:{$filter_height}px. But uploaded image sizes are w:{$image_sizes[0]}px h:{$image_sizes[1]}px";

					return $file;

				}
				
			}

			/**
			 * Crop ratios if only image sizes matches with the defined sizes in plugin options page
			 */
			if ( isset( $image_size_options[ 'data' ][ 'only' ] ) ){

				if ( $image_sizes[ 0 ] != $filter_width && $image_sizes[ 1 ] != $filter_height ){

					ImageUtils::unset_ratios();
					return $file;
				
				}
				
			}

		}

		ImageUtils::recalculate_ratio_px_sizes( $image_sizes );

		/**
		 * Doesnt work because of that line
		 * @see https://github.com/WordPress/wordpress-develop/blob/6.3/src/wp-includes/class-wp-image-editor-imagick.php#L558
		 */
		//add_filter( 'wp_image_resize_identical_dimensions', true, $image_sizes[ 0 ], $image_sizes[ 1 ] );
		//add_filter( 'wp_image_resize_identical_dimensions', true, 2000, 1200 );

		return $file;

	}

}

return new ArtDirectionImagesModule();
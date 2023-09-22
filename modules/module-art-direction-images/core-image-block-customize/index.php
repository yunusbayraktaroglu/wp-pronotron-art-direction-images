<?php

namespace WPPronotronArtDirectionImages\Module\ArtDirectionImagesModule;

use WPPronotronArtDirectionImages\Utils\DevelopmentUtils;
use WPPronotronArtDirectionImages\Utils\DependencyUtils;
use WPPronotronArtDirectionImages\Utils\ImageUtils;

/**
 * Submodule CustomizeCoreImageBlock
 * 
 * - Displays a button for 'core/image' block in editor 
 * 	 to convert image to art-directioned if image have multiple orientations
 * 
 * - Change 'core/image' blocks render output if art-directioned
 * 
 * @package WPPronotronArtDirectionImages\Module\ArtDirectionImagesModule
 * 
 */
class CustomizeCoreImageBlock {

	public string $submodule_id;
	public string $submodule_dir;
	public string $submodule_url;

	public function init(): void {

		$submodule_id 	= $this->submodule_id;
		$submodule_dir 	= $this->submodule_dir;
		$submodule_url 	= $this->submodule_url;

		add_action( 'enqueue_block_assets', function() use( $submodule_id, $submodule_dir, $submodule_url ){
			DependencyUtils::load_dependency( $submodule_id, $submodule_dir, $submodule_url );
		});

		add_action( 'wp_enqueue_scripts', function() use( $submodule_id, $submodule_dir, $submodule_url ){
			DependencyUtils::load_front_end_dependency( $submodule_id, $submodule_dir, $submodule_url );
		});

        add_action( 'register_block_type_args', [ $this, 'core_image_block_type_args' ], 10, 3 );
	}

    /**
     * Modify core image block render callback
     */
    public function core_image_block_type_args( $args, $name ){

        if ( 'core/image' === $name ) {
            $args[ 'render_callback' ] = [ $this, 'modify_core_image_output' ]; 
        }

        return $args;
    }

	/**
	 * Modify output of "core/image" block
	 * Art-directioned images have "isFluid" attribute
	 * 
	 * @return $content
	 */
    public function modify_core_image_output( $attributes, $content ) {

		/**
		 * Expected attributes
		 */
		// (
		// 	[id] => 16
		// 	[sizeSlug] => full
		// 	[linkDestination] => none
		// 	[isFluid] => 1
		// 	[alt] => 
		// )
		// DevelopmentUtils::debug_log( $attributes );

		if ( isset( $attributes[ 'isFluid' ] ) ){

			$is_webp = true;
			$is_figure = true;
			$descriptor = ""; // "dimension" | "" (for density)

			$content = ImageUtils::get_art_direction_image( $attributes[ 'id' ], $is_webp, $is_figure, $descriptor );
			
			//$meta = wp_get_attachment_metadata( $attributes[ 'id' ] );
			//DevelopmentUtils::debug_log( $meta );
		}

		return $content;
    }
}

return new CustomizeCoreImageBlock();

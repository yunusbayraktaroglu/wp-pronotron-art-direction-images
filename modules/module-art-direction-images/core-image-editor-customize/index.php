<?php

namespace WPPronotronArtDirectionImages\Module\ArtDirectionImagesModule;

use WPPronotronArtDirectionImages\Utils\DependencyUtils;

/**
 * Submodule CustomizeImageEditor
 *
 * - Adds "Crop Image Ratios" button to open ratio editor screen
 * 
 * @package WPPronotronArtDirectionImages\Module\ArtDirectionImagesModule
 *
 */
class CustomizeImageEditor {

	public string $submodule_id;
	public string $submodule_dir;
	public string $submodule_url;

	public function init(): void {

		$submodule_id 	= $this->submodule_id;
		$submodule_dir 	= $this->submodule_dir;
		$submodule_url 	= $this->submodule_url;

		add_action( 'wp_enqueue_media', function() use( $submodule_id, $submodule_dir, $submodule_url ){
			DependencyUtils::load_dependency( $submodule_id, $submodule_dir, $submodule_url );
		});

		/**
		 * Create ajax handled function
		 * Called by javascript "Crop Image Ratios" button on media popup with ajax
		 */
        add_action( 'wp_ajax_pronotron_image_editor', [ $this, 'pronotron_image_editor' ] );
	}

    /**
     * Replacement for default wp_ajax_image_editor();
	 * Called by ajax global imageEdit js library 
     *
     * This function called by wordpress ajax request to open default image editor
     * Able image edit page to load our image-edit.php template
     * @see https://developer.wordpress.org/reference/functions/wp_ajax_image_editor/
     *
     */
    public function pronotron_image_editor(){

        /** Debug if opened with this function */
        // error_log( print_r( array($attachment_id, "PRONOTRON IMAGE EDITOR"), true) );

        $attachment_id = (int) $_POST['postid'];

        if ( empty( $attachment_id ) || ! current_user_can( 'edit_post', $attachment_id ) ) {
            wp_die( -1 );
        }

        check_ajax_referer( "image_editor-$attachment_id" );
        include_once __DIR__ . '/php/image-edit.php';

        $msg = false;

        switch ( $_POST['do'] ){
            case 'save':
                $msg = wp_save_image( $attachment_id );
                if ( ! empty( $msg->error ) ) {
                    wp_send_json_error( $msg );
                }

                wp_send_json_success( $msg );
                break;
            case 'scale':
                $msg = wp_save_image( $attachment_id );
                break;
            case 'restore':
                $msg = wp_restore_image( $attachment_id );
                break;
        }

        ob_start();
        wp_image_editor( $attachment_id, $msg );
        $html = ob_get_clean();

        if ( ! empty( $msg->error ) ) {
            wp_send_json_error(
                array(
                    'message' => $msg,
                    'html'    => $html,
                )
            );
        }

        wp_send_json_success(
            array(
                'message' => $msg,
                'html'    => $html,
            )
        );
    }

}

return new CustomizeImageEditor();
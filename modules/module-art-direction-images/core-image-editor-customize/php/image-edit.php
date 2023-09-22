<?php
/**
 * WordPress Image Editor
 *
 * @package WordPress
 * @subpackage Administration
 * @see https://github.com/WordPress/wordpress-develop/blob/trunk/src/wp-admin/includes/image-edit.php
 * 
 * Changed part mentioned with @edited
 * @fix
 * - After ratio crop, hide second crop with cropped image
 * 
 */

use WPPronotronArtDirectionImages\Utils\DevelopmentUtils;
use WPPronotronArtDirectionImages\Utils\ImageUtils;

/**
 * @return
 * All user registered image ratios with radio buttons
 */
require_once __DIR__ . '/image-edit-layout.php';

/**
 * Loads the WP image-editing interface.
 *
 * @since 2.9.0
 *
 * @param int          $post_id Attachment post ID.
 * @param false|object $msg     Optional. Message to display for image editor updates or errors.
 *                              Default false.
 */
function wp_image_editor( $post_id, $msg = false ){

	$nonce     = wp_create_nonce( "image_editor-$post_id" );
	$meta      = wp_get_attachment_metadata( $post_id );
	$sub_sizes = isset( $meta['sizes'] ) && is_array( $meta['sizes'] );
	$note      = '';

	if ( isset( $meta['width'], $meta['height'] ) ) {
		$big = max( $meta['width'], $meta['height'] );
	} else {
		die( __( 'Image data does not exist. Please re-upload the image.' ) );
	}

	$sizer = $big > 600 ? 600 / $big : 1;

	$backup_sizes = get_post_meta( $post_id, '_wp_attachment_backup_sizes', true );
	$can_restore  = false;
	if ( ! empty( $backup_sizes ) && isset( $backup_sizes['full-orig'], $meta['file'] ) ) {
		$can_restore = wp_basename( $meta['file'] ) !== $backup_sizes['full-orig']['file'];
	}

	if ( $msg ) {
		if ( isset( $msg->error ) ) {
			$note = "<div class='notice notice-error' tabindex='-1' role='alert'><p>$msg->error</p></div>";
		} elseif ( isset( $msg->msg ) ) {
			$note = "<div class='notice notice-success' tabindex='-1' role='alert'><p>$msg->msg</p></div>";
		}
	}

	/**
	 * Shows the settings in the Image Editor that allow selecting to edit only the thumbnail of an image.
	 *
	 * @since 6.3.0
	 *
	 * @param bool $show Whether to show the settings in the Image Editor. Default false.
	 */
	$edit_thumbnails_separately = (bool) apply_filters( 'image_edit_thumbnails_separately', false );

	
	/**
	 * Pass ratio variation urls to layout.php
	 * 
	 * @package Pronotron
	 */
	$prepared = [];
	$baze_url = ImageUtils::get_img_base_url( $meta[ 'file' ] );

	foreach( $meta[ 'sizes' ] as $ratio_id => $ratio_info ){
		$prepared[ $ratio_id ] = $baze_url . $ratio_info[ 'file' ];
	}

	// DevelopmentUtils::debug_log( $meta );

	?>
<div class="imgedit-wrap wp-clearfix">
	<div id="imgedit-panel-<?php echo $post_id; ?>">

		<?php echo $note; ?>

		<div class="imgedit-panel-content imgedit-panel-tools wp-clearfix">
			<div class="imgedit-menu wp-clearfix">
				<button type="button" onclick="imageEdit.toggleCropTool( <?php echo "$post_id, '$nonce'"; ?>, this );"
					aria-expanded="false" aria-controls="imgedit-crop" class="imgedit-crop button disabled"
					disabled><?php esc_html_e( 'Crop' ); ?></button>
			</div>
			<div class="imgedit-submit imgedit-menu">
				<button type="button" id="image-undo-<?php echo $post_id; ?>"
					onclick="imageEdit.undo(<?php echo "$post_id, '$nonce'"; ?>, this)"
					class="imgedit-undo button disabled" disabled><?php esc_html_e( 'Undo' ); ?></button>
				<button type="button" id="image-redo-<?php echo $post_id; ?>"
					onclick="imageEdit.redo(<?php echo "$post_id, '$nonce'"; ?>, this)"
					class="imgedit-redo button disabled" disabled><?php esc_html_e( 'Redo' ); ?></button>
				<button type="button" onclick="imageEdit.close(<?php echo $post_id; ?>, 1)"
					class="button imgedit-cancel-btn"><?php esc_html_e( 'Cancel Editing' ); ?></button>
				<button type="button" onclick="imageEdit.saveTest(<?php echo "$post_id, '$nonce'"; ?>)" disabled="disabled"
					class="button button-primary imgedit-submit-btn"><?php esc_html_e( 'Save Edits' ); ?></button>
			</div>
		</div>


		<div class="imgedit-panel-content wp-clearfix">

			<div class="imgedit-tools">
				<input type="hidden" id="imgedit-nonce-<?php echo $post_id; ?>" value="<?php echo $nonce; ?>" />
				<input type="hidden" id="imgedit-sizer-<?php echo $post_id; ?>" value="<?php echo $sizer; ?>" />
				<input type="hidden" id="imgedit-history-<?php echo $post_id; ?>" value="" />
				<input type="hidden" id="imgedit-undone-<?php echo $post_id; ?>" value="0" />
				<input type="hidden" id="imgedit-selection-<?php echo $post_id; ?>" value="" />
				<input type="hidden" id="imgedit-x-<?php echo $post_id; ?>"
					value="<?php echo isset( $meta['width'] ) ? $meta['width'] : 0; ?>" />
				<input type="hidden" id="imgedit-y-<?php echo $post_id; ?>"
					value="<?php echo isset( $meta['height'] ) ? $meta['height'] : 0; ?>" />
				<div id="imgedit-crop-<?php echo $post_id; ?>" class="imgedit-crop-wrap">
					<div class="imgedit-crop-grid"></div>
					<img id="image-preview-<?php echo $post_id; ?>"
						onload="imageEdit.imgLoaded('<?php echo $post_id; ?>')"
						src="<?php echo esc_url( admin_url( 'admin-ajax.php', 'relative' ) ) . '?action=imgedit-preview&amp;_ajax_nonce=' . $nonce . '&amp;postid=' . $post_id . '&amp;rand=' . rand( 1, 99999 ); ?>"
						alt="" />
				</div>
			</div>

			<div class="imgedit-settings">
				<div class="imgedit-tool-active">
					
					<?php if ( $can_restore ) { ?>
						<!-- Restoration -->
						<div class="imgedit-group">
							<div class="imgedit-group-top">
								<h2><button type="button" onclick="imageEdit.toggleHelp(this);" class="button-link"
										aria-expanded="false"><?php _e( 'Restore original image' ); ?> <span
											class="dashicons dashicons-arrow-down imgedit-help-toggle"></span></button></h2>
								<div class="imgedit-help imgedit-restore">
									<p>
										<?php
											_e( 'Discard any changes and restore the original image.' );
											if ( ! defined( 'IMAGE_EDIT_OVERWRITE' ) || ! IMAGE_EDIT_OVERWRITE ) {
												echo ' ' . __( 'Previously edited copies of the image will not be deleted.' );
											}
										?>
									</p>
									<div class="imgedit-submit">
										<input type="button"
											onclick="imageEdit.action(<?php echo "$post_id, '$nonce'"; ?>, 'restore')"
											class="button button-primary" value="<?php esc_attr_e( 'Restore image' ); ?>"
											<?php echo $can_restore; ?> />
									</div>
								</div>
							</div>
						</div>
					<?php } ?>

					<!-- Crop helper panel -->
					<div class="imgedit-group">
						<div id="imgedit-crop" tabindex="-1" class="imgedit-group-controls">

							<fieldset class="imgedit-crop-ratio" style="display:none;">
								<legend><?php _e( 'Aspect ratio:' ); ?></legend>
								<div class="nowrap">
									<label for="imgedit-crop-width-<?php echo $post_id; ?>" class="screen-reader-text">
										<?php
											/* translators: Hidden accessibility text. */
											_e( 'crop ratio width' );
										?>
									</label>
									<input type="number" step="1" min="1"
										id="imgedit-crop-width-<?php echo $post_id; ?>"
										onkeyup="imageEdit.setRatioSelection(<?php echo $post_id; ?>, 0, this)"
										onblur="imageEdit.setRatioSelection(<?php echo $post_id; ?>, 0, this)" />
									<span class="imgedit-separator" aria-hidden="true">:</span>
									<label for="imgedit-crop-height-<?php echo $post_id; ?>" class="screen-reader-text">
										<?php
											/* translators: Hidden accessibility text. */
											_e( 'crop ratio height' );
										?>
									</label>
									<input type="number" step="1" min="0"
										id="imgedit-crop-height-<?php echo $post_id; ?>"
										onkeyup="imageEdit.setRatioSelection(<?php echo $post_id; ?>, 1, this)"
										onblur="imageEdit.setRatioSelection(<?php echo $post_id; ?>, 1, this)" />
								</div>
							</fieldset>
							<fieldset id="imgedit-crop-sel-<?php echo $post_id; ?>" class="imgedit-crop-sel" style="display:none;">
								<legend><?php _e( 'Selection:' ); ?></legend>
								<div class="nowrap">
									<label for="imgedit-sel-width-<?php echo $post_id; ?>" class="screen-reader-text">
										<?php
											/* translators: Hidden accessibility text. */
											_e( 'selection width' );
										?>
									</label>
									<input type="number" step="1" min="0" id="imgedit-sel-width-<?php echo $post_id; ?>"
										onkeyup="imageEdit.setNumSelection(<?php echo $post_id; ?>, this)"
										onblur="imageEdit.setNumSelection(<?php echo $post_id; ?>, this)" />
									<span class="imgedit-separator" aria-hidden="true">&times;</span>
									<label for="imgedit-sel-height-<?php echo $post_id; ?>" class="screen-reader-text">
										<?php
											/* translators: Hidden accessibility text. */
											_e( 'selection height' );
										?>
									</label>
									<input type="number" step="1" min="0"
										id="imgedit-sel-height-<?php echo $post_id; ?>"
										onkeyup="imageEdit.setNumSelection(<?php echo $post_id; ?>, this)"
										onblur="imageEdit.setNumSelection(<?php echo $post_id; ?>, this)" />
								</div>
							</fieldset>
							<fieldset id="imgedit-crop-sel-<?php echo $post_id; ?>" class="imgedit-crop-sel">
								<legend><?php _e( 'Starting Coordinates:' ); ?></legend>
								<div class="nowrap">
									<label for="imgedit-start-x-<?php echo $post_id; ?>" class="screen-reader-text">
										<?php
											/* translators: Hidden accessibility text. */
											_e( 'horizontal start position' );
										?>
									</label>
									<input type="number" step="1" min="0" id="imgedit-start-x-<?php echo $post_id; ?>"
										onkeyup="imageEdit.setNumSelection(<?php echo $post_id; ?>, this)"
										onblur="imageEdit.setNumSelection(<?php echo $post_id; ?>, this)" value="0" />
									<span class="imgedit-separator" aria-hidden="true">&times;</span>
									<label for="imgedit-start-y-<?php echo $post_id; ?>" class="screen-reader-text">
										<?php
											/* translators: Hidden accessibility text. */
											_e( 'vertical start position' );
										?>
									</label>
									<input type="number" step="1" min="0" id="imgedit-start-y-<?php echo $post_id; ?>"
										onkeyup="imageEdit.setNumSelection(<?php echo $post_id; ?>, this)"
										onblur="imageEdit.setNumSelection(<?php echo $post_id; ?>, this)" value="0" />
								</div>
							</fieldset>
							<div class="imgedit-crop-apply imgedit-menu container">
								<button class="button-primary" type="button"
									onclick="imageEdit.handleCropToolClick( <?php echo "$post_id, '$nonce'"; ?>, this );"
									class="imgedit-crop-apply button"><?php esc_html_e( 'Apply Crop' ); ?></button>
								<button type="button"
									onclick="imageEdit.handleCropToolClick( <?php echo "$post_id, '$nonce'"; ?>, this );"
									class="imgedit-crop-clear button"
									disabled="disabled"><?php esc_html_e( 'Clear Crop' ); ?></button>
							</div>
						</div>
					</div>
				</div>


				<?php if ( $sub_sizes ) { ?>
					<div class="imgedit-group imgedit-applyto">
						
						<div class="imgedit-thumbnail-preview-group">
							<div id="imgedit-save-target-<?php echo $post_id; ?>" class="imgedit-save-target">
								<fieldset>
									<legend><?php _e( 'Apply changes to:' ); ?></legend>
									<?php wp_pronotron_print_ratio_buttons( $post_id, $prepared, $nonce ); ?>
								</fieldset>
							</div>
						</div>

					</div>
				<?php } ?>

			</div> <!-- end of .imgedit-settings -->
			
		</div> <!-- end of .imgedit-panel-content -->



	</div>

	<div class="imgedit-wait" id="imgedit-wait-<?php echo $post_id; ?>"></div>
	<div class="hidden" id="imgedit-leaving-<?php echo $post_id; ?>">
		<?php _e( "There are unsaved changes that will be lost. 'OK' to continue, 'Cancel' to return to the Image Editor." ); ?>
	</div>

</div>
<?php
}




/**
 * Streams image in WP_Image_Editor to browser.
 *
 * @since 2.9.0
 *
 * @param WP_Image_Editor $image         The image editor instance.
 * @param string          $mime_type     The mime type of the image.
 * @param int             $attachment_id The image's attachment post ID.
 * @return bool True on success, false on failure.
 */
function wp_stream_image( $image, $mime_type, $attachment_id ){

	DevelopmentUtils::debug_log( 'STREAM IMAGE' );

	if ( $image instanceof WP_Image_Editor ) {

		/**
		 * Filters the WP_Image_Editor instance for the image to be streamed to the browser.
		 *
		 * @since 3.5.0
		 *
		 * @param WP_Image_Editor $image         The image editor instance.
		 * @param int             $attachment_id The attachment post ID.
		 */
		$image = apply_filters( 'image_editor_save_pre', $image, $attachment_id );

		if ( is_wp_error( $image->stream( $mime_type ) ) ) {
			return false;
		}

		return true;
	} else {
		/* translators: 1: $image, 2: WP_Image_Editor */
		_deprecated_argument( __FUNCTION__, '3.5.0', sprintf( __( '%1$s needs to be a %2$s object.' ), '$image', 'WP_Image_Editor' ) );

		/**
		 * Filters the GD image resource to be streamed to the browser.
		 *
		 * @since 2.9.0
		 * @deprecated 3.5.0 Use {@see 'image_editor_save_pre'} instead.
		 *
		 * @param resource|GdImage $image         Image resource to be streamed.
		 * @param int              $attachment_id The attachment post ID.
		 */
		$image = apply_filters_deprecated( 'image_save_pre', array( $image, $attachment_id ), '3.5.0', 'image_editor_save_pre' );

		switch ( $mime_type ) {
			case 'image/jpeg':
				header( 'Content-Type: image/jpeg' );
				return imagejpeg( $image, null, 90 );
			case 'image/png':
				header( 'Content-Type: image/png' );
				return imagepng( $image );
			case 'image/gif':
				header( 'Content-Type: image/gif' );
				return imagegif( $image );
			case 'image/webp':
				if ( function_exists( 'imagewebp' ) ) {
					header( 'Content-Type: image/webp' );
					return imagewebp( $image, null, 90 );
				}
				return false;
			default:
				return false;
		}
	}
}

/**
 * Saves image to file.
 *
 * @since 2.9.0
 * @since 3.5.0 The `$image` parameter expects a `WP_Image_Editor` instance.
 * @since 6.0.0 The `$filesize` value was added to the returned array.
 *
 * @param string          $filename  Name of the file to be saved.
 * @param WP_Image_Editor $image     The image editor instance.
 * @param string          $mime_type The mime type of the image.
 * @param int             $post_id   Attachment post ID.
 * @return array|WP_Error|bool {
 *     Array on success or WP_Error if the file failed to save.
 *     When called with a deprecated value for the `$image` parameter,
 *     i.e. a non-`WP_Image_Editor` image resource or `GdImage` instance,
 *     the function will return true on success, false on failure.
 *
 *     @type string $path      Path to the image file.
 *     @type string $file      Name of the image file.
 *     @type int    $width     Image width.
 *     @type int    $height    Image height.
 *     @type string $mime-type The mime type of the image.
 *     @type int    $filesize  File size of the image.
 * }
 */
function wp_save_image_file( $filename, $image, $mime_type, $post_id ){

	// DevelopmentUtils::debug_log( 'SAVE IMAGE FILE' );

	if ( $image instanceof WP_Image_Editor ) {

		/** This filter is documented in wp-admin/includes/image-edit.php */
		$image = apply_filters( 'image_editor_save_pre', $image, $post_id );

		/**
		 * Filters whether to skip saving the image file.
		 *
		 * Returning a non-null value will short-circuit the save method,
		 * returning that value instead.
		 *
		 * @since 3.5.0
		 *
		 * @param bool|null       $override  Value to return instead of saving. Default null.
		 * @param string          $filename  Name of the file to be saved.
		 * @param WP_Image_Editor $image     The image editor instance.
		 * @param string          $mime_type The mime type of the image.
		 * @param int             $post_id   Attachment post ID.
		 */
		$saved = apply_filters( 'wp_save_image_editor_file', null, $filename, $image, $mime_type, $post_id );

		if ( null !== $saved ) {
			return $saved;
		}

		return $image->save( $filename, $mime_type );
	} else {
		/* translators: 1: $image, 2: WP_Image_Editor */
		_deprecated_argument( __FUNCTION__, '3.5.0', sprintf( __( '%1$s needs to be a %2$s object.' ), '$image', 'WP_Image_Editor' ) );

		/** This filter is documented in wp-admin/includes/image-edit.php */
		$image = apply_filters_deprecated( 'image_save_pre', array( $image, $post_id ), '3.5.0', 'image_editor_save_pre' );

		/**
		 * Filters whether to skip saving the image file.
		 *
		 * Returning a non-null value will short-circuit the save method,
		 * returning that value instead.
		 *
		 * @since 2.9.0
		 * @deprecated 3.5.0 Use {@see 'wp_save_image_editor_file'} instead.
		 *
		 * @param bool|null        $override  Value to return instead of saving. Default null.
		 * @param string           $filename  Name of the file to be saved.
		 * @param resource|GdImage $image     Image resource or GdImage instance.
		 * @param string           $mime_type The mime type of the image.
		 * @param int              $post_id   Attachment post ID.
		 */
		$saved = apply_filters_deprecated(
			'wp_save_image_file',
			array( null, $filename, $image, $mime_type, $post_id ),
			'3.5.0',
			'wp_save_image_editor_file'
		);

		if ( null !== $saved ) {
			return $saved;
		}

		switch ( $mime_type ) {
			case 'image/jpeg':
				/** This filter is documented in wp-includes/class-wp-image-editor.php */
				return imagejpeg( $image, $filename, apply_filters( 'jpeg_quality', 90, 'edit_image' ) );
			case 'image/png':
				return imagepng( $image, $filename );
			case 'image/gif':
				return imagegif( $image, $filename );
			case 'image/webp':
				if ( function_exists( 'imagewebp' ) ) {
					return imagewebp( $image, $filename );
				}
				return false;
			default:
				return false;
		}
	}
}

/**
 * Image preview ratio. Internal use only.
 *
 * @since 2.9.0
 *
 * @ignore
 * @param int $w Image width in pixels.
 * @param int $h Image height in pixels.
 * @return float|int Image preview ratio.
 */
function _image_get_preview_ratio( $w, $h ){
	$max = max( $w, $h );
	return $max > 600 ? ( 600 / $max ) : 1;
}





/**
 * Streams image in post to browser, along with enqueued changes
 * in `$_REQUEST['history']`.
 *
 * @since 2.9.0
 *
 * @param int $post_id Attachment post ID.
 * @return bool True on success, false on failure.
 */
function stream_preview_image( $post_id ) {

	DevelopmentUtils::debug_log( "STREAM PREVIEW IMAGE" );

	$post = get_post( $post_id );

	wp_raise_memory_limit( 'admin' );

	$img = wp_get_image_editor( _load_image_to_edit_path( $post_id ) );

	if ( is_wp_error( $img ) ) {
		return false;
	}

	$changes = ! empty( $_REQUEST['history'] ) ? json_decode( wp_unslash( $_REQUEST['history'] ) ) : null;
	if ( $changes ) {
		$img = image_edit_apply_changes( $img, $changes );
	}

	// Scale the image.
	$size = $img->get_size();
	$w    = $size['width'];
	$h    = $size['height'];

	$ratio = _image_get_preview_ratio( $w, $h );
	$w2    = max( 1, $w * $ratio );
	$h2    = max( 1, $h * $ratio );

	if ( is_wp_error( $img->resize( $w2, $h2 ) ) ) {
		return false;
	}

	return wp_stream_image( $img, $post->post_mime_type, $post_id );
}

/**
 * Restores the metadata for a given attachment.
 *
 * @since 2.9.0
 *
 * @param int $post_id Attachment post ID.
 * @return stdClass Image restoration message object.
 */
function wp_restore_image( $post_id ) {
	$meta             = wp_get_attachment_metadata( $post_id );
	$file             = get_attached_file( $post_id );
	$backup_sizes     = get_post_meta( $post_id, '_wp_attachment_backup_sizes', true );
	$old_backup_sizes = $backup_sizes;
	$restored         = false;
	$msg              = new stdClass();

	if ( ! is_array( $backup_sizes ) ) {
		$msg->error = __( 'Cannot load image metadata.' );
		return $msg;
	}

	$parts         = pathinfo( $file );
	$suffix        = time() . rand( 100, 999 );
	$default_sizes = get_intermediate_image_sizes();

	if ( isset( $backup_sizes['full-orig'] ) && is_array( $backup_sizes['full-orig'] ) ) {
		$data = $backup_sizes['full-orig'];

		if ( $parts['basename'] != $data['file'] ) {
			if ( defined( 'IMAGE_EDIT_OVERWRITE' ) && IMAGE_EDIT_OVERWRITE ) {

				// Delete only if it's an edited image.
				if ( preg_match( '/-e[0-9]{13}\./', $parts['basename'] ) ) {
					wp_delete_file( $file );
				}
			} elseif ( isset( $meta['width'], $meta['height'] ) ) {
				$backup_sizes[ "full-$suffix" ] = array(
					'width'  => $meta['width'],
					'height' => $meta['height'],
					'file'   => $parts['basename'],
				);
			}
		}

		$restored_file = path_join( $parts['dirname'], $data['file'] );
		$restored      = update_attached_file( $post_id, $restored_file );

		$meta['file']   = _wp_relative_upload_path( $restored_file );
		$meta['width']  = $data['width'];
		$meta['height'] = $data['height'];
	}

	foreach ( $default_sizes as $default_size ) {
		if ( isset( $backup_sizes[ "$default_size-orig" ] ) ) {
			$data = $backup_sizes[ "$default_size-orig" ];
			if ( isset( $meta['sizes'][ $default_size ] ) && $meta['sizes'][ $default_size ]['file'] != $data['file'] ) {
				if ( defined( 'IMAGE_EDIT_OVERWRITE' ) && IMAGE_EDIT_OVERWRITE ) {

					// Delete only if it's an edited image.
					if ( preg_match( '/-e[0-9]{13}-/', $meta['sizes'][ $default_size ]['file'] ) ) {
						$delete_file = path_join( $parts['dirname'], $meta['sizes'][ $default_size ]['file'] );
						wp_delete_file( $delete_file );
					}
				} else {
					$backup_sizes[ "$default_size-{$suffix}" ] = $meta['sizes'][ $default_size ];
				}
			}

			$meta['sizes'][ $default_size ] = $data;
		} else {
			unset( $meta['sizes'][ $default_size ] );
		}
	}

	if ( ! wp_update_attachment_metadata( $post_id, $meta ) ||
		( $old_backup_sizes !== $backup_sizes && ! update_post_meta( $post_id, '_wp_attachment_backup_sizes', $backup_sizes ) ) ) {

		$msg->error = __( 'Cannot save image metadata.' );
		return $msg;
	}

	if ( ! $restored ) {
		$msg->error = __( 'Image metadata is inconsistent.' );
	} else {
		$msg->msg = __( 'Image restored successfully.' );
		if ( defined( 'IMAGE_EDIT_OVERWRITE' ) && IMAGE_EDIT_OVERWRITE ) {
			delete_post_meta( $post_id, '_wp_attachment_backup_sizes' );
		}
	}

	return $msg;
}







/**
 * Performs full-size cropping based on selection and max-size calculation, then returns
 * image to make sub sizes.
 * 
 * @param WP_Image_Editor $image   WP_Image_Editor instance.
 * @param array           $changes Array of change operations.
 * @param string          $target Target ratio as string ( portrait_3_5_0 ).
 * 
 * @return WP_Image_Editor WP_Image_Editor instance with changes applied.
 */
function image_edit_apply_changes( $image, $changes, $ratio_type, $ratio_x, $ratio_y ){

	if ( ! is_array( $changes ) ){
		return $image;
	}

	// Expand change operations.
	foreach ( $changes as $key => $obj ){
		if ( isset( $obj->c ) ){
			$obj->type = 'crop';
			$obj->selection = $obj->c;
			unset( $obj->c );
		}
		$changes[ $key ] = $obj;
	}

	/** 
	 * WP has constant 600px preview image in editor screen. Then, it is recalculating crop selection sizes, 
	 * based on ratio between 600px and image source size.
	 * 
	 * That causes some problems which avoids cropping (like both width and height are bigger than crop sizes), 
	 * while our exact px size calculation of ratio is different from that.
	 */

	/**
	 * Calculate maximum size of ratio depends on source sizes
	 */
	$source_size = $image->get_size();
	$cropped_size = ImageUtils::calculate_ratio_full_size( $ratio_type, $source_size, $ratio_x, $ratio_y );

	/**
	 * Recalculate X, Y coordinates of selection, 600px to image source size and safe it to avoid problems.
	 * (see the note above)
	 */
	$selection = $changes[ 0 ]->selection;
	$scale = 1 / _image_get_preview_ratio( $source_size[ 'width' ], $source_size[ 'height' ] );

	$x_coord = $selection->x * $scale;
	$y_coord = $selection->y * $scale;
	$x_over = ( $x_coord + $cropped_size[ 0 ] ) - $source_size[ 'width' ];
	$y_over = ( $y_coord + $cropped_size[ 1 ] ) - $source_size[ 'height' ];

	if ( $x_over > 0 ){
		$x_coord -= $x_over; // move selection to left
	}
	if ( $y_over > 0 ){
		$y_coord -= $y_over; // move selection to top
	}

	/**
	 * Crop the image
	 */
	$image->crop( 
		floor( $x_coord ), 
		floor( $y_coord ), 
		floor( $cropped_size[ 0 ] ), 
		floor( $cropped_size[ 1 ] )
	);

	// DevelopmentUtils::debug_log([
	// 	'image_edit_apply_changes' => 1,
	// 	'default size' => $source_size,
	// 	'cropped size' => $image->get_size(),
	// ]);

	return [ 
		$image, 
		array( floor( $x_coord ), floor( $y_coord ) ) 
	];
	
}



/**
 * Saves image to post, along with enqueued changes
 * in `$_REQUEST['history']`.
 *
 * @since 2.9.0
 *
 * @param int $post_id Attachment post ID.
 * @return stdClass
 */
function wp_save_image( $post_id ) {

	// Sample $_REQUEST
	// (
	// 	[action] => pronotron_image_editor
	// 	[_ajax_nonce] => 3bc87492f0
	// 	[postid] => 160
	// 	[history] => [{\"c\":{\"x\":216,\"y\":0,\"w\":144,\"h\":240}}]
	// 	[target] => [\"portrait_3_5_0\",\"portrait_3_5_1\", ...]
	// 	[context] => 
	// 	[do] => save (save, scale, restore)
	// )
	//DevelopmentUtils::debug_log( $_REQUEST );


	$_wp_additional_image_sizes = wp_get_additional_image_sizes();
	$post    = get_post( $post_id );

	$return  = new stdClass();
	$success = false;
	$delete  = false;
	$nocrop  = false;


	/**
	 * Ratio data definition
	 */
	$ratios = json_decode( wp_unslash( $_REQUEST[ 'target' ] ) );
	$ratio = explode( "_", $ratios[ 0 ] );
	$ratio_type = $ratio[ 0 ];
	$ratio_x = $ratio[ 1 ];
	$ratio_y = $ratio[ 2 ];


	/**
	 * @edited
	 * Custom image editor created, original doesnt allow same size image cropping
	 */
	require_once __DIR__ . '/class-pronotron-image-editor-imagick.php';
	$img = new WP_Pronotron_Image_Editor_Imagick( _load_image_to_edit_path( $post_id, 'full' ) );
	$loaded = $img->load();
	
	if ( is_wp_error( $img ) ){
		$return->error = esc_js( __( 'Unable to create new image.' ) );
		return $return;
	}


	/**
	 * Get target from REQUEST, ( landscapes, portaits, thumbnail )
	 */
	$target = ! empty( $_REQUEST['target'] ) ? preg_replace( '/[^a-z0-9_-]+/i', '', $_REQUEST['target'] ) : '';

	/** This filter is documented in wp-admin/includes/image-edit.php */
	$edit_thumbnails_separately = (bool) apply_filters( 'image_edit_thumbnails_separately', false );


	/**
	 * 1- Crops main image by ratio here, and cold crop x, y coordinates to use in bulk operations later
	 * 2- Than creates subsizes below
	 */
	$crop_dimensions = false;

	if ( ! empty( $_REQUEST[ 'history' ] ) ){

		$changes = json_decode( wp_unslash( $_REQUEST[ 'history' ] ) );

		if ( $changes ){

			// That crops the biggest ratio dimensions than create subsizes below
			list( $cropped_image, $crop_dimensions ) = image_edit_apply_changes( $img, $changes, $ratio_type, $ratio_x, $ratio_y );

			$img = $cropped_image;
		}

	} else {

		$return->error = esc_js( __( 'Nothing to save, the image has not changed.' ) );
		return $return;

	}


	/**
	 * wp_get_attachment_metadata() returns all sizes(thumbnail, landscape_5_3_full, portrait...), width, height
	 * @var $backup_sizes -> returns null if any edit not yet done
	 */
	$meta         = wp_get_attachment_metadata( $post_id );
	$backup_sizes = get_post_meta( $post->ID, '_wp_attachment_backup_sizes', true );

	if ( ! is_array( $meta ) ){
		$return->error = esc_js( __( 'Image data does not exist. Please re-upload the image.' ) );
		return $return;
	}

	if ( ! is_array( $backup_sizes ) ){
		$backup_sizes = array();
	}


	/**
	 * Maintain "_last_crop_coordinates" meta for each art directioned image
	 * to use in bulk operations later
	 */
	$last_crop = get_post_meta( $post->ID, '_last_crop_coordinates', true );

	if ( ! is_array( $last_crop ) ){
		$last_crop = array();
	}

	$last_crop[ "{$ratio_x}/{$ratio_y}" ] = $crop_dimensions;

	update_post_meta( $post->ID, '_last_crop_coordinates', $last_crop );
	//DevelopmentUtils::debug_log( array( 'LAST_CROPS' => $last_crop ) );


	/**
	 * Generate filename
	 */
	$path = get_attached_file( $post_id );

	$basename = pathinfo( $path, PATHINFO_BASENAME );
	$dirname  = pathinfo( $path, PATHINFO_DIRNAME );
	$ext      = pathinfo( $path, PATHINFO_EXTENSION );
	$filename = pathinfo( $path, PATHINFO_FILENAME );
	$suffix   = time() . rand( 100, 999 );

	if ( defined( 'IMAGE_EDIT_OVERWRITE' ) && IMAGE_EDIT_OVERWRITE &&
		isset( $backup_sizes['full-orig'] ) && $backup_sizes['full-orig']['file'] != $basename ){

		if ( $edit_thumbnails_separately && 'thumbnail' === $target ) {
			$new_path = "{$dirname}/{$filename}-temp.{$ext}";
		} else {
			$new_path = $path;
		}
	} else {
		while ( true ){
			$filename     = preg_replace( '/-e([0-9]+)$/', '', $filename );
			$filename    .= "-e{$suffix}";
			$new_filename = "{$filename}.{$ext}";
			$new_path     = "{$dirname}/$new_filename";
			if ( file_exists( $new_path ) ){
				$suffix++;
			} else {
				break;
			}
		}
	}

	// Save the full-size file, also needed to create sub-sizes.
	if ( ! wp_save_image_file( $new_path, $img, $post->post_mime_type, $post_id ) ) {
		$return->error = esc_js( __( 'Unable to save the image.' ) );
		return $return;
	}




	// $sizes should be an array
	$sizes = json_decode( wp_unslash( $_REQUEST[ 'target' ] ) );
	$success = true;
	$delete  = true;
	$nocrop  = true;



	/*
	 * We need to remove any existing resized image files because
	 * a new crop or rotate could generate different sizes (and hence, filenames),
	 * keeping the new resized images from overwriting the existing image files.
	 * https://core.trac.wordpress.org/ticket/32171
	 */
	// if ( defined( 'IMAGE_EDIT_OVERWRITE' ) && IMAGE_EDIT_OVERWRITE && ! empty( $meta['sizes'] ) ) {
	// 	foreach ( $meta['sizes'] as $size ) {
	// 		if ( ! empty( $size['file'] ) && preg_match( '/-e[0-9]{13}-/', $size['file'] ) ) {
	// 			$delete_file = path_join( $dirname, $size['file'] );
	// 			wp_delete_file( $delete_file );
	// 		}
	// 	}
	// }

	if ( defined( 'IMAGE_EDIT_OVERWRITE' ) && IMAGE_EDIT_OVERWRITE && ! empty( $meta['sizes'] ) ){
		foreach ( $meta['sizes'] as $key => $size ) {

			/** $sizes -> radio button request sizes [landscape,portrait,...] */
			if ( in_array( $key, $sizes ) ){
				if ( ! empty( $size['file'] ) && preg_match( '/-e[0-9]{13}-/', $size['file'] ) ){

					$delete_file = path_join( $dirname, $size['file'] );
					wp_delete_file( $delete_file );
				}
			}
		}
	}



	if ( isset( $sizes ) ){

		$_sizes = array();

		foreach ( $sizes as $size ){

			$tag = false;

			if ( isset( $meta['sizes'][ $size ] ) ){

				if ( isset( $backup_sizes[ "$size-orig" ] ) ){
					if ( 
						( ! defined( 'IMAGE_EDIT_OVERWRITE' ) || ! IMAGE_EDIT_OVERWRITE ) && 
						$backup_sizes[ "$size-orig" ]['file'] != $meta['sizes'][ $size ]['file'] ){

						$tag = "$size-$suffix";
					}
				} else {
					$tag = "$size-orig";
				}

				if ( $tag ) {
					$backup_sizes[ $tag ] = $meta['sizes'][ $size ];
				}

			}



			$image_full_size = array( $meta[ 'width' ], $meta[ 'height' ] );
			$test = ImageUtils::recalculate_ratio_px_sizes( $image_full_size );
			//DevelopmentUtils::debug_log( [ $test, $size ] );
			//DevelopmentUtils::debug_log( $meta );

			$width  = (int) $test[ $size ]['width'];
			$height = (int) $test[ $size ]['height'];
			$crop   = ( $nocrop ) ? false : $test[ $size ]['crop'];

			$_sizes[ $size ] = array(
				'width'  => $width,
				'height' => $height,
				'crop'   => $crop,
			);
			
		}

		//DevelopmentUtils::debug_log( $_sizes );

		/**
		 * 1- multi_resize()
		 * 2- make_subsize()
		 * 3- resize()
		 * 
		 * 4- image_resize_dimensions() -> error
		 * error: Could not calculate resized image dimensions
		 * 
		 */
		$meta['sizes'] = array_merge( $meta['sizes'], $img->multi_resize( $_sizes ) );
	}







	unset( $img );

	if ( $success ) {
		wp_update_attachment_metadata( $post_id, $meta );
		update_post_meta( $post_id, '_wp_attachment_backup_sizes', $backup_sizes );

		if ( 'thumbnail' === $target || 'all' === $target || 'full' === $target ) {
			// Check if it's an image edit from attachment edit screen.
			if ( ! empty( $_REQUEST['context'] ) && 'edit-attachment' === $_REQUEST['context'] ) {
				$thumb_url         = wp_get_attachment_image_src( $post_id, array( 900, 600 ), true );
				$return->thumbnail = $thumb_url[0];
			} else {
				$file_url = wp_get_attachment_url( $post_id );
				if ( ! empty( $meta['sizes']['thumbnail'] ) ) {
					$thumb             = $meta['sizes']['thumbnail'];
					$return->thumbnail = path_join( dirname( $file_url ), $thumb['file'] );
				} else {
					$return->thumbnail = "$file_url?w=128&h=128";
				}
			}
		}
	} else {
		$delete = true;
	}

	if ( $delete ) {
		wp_delete_file( $new_path );
	}

	$return->msg = esc_js( __( 'Image saved' ) );
	return $return;
}
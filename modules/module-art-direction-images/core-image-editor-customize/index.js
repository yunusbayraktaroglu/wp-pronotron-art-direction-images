import $ from "jquery";
//import { _ } from 'underscore';
import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';

import './css/editor.scss';


/**
 * Override Wordpress default ImageEdit library
 * 
 * - Override "open" and "save" functions to open our php file
 * - Auto create crop area by x and y value sended by our php file
 */
domReady( function () {
	
    if ( ! window.imageEdit ){
        console.log( "Wordpresssss 'image-edit.js' not yet initialized." );
    }

	/**
	 * Add "Crop Image Ratios" button to media modal
	 * Two Column
	 * @see https://github.com/WordPress/wordpress-develop/blob/trunk/src/js/media/views/attachment/details-two-column.js
	 * 
	 */
	if ( wp.media.view.Attachment.Details.TwoColumn ){
		wp.media.view.Attachment.Details.TwoColumn = wp.media.view.Attachment.Details.TwoColumn.extend({
			events: {
				...wp.media.view.Attachment.Details.TwoColumn.prototype.events,
				'click .crop-image-ratios': 'editAttachmentRatios'
			},
			editAttachmentRatios: function( event ){
				if ( event ){
					event.preventDefault();
				}
				this.controller.content.mode( 'edit-image-ratios' );
			},
			template: function( view ){
	
				// tmpl-attachment-details
				const html = wp.media.template( 'attachment-details-two-column' )( view ); // the template to extend
	
				const dom = document.createElement('div');
				dom.innerHTML = html;
		
				// create image actions wrapper
				const details = dom.querySelector( ".attachment-actions" );
				const actions = document.createElement( "button" ); // create a new element
				actions.classList.add( "button", "crop-image-ratios" ); // add a class to the element for styling
				//actions.setAttribute('id', this.model.attribute.id); // add the image-id using the attributes
				actions.innerHTML = "Crop Image Ratios"; // element text
				details.appendChild( actions ); // add new element at the correct spot
		
				return dom.innerHTML;
			}
		});
	}



	/**
	 * Configure event handlers when fired "Crop Image Ratios" button clicked
	 * @see https://github.com/WordPress/wordpress-develop/blob/trunk/src/js/media/views/frame/edit-attachments.js
	 * 
	 */
	const EditAttachments = wp.media.view.MediaFrame.EditAttachments;

	if ( EditAttachments ){
		wp.media.view.MediaFrame.EditAttachments = EditAttachments.extend({
			bindHandlers: function() {
				EditAttachments.prototype.bindHandlers.apply( this, arguments );
				this.on( 'content:create:edit-image-ratios', this.editImageRatiosMode, this );
				this.on( 'content:render:edit-image-ratios', this.editImageRatiosModeRender, this );
			},
			editImageRatiosMode: function( contentRegion ){
				var editImageController = new wp.media.controller.EditImage({
					model: this.model,
					frame: this
				});
				// Noop some methods.
				editImageController._toolbar = function(){};
				editImageController._router = function(){};
				editImageController._menu = function(){};
		
				contentRegion.view = new wp.media.view.EditImage.Details({
					model: this.model,
					frame: this,
					controller: editImageController
				});
		
				this.gridRouter.navigate( this.gridRouter.baseUrl( '?item=' + this.model.id + '&mode=edit-ratios' ) );
			},
			editImageRatiosModeRender: function( view ){
				function openEditor(){
					window.imageEdit.openTest( this.model.get( 'id' ), this.model.get( 'nonces' ).edit, this );
				}
				view.on( 'ready', openEditor );
			},
		});
	}





	/**
	 * @todo
	 * This doesnt works
	 * Helps when edit-ratios frame directly opened from link like:
	 * http://localhost:8888/wp-admin/upload.php?item=16&mode=edit
	 */
	// wp.media.view.MediaFrame.Manage.Router = wp.media.view.MediaFrame.Manage.Router.extend({
	// 	routes: {
	// 		...wp.media.view.MediaFrame.Manage.Router.prototype.routes,
	// 		'upload.php?item=:slug&mode=edit-ratios': 'editRatios',
	// 	},
	// 	editRatios: function( query ){
	// 		console.log( "router" );
	// 		this.showItem( query );
	// 		wp.media.frames.edit.content.mode( "edit-image-ratios" );
	// 	}
	// });




	/**
     * Extend default imageEdit.open with 1 line of code
     * change 'ajax.action' to our action to load our php file
     * 
     * @see https://github.com/WordPress/WordPress/blob/master/wp-admin/js/image-edit.js#L752
     * 
     */
	window.imageEdit.openTest = function( postid, nonce, view ){

		this._view = view;

		const elem = $( '#image-editor-' + postid );
		const head = $( '#media-head-' + postid );
		const btn = $( '#imgedit-open-btn-' + postid );
		const spin = btn.siblings( '.spinner' );

		/*
		* Instead of disabling the button, which causes a focus loss and makes screen
		* readers announce "unavailable", return if the button was already clicked.
		*/
		if ( btn.hasClass( 'button-activated' ) ) {
			return;
		}

		spin.addClass( 'is-active' );

		const data = {
			'action': 'pronotron_image_editor', // --> ONLY CHANGED PART
			'_ajax_nonce': nonce,
			'postid': postid,
			'do': 'open'
		};

		const dfd = $.ajax( {
			url:  ajaxurl,
			type: 'post',
			data: data,
			beforeSend: function() {
				btn.addClass( 'button-activated' );
			}
		} ).done( function( response ) {
			var errorMessage;

			if ( '-1' === response ) {
				errorMessage = __( 'Could not load the preview image.' );
				elem.html( '<div class="notice notice-error" tabindex="-1" role="alert"><p>' + errorMessage + '</p></div>' );
			}

			if ( response.data && response.data.html ) {
				elem.html( response.data.html );
			}

			head.fadeOut( 'fast', function() {
				elem.fadeIn( 'fast', function() {
					if ( errorMessage ) {
						$( document ).trigger( 'image-editor-ui-ready' );
					}
				} );
				btn.removeClass( 'button-activated' );
				spin.removeClass( 'is-active' );
			} );

			// Initialise the Image Editor now that everything is ready.
			window.imageEdit.init( postid );
		} );

		return dfd;
	};



    /**
     * Extend default imageEdit.save with 1 line of code
     * change 'ajax.action' to our action to load our php file
	 * called in image-edit.php
     * 
     * @see https://github.com/WordPress/WordPress/blob/master/wp-admin/js/image-edit.js
     * @see https://atimmer.github.io/wordpress-jsdoc/-_enqueues_lib_image-edit.js.html
     * 
     */
	window.imageEdit.saveTest = function( postid, nonce ){

		var data,
			target = this.getTarget( postid ),
			history = this.filterHistory( postid, 0 ),
			self = this;

		if ( '' === history ) {
			return false;
		}

		this.toggleEditor( postid, 1 );

		data = {
			'action': 'pronotron_image_editor',  // --> ONLY CHANGED PART
			'_ajax_nonce': nonce,
			'postid': postid,
			'history': history,
			'target': target,
			'context': $( '#image-edit-context' ).length ? $( '#image-edit-context' ).val() : null,
			'do': 'save'
		};

		// Post the image edit data to the backend.
		$.post( ajaxurl, data, function( response ) {
			// If a response is returned, close the editor and show an error.
			if ( response.data.error ) {
				$( '#imgedit-response-' + postid )
					.html( '<div class="notice notice-error" tabindex="-1" role="alert"><p>' + response.data.error + '</p></div>' );

				imageEdit.close(postid);
				wp.a11y.speak( response.data.error );
				return;
			}

			if ( response.data.fw && response.data.fh ) {
				$( '#media-dims-' + postid ).html( response.data.fw + ' &times; ' + response.data.fh );
			}

			if ( response.data.thumbnail ) {
				$( '.thumbnail', '#thumbnail-head-' + postid ).attr( 'src', '' + response.data.thumbnail );
			}

			if ( response.data.msg ) {
				$( '#imgedit-response-' + postid )
					.html( '<div class="notice notice-success" tabindex="-1" role="alert"><p>' + response.data.msg + '</p></div>' );

				wp.a11y.speak( response.data.msg );
			}

			if ( self._view ) {
				self._view.save();
			} else {
				imageEdit.close( postid );
			}
		});
	};



    /**
     * Set ratios of defined image sizes
     * @param {number} postid - editing attachment id 
     * @param {string} nonce - wp nonce
     * @param {number} x - x ratio
     * @param {number} y - y ratio
     * 
     */
    window.imageEdit.ySetRatioSelection = function( postid, nonce, x, y ) {

        const img = $( '#image-preview-' + postid );
        const height = img.innerHeight();
        const width = img.innerWidth();

		const data = {};

		/** Landscape */
		if ( x > y ){
			data.minWidth = width;
			data.minHeight = width / x * y;

			data.leftX = 0;
			data.leftY = ( height - data.minHeight ) / 2;
			data.rightX = width;
			data.rightY = data.leftY + data.minHeight;
		} 
		/** Portrait */
		else {
			data.minWidth = height / y * x;
			data.minHeight = height;

			data.leftX = ( width - data.minWidth ) / 2;
			data.leftY = 0;
			data.rightX = data.leftX + data.minWidth;
			data.rightY = height;
		}

		/**
		 * For 5 / 3 source image, 5 / 4 ratio registered
		 */
		if( data.minHeight > height ){
			const multiplier = data.minHeight / height;
			data.minHeight /= multiplier;
			data.minWidth /= multiplier;
		}

        this.iasapi.setOptions({
            //aspectRatio: x + ':' + y,
            minWidth: data.minWidth,
			maxWidth: data.minWidth,
            minHeight: data.minHeight,
			maxHeight: data.minHeight,
            show: true,
        });

		/**
		 * Set the current selection
		 *
		 * @param x1
		 *            X coordinate of the upper left corner of the selection area
		 * @param y1
		 *            Y coordinate of the upper left corner of the selection area
		 * @param x2
		 *            X coordinate of the lower right corner of the selection area
		 * @param y2
		 *            Y coordinate of the lower right corner of the selection area
		 * @param noScale
		 *            If set to <code>true</code>, scaling is not applied to the
		 *            new selection
		 */
        this.iasapi.setSelection( data.leftX, data.leftY, data.rightX, data.rightY, true );
        this.iasapi.update();
	};

});
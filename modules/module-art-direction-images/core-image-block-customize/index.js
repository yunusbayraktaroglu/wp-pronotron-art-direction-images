/**
 * Extend "core/image" block
 * 
 * - Add "isFlud" attribute to default attributes
 * - Add art-directioned toggle if image has not missing sizes (landscapes, portraits)
 * - Unregister default "rounded" style
 * - Remove "border option" in editor panel 
 * 
 * @todos
 * - Caption textbox in image with absolute sibling creates select problems in editor
 * - Disable if sizeSlug portait / landscape
 * 
 * References
 * @see https://developer.wordpress.org/block-editor/reference-guides/filters/block-filters/
 * @see https://gutenberg.10up.com/guides/modifying-the-markup-of-a-core-block
 * 
 */

import './css/editor.scss';
import './css/style.scss';

import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { BlockContextProvider, InspectorControls, useBlockProps  } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { unregisterBlockStyle } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { useEffect, useMemo } from '@wordpress/element';

import domReady from '@wordpress/dom-ready';
import { ArtDirectionImage } from './js/ArtDirectionImage.js';

domReady( function () {
    unregisterBlockStyle( 'core/image', 'rounded' );
    unregisterBlockStyle( 'core/image', 'default' );
    // registerBlockStyle( 'core/image', {
    //     name: 'test',
    //     label: 'test',
    // } );
});


/** 
 * - Define an attribute named "isFluid" for core/image blocks
 * 	 can to be setted with setAttributes()
 * 
 * - Remove "border option" in panel 
 */
function modifyImageBlockOptions( settings, name ) {

	if ( name !== 'core/image' ) {
		return settings;
	}

	return {
		...settings,
        attributes: {
            ...settings.attributes,
			isFluid: {
				type: "boolean",
				default: false
			}
        },
        supports: {
            ...settings.supports,
            __experimentalBorder: false
        }
	};
}

addFilter(
    'blocks.registerBlockType',
    'wp-pronotron/modify-image-block-options',
    modifyImageBlockOptions
);



function checkIfArtDirecitoned( imageSizes ){
	const sizeNames = Object.keys( imageSizes );
	
	const landscape = sizeNames.some( name => name.includes( "landscape" ) );
	const portrait = sizeNames.some( name => name.includes( "portrait" ) );

	return ( landscape && portrait );
}

/**
 * - Extend "core/image" block's edit() function
 * - Provide context with <BlockContextProvider> to avoid resize & crop functions
 */
const CustomizedCoreImageBlock = createHigherOrderComponent( ( BlockEdit ) => ( props ) => {

	/**
	 * Pick "core/image" blocks
	 */
	if ( props.name !== 'core/image' ){
		return <BlockEdit { ...props } />;
	}


	/**
	 * - Check for missing image sizes [ landscape_5_3_full, portrait_3_5_medium, ... ]
	 *   If has some missing sizes, return default "core/image" block
	 * 
	 * - Image data returns undefined on first mount, so check for image is ready first 
	 * 
	 */
	const imageData = useSelect(( select ) => select( 'core' ).getMedia( props.attributes.id ) );
	console.log( imageData )

	/**
	 * - Get block props to create virtual parent and hide original image block behind
	 * - Disable fluid when image changed to check if the image have all image sizes
	 */
	const blockProps = useBlockProps();


	/**
	 * Recheck art-direction compatibility if image has changed
	 */
	useEffect(() => {
		// console.log( "Image replaced", imageData );

		if ( imageData && attributes.isFluid ){
			const isArt = checkIfArtDirecitoned( imageData.media_details.sizes );
			if ( ! isArt ){
				props.setAttributes({ isFluid: false });
			}
		}
	}, [ imageData ]);



	const { attributes, setAttributes } = props;


	if ( ! imageData || ! checkIfArtDirecitoned( imageData.media_details.sizes ) ){
        return <BlockEdit { ...props } />;
	}

	

    /** 
	 * Image is compatible to be "art-directioned"
	 * Disable crop & resize with <BlockContextProvider> context
	 * 
	 * @todo Disable if sizeSlug vertical 
	 */
    const { isFluid, sizeSlug } = attributes;


    return (
        <>
		    <InspectorControls>
                <PanelBody>
					<ToggleControl
						label={ __( 'Art Direction Image' ) }
						checked={ isFluid }
                        onChange={ () => setAttributes({ isFluid: ! isFluid }) }
						help={ 'Image is compatible to be art-directioned' }
					/>
                </PanelBody>
            </InspectorControls>

			{ isFluid ? (
				<figure {...blockProps}>
					<BlockContextProvider value={{ allowResize: false, imageCrop: false }}>
						<ArtDirectionImage id={ imageData.id } />
						<BlockEdit { ...props } className={ 'wp-pronotron-fluid-image-original' } />
					</BlockContextProvider>
				</figure>
			) : (
				<BlockEdit { ...props } />
			)}
        </>
    );

}, 'withSidebarSelect' );

addFilter(
    'editor.BlockEdit',
    'wp-pronotron/customized-core-image',
    CustomizedCoreImageBlock
);




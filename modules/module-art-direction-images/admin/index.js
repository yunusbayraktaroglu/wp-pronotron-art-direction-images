import './css/editor.scss';
import './css/style.scss';

import { __ } from '@wordpress/i18n';
import { createRoot } from '@wordpress/element';

import { ImageRatios } from './js/AspectRatioForm.js';
import { ImageUploadSize } from './js/ImageUploadSizeForm.js';
import { RatioVariations } from './js/RatioVaryForm.js';


const module_id = "options-module-art-direction-images";


const image_ratios_root = document.getElementById( `${module_id}_image_ratios` );
const image_ratios_default = JSON.parse( image_ratios_root.dataset.default );
const image_ratios_optionId = JSON.parse( image_ratios_root.dataset.option );
const image_ratios_isActive = JSON.parse( image_ratios_root.dataset.active );
const image_ratios_reactRoot = createRoot( image_ratios_root );

document.addEventListener( "DOMContentLoaded", function( event ){
	image_ratios_reactRoot.render( 
		<ImageRatios 
			optionId={ image_ratios_optionId } 
			defaultSettings={ image_ratios_default } 
			isActive={ image_ratios_isActive }
		/> 
	);
});



const ratio_vary_root = document.getElementById( `${module_id}_ratio_variations` );
const ratio_vary_default = JSON.parse( ratio_vary_root.dataset.default );
const ratio_vary_optionId = JSON.parse( ratio_vary_root.dataset.option );
const ratio_vary_reactRoot = createRoot( ratio_vary_root );

document.addEventListener( "DOMContentLoaded", function( event ){
	ratio_vary_reactRoot.render( 
		<RatioVariations optionId={ ratio_vary_optionId } defaultSettings={ ratio_vary_default } /> 
	);
});



const upload_sizes_root = document.getElementById( `${module_id}_upload_sizes` );
const upload_sizes_default = JSON.parse( upload_sizes_root.dataset.default );
const upload_sizes_optionId = JSON.parse( upload_sizes_root.dataset.option );
const upload_sizes_reactRoot = createRoot( upload_sizes_root );

document.addEventListener( "DOMContentLoaded", function( event ){
	upload_sizes_reactRoot.render( 
		<ImageUploadSize optionId={ upload_sizes_optionId } defaultSettings={ upload_sizes_default } /> 
	);
});

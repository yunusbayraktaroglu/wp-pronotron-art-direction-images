import { useEffect, useState } from 'react';

function greatestCommonDivisor( width, height ){
	return ( width % height ) ? greatestCommonDivisor( height, width % height ) : height;
}

export function ImageUploadSize({ optionId, defaultSettings }){

	//console.log( defaultSettings );

	const { active, data, ...uploadSizes } = defaultSettings;

	const [ isActive, setIsActive ] = useState( active );
	const [ uploadSize, setUploadSize ] = useState( uploadSizes );
	const [ ratioInfo, setRatioInfo ] = useState( '' );

	useEffect(() => {
		const divisor = greatestCommonDivisor( uploadSize.width, uploadSize.height );
		const ratio = {
			x: uploadSize.width / divisor,
			y: uploadSize.height / divisor
		};
		setRatioInfo( `( X Ratio: ${ ratio.x }, Y Ratio: ${ ratio.y } )` );
	}, [ uploadSize ]);
	
	const changeSize = (event) => {
		setUploadSize({
			...uploadSize,
			[event.target.dataset.prop]: parseInt( event.target.value ),
		});
	};

	return (
		<>
			<fieldset>
				<legend className="screen-reader-text"><span>Activate.</span></legend>
				<label htmlFor={ `${ optionId }[active]` }>
					<Checkbox name={ `${ optionId }[active]` } value={ isActive } toggle={ setIsActive } />
					Activate on image upload.
				</label>
			</fieldset>
			<fieldset style={{ opacity: isActive ? 1.0 : 0.4 }} disabled={ ! isActive }>
				<fieldset className="flex-middle">
					<fieldset>
						<legend className="screen-reader-text"><span>Force image sizes on upload.</span></legend>
						<label htmlFor={ `${ optionId }[width]` } className='img-size-label'>Width :</label>
						<input 
							onChange={ changeSize }
							type="number"
							className="small-text"
							data-prop="width"
							id={ `${ optionId }[width]` }
							name={ `${ optionId }[width]` }
							defaultValue={ uploadSize.width }
							min="500"
							step="100" />
						<br />
						<label htmlFor={ `${ optionId }[height]` } className='img-size-label'>Height :</label>
						<input 
							onChange={ changeSize }
							type="number"
							className="small-text"
							data-prop="height"
							id={ `${ optionId }[height]` }
							name={ `${ optionId }[height]` }
							defaultValue={ uploadSize.height }
							min="500"
							step="100" />
						<br />
					</fieldset>
					<span className="helper" style={{ display: isActive ? 'block' : 'none' }}>{ ratioInfo }</span>
				</fieldset>
				<fieldset>
					<legend className="screen-reader-text"><span>Force image sizes on upload.</span></legend>
					<label htmlFor={ `${ optionId }[data][force]` }>
						<CheckboxChild name={ `${ optionId }[data][force]` } defaultSettings={ data?.force } />
						Force image sizes on upload
					</label>
					<br />
					<legend className="screen-reader-text"><span>Crop ratios only if match with that dimensions.</span></legend>
					<label htmlFor={ `${ optionId }[data][only]` }>
						<CheckboxChild name={ `${ optionId }[data][only]` } defaultSettings={ data?.only } />
						Crop ratios only if match with that dimensions
					</label>
				</fieldset>
			</fieldset>
		</>

	)
}


function Checkbox({ name, value, toggle }){

	return (
		<input 
			type="checkbox" 
			name={ name }
			id={ name }
			checked={ value }
			value={ value }
			onChange={ () => toggle( value ? 0 : 1 ) }
		/>
	)

};


function CheckboxChild({ name, defaultSettings }){

	const [ checked, setChecked ] = useState( defaultSettings ?? 0 );

	return (
		<input 
			type="checkbox" 
			name={ name }
			id={ name }
			checked={ checked }
			value={ checked }
			onChange={ () => setChecked( checked ? 0 : 1 ) }
		/>
	)

};
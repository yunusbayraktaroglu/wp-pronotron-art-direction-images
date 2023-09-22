import { useEffect, useState } from 'react';

export function RatioVariations({ optionId, defaultSettings }){

	const [ ratioVariations, setRatioVariations ] = useState( defaultSettings ?? [] );

	return (
		<div style={{ display: 'flex' }}>
		<div className="fieldset-wrapper">
			<div className="flex-middle">
				<button 
					type="button" 
					className="button button-add" 
					onClick={ () => setRatioVariations([ ...ratioVariations, 0.5 ]) }
				>
						Add Variation
				</button>
			</div>
			<div className="fieldset-list">
				<fieldset>
					<label>Default Variation :</label>
					<input 
						value={ 1.0 }
						disabled
						type="number"
						className="small-text"
						min={ 0 }
						max={ 0.99 }
						step={ 0.01 }
					/>
				</fieldset>
				{ ratioVariations.map( ( ratio, index ) =>
					<VariationControl 
						key={ index } 
						name={ `${ optionId }[${ index }]` }
						type="landscape"
						defaultMultiplier={ ratio } />
				) }
			</div>
		</div>
		</div>

	)
}

function VariationControl({ name, defaultMultiplier }){

	const [ multiplier, setMultiplier ] = useState( defaultMultiplier );
	const [ active, setActive ] = useState( true );

	if ( ! active ) return null;

	return (
		<fieldset>
			<fieldset>
				<label htmlFor={ `${ name }[x]` }>Variation :</label>
				<input 
					onChange={( event ) => setMultiplier( event.target.value )}
					value={ multiplier }
					type="number"
					className="small-text"
					id={ `${ name }` }
					name={ `${ name }` }
					min={ 0 }
					max={ 0.99 }
					step={ 0.01 }
				/>
			</fieldset>
			<button 
				type="button" 
				className="button button-remove" 
				onClick={ () => setActive( false ) }>X</button>
		</fieldset>
	)
}
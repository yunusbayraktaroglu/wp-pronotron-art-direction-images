import { useEffect, useState } from 'react';

export function ImageRatios({ optionId, defaultSettings, isActive }){

	//console.log( defaultSettings );

	// Ratio type might be empty
	const [ landscapeRatios, setLandscapeRatios ] = useState( defaultSettings[ 'landscape_ratios' ] ?? [] );
	const [ portraitRatios, setPortraitratios ] = useState( defaultSettings[ 'portrait_ratios' ] ?? [] );

	return (
		<>
		{ ! isActive && 
			<p style={{ fontWeight: 'bold', color: 'red', marginBottom: 10}}>
				Save your ratios to activate plugin.
			</p> 
		}
		<div style={{ display: 'flex' }}>
			<div className="fieldset-wrapper">
				<div className="flex-middle">
					<label>Landscape Ratios</label>
					<button 
						type="button" 
						className="button button-add" 
						onClick={ () => setLandscapeRatios([ ...landscapeRatios, { x: 5, y: 3 } ]) }
					>
							Add Ratio
					</button>
				</div>
				<div className="fieldset-list">
					{ landscapeRatios.map( ( ratio, index ) =>
						<RatioControl 
							key={ index } 
							name={ `${ optionId }[landscape_ratios][${ index }]` }
							type="landscape"
							defaultRatio={ ratio } />
					) }
				</div>
			</div>

			<div className="fieldset-wrapper" style={{ marginLeft: 20, paddingLeft: 20, borderLeft: "1px solid #ccc" }}>
				<div className="flex-middle">
					<label>Portrait Ratios</label>
					<button 
						type="button" 
						className="button button-add" 
						onClick={ () => setPortraitratios([ ...portraitRatios, { x: 3, y: 5 } ]) }
					>
							Add Ratio
					</button>
				</div>
				<div className="fieldset-list">
					{ portraitRatios.map( (ratio, index) =>
						<RatioControl 
							key={ index } 
							name={ `${ optionId }[portrait_ratios][${ index }]` }
							type="portrait"
							defaultRatio={ ratio } />
					) }
				</div>
			</div>
		</div>
		</>
	)
}

function RatioControl({ type, name, defaultRatio }){

	const [ ratio, setRatio ] = useState( defaultRatio );
	const [ active, setActive ] = useState( true );

	const changeRatio = ( event ) => {
		const temporary = { ...ratio, [event.target.dataset.prop]: parseInt( event.target.value ) };

		if ( (type === "portrait" && temporary.x >= temporary.y) || (type === "landscape" && temporary.y >= temporary.x) ){
			event.preventDefault();
			return false;
		}
		setRatio( temporary );
	};

	if ( ! active ) return null;

	return (
		<fieldset>
			<fieldset>
				<label htmlFor={ `${ name }[x]` } className='ratio-label'>X Ratio :</label>
				<input 
					onChange={ changeRatio }
					value={ ratio.x }
					type="number"
					className="small-text"
					id={ `${ name }[x]` }
					name={ `${ name }[x]` }
					min="1"
					step="1"
					data-prop="x"
				/>
				<br />
				<label htmlFor={ `${ name }[y]` } className='ratio-label'>Y Ratio :</label>
				<input 
					onChange={ changeRatio }
					value={ ratio.y }
					type="number"
					className="small-text"
					id={ `${ name }[y]` }
					name={ `${ name }[y]` }
					min="1"
					step="1"
					data-prop="y"
				/>
				<br/>
			</fieldset>
			<button 
				type="button" 
				className="button button-remove" 
				onClick={ () => setActive( false ) }>X</button>
		</fieldset>
	)
}
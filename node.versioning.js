"use strict";

import readline from 'node:readline';
import { exec } from "child_process";
import { promises } from "fs";

// Create node interface
const rl = readline.createInterface({ input: process.stdin, output: process.stdout });

// Colored strings
const red = ( string ) => `\x1b[31m${ string }\x1b[0m`;
const green = ( string ) => `\x1b[32m${ string }\x1b[0m`;
const bgRed = ( string ) => `\x1b[41m${ string }\x1b[0m`;
const bgGreen = ( string ) => `\x1b[42m${ string }\x1b[0m`;


/**
 * Asks for a new version
 * 
 * @param {string} currentVersion - Version from package.json
 * @returns {Promise<string>} answer
 */
const upgradeTypeSelect = ( currentVersion ) => {

	return new Promise( ( resolve, reject ) => {

		const acceptedAnswers = [ "major", "minor", "patch", "override" ];

		const version = bgRed( ` ${ currentVersion } ` );
		const upgradeSelect = green( `[ ${ acceptedAnswers.join( ", " ) } ]` );

		rl.question( `Current version: ${ version } → chose an upgrade ${ upgradeSelect } `, ( answer ) => {

			if (  acceptedAnswers.includes( answer ) ){
				resolve( answer );
			} else {
				reject( `Unsupported answer: '${ answer }'.` );
			}

		} );

	} );

}

/**
 * Creates target version
 * 
 * @param {string} currentVersion - Version from package.json
 * @param {string} upgradeType - Answer from previous question 
 * @returns {Promise<string>} answer
 */
const createFinalVersion = ( currentVersion, upgradeType ) => {

	return new Promise( ( resolve, reject ) => {

		let finalVersion = [];
		const version = currentVersion.split( "." );

		if ( version.length > 3 ){
			switch( upgradeType ){
				case "patch":
					finalVersion = [ version[0], version[1], Number( version[2] ) + 1 ];
					break;
				default:
					finalVersion = version;
			}
		}

		switch( upgradeType ){
			case "major":
				finalVersion = [ Number( version[0] ) + 1, 0, 0 ];
				break;
			case "minor":
				finalVersion = [ version[0], Number( version[1] ) + 1, 0 ];
				break;
			case "patch":
				finalVersion = [ version[0], version[1], Number( version[2] ) + 1 ];
				break;
			default:
				finalVersion = version;
		}

		finalVersion = finalVersion.join( "." );

		const currentV = bgRed( ` ${ currentVersion } ` );
		const finalV = bgGreen( ` ${ finalVersion } ` );

		rl.question( `Update version: ${ currentV } → ${ finalV } - Continue? ${ green( "[ yes ]" ) } `, ( answer ) => {
			
			const acceptedAnswers = [ "yes" ];
			
			if ( acceptedAnswers.includes( answer ) ){
				resolve( finalVersion );
			} else {
				reject( "Aborted" );
			}

		} );

	} );

}


/**
 * Updates files that includes version
 */
const updateFiles = async( finalVersion, packageData ) => {

	/** 
	 * Update package.json 
	 */
	packageData.version = finalVersion;
	await promises.writeFile( "./package.json", JSON.stringify( packageData, null, '\t' ) );

	console.log( green( `"package.json" → version changed.` ) );

	/** 
	 * Update main plugin file
	 */
	const files = [ `${ packageData.name }.php` ];

	await Promise.all( files.map( async( file ) => {
		const content = await promises.readFile( file, 'utf8' );
		const newContent = content.replace( /\d{1,4}\.\d{1,4}\.\d{1,4}/g, finalVersion );
		await promises.writeFile( file, newContent, 'utf-8' );
		console.log( green( `"${ file }" -> version changed.` ) );
	}));
	
}


/**
 * CLI versioning
 */
const main = async() => {

	try {

		// Read package.json
		const packageJson = await promises.readFile( './package.json', 'utf8' );
		const packageData = JSON.parse( packageJson );
		const currentVersion = packageData.version;

		// Get user answers
		const upgradeType = await upgradeTypeSelect( currentVersion );
		const targetVersion = await createFinalVersion( currentVersion, upgradeType );

		if ( currentVersion !== targetVersion ){
			await updateFiles( targetVersion, packageData );
		}

		/**
		 * Create git tag and fire github action
		 */
		console.log( `Creating git tag: v${ targetVersion }` );

		//const gitCheckout = `git checkout develop`;
		const gitStage 		= `git add .`;
		const gitCommit		= `git commit -m "Created v${ targetVersion }"`;
		const gitTag 		= `git tag -f v${ targetVersion }`;
		const gitPush 		= `git push origin && git push origin v${ targetVersion } -f`;

		const gitCommand = gitStage + " && " + gitCommit + " && " + gitTag + " && " + gitPush;

		exec( `${ gitCommand }`, function( error, stdout, stderr ){

			console.log( stdout, stderr );
			
			if ( error !== null ){
				console.log( 'exec error: ' + error );
			}

		} );

	} catch( err ){

		console.log( red( `Not completed: ${ err }` ) );

	} finally {

		rl.close();

	}

}

main();
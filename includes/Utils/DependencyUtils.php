<?php

namespace WPPronotronArtDirectionImages\Utils;

/**
 * Class DevelopmentUtils
 * 
 * Helper functions for development purposes
 * @package WPPronotronArtDirectionImages\Utils
 * 
 */
class DependencyUtils {

	/**
	 * Load dependency by it's url and directory
	 * 
	 * @param string $dependency_id
	 * @param string $dependency_dir
	 * @param string $dependency_url
	 * @param array $dependency_local
	 * @return void
	 */
	public static function load_dependency( $dependency_id, $dependency_dir, $dependency_url, $dependency_local = false ){

		$assets = include( "{$dependency_dir}/index.asset.php" );
	
		wp_enqueue_script(
			"pronotron-{$dependency_id}-script",
			"{$dependency_url}/index.js",
			$assets[ 'dependencies' ],
			$assets[ 'version' ],
			true
		);

		/**
		 * Print global JS objects if defined
		 */
		if ( $dependency_local ){

			wp_localize_script( 
				"pronotron-{$dependency_id}-script", 
				$dependency_local[ 'id' ], 
				$dependency_local[ 'data' ]
			);
		}

		/**
		 * Enqueue CSS files if exist
		 */
		if ( file_exists( "{$dependency_dir}/index.css" ) ){

			wp_enqueue_style( 
				"pronotron-{$dependency_id}-editor-style", 
				"{$dependency_url}/index.css", 
				array(), 
				$assets[ 'version' ] 
			);
		}

	}

	/**
	 * Load front-end dependency
	 */
	public static function load_front_end_dependency( $dependency_id, $dependency_dir, $dependency_url, $dependency_local = false ){

		$assets = include( "{$dependency_dir}/index.asset.php" );
	
		/**
		 * Enqueue CSS files if exist
		 */
		if ( file_exists( "{$dependency_dir}/style-index.css" ) ){

			wp_enqueue_style( 
				"pronotron-{$dependency_id}-style", 
				"{$dependency_url}/style-index.css", 
				array(), 
				$assets[ 'version' ] 
			);
		}
	}

}
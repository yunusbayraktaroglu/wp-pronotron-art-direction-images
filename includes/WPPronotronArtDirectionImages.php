<?php

// Global. - namespace WPPronotronArtDirectionImages;

use WPPronotronArtDirectionImages\AdminPageWithoutMenu;
use WPPronotronArtDirectionImages\PluginUpdater;
use WPPronotronArtDirectionImages\Utils\ImageUtils;
use WPPronotronArtDirectionImages\Utils\OptionsUtils;
use WPPronotronArtDirectionImages\Utils\DevelopmentUtils;


final class Constants {
	/** Slug of the plugin */
	public string $slug;
	/** Directory of the plugin */
	public string $dir;
	/** Url of the plugin */
	public string $url;
	/** Basename of the plugin wp-ard-.../wp-ard.php */
	public string $base;
	/** Parsed plugin data in base php file */
	public array $data;
}

/**
 * Class WPPronotronArtDirectionImages
 * @package WPPronotronArtDirectionImages
 */
final class WPPronotronArtDirectionImages {

	private static WPPronotronArtDirectionImages $instance;
	public static PluginUpdater $plugin_updater;
	public static $modules = array();

	/**
	 * The instance of the WPPronotronArtDirectionImages object
	 * @return \WPPronotronArtDirectionImages
	 */
	public static function instance(){

		if ( ! isset( self::$instance ) || ! ( self::$instance instanceof self ) ){

			self::$instance = new self();
			$constants = self::$instance->create_constants();

			/**
			 * Autoload required classes
			 */
			if ( file_exists( "{$constants->dir}vendor/autoload.php" ) ){
				require_once "{$constants->dir}vendor/autoload.php";
			}

			/**
			 * Setup plugin constants to use in object creations
			 */
			OptionsUtils::setup_constants( $constants );

			self::$instance->actions();
			self::$instance::$plugin_updater = new PluginUpdater();

			// DevelopmentUtils::console_log( OptionsUtils::get_constants() );
			// DevelopmentUtils::console_log([ 
			// 	'options-module-art-direction-images' => get_option( 'options-module-art-direction-images' ),
			// ]);

		}

		return self::$instance;

	}

	/**
	 * Setup plugin constants
	 * @return Constants
	 */
	private function create_constants(): Constants {

		$plugin_directory = dirname( __DIR__ );
		$plugin_slug = plugin_basename( $plugin_directory );
		$plugin_main_file = $plugin_directory . "/$plugin_slug.php";

		$constants = new Constants();
		
		$constants->slug = $plugin_slug;
		$constants->dir = plugin_dir_path( $plugin_main_file );
		$constants->url = plugins_url( '/', $plugin_main_file );
		$constants->base = plugin_basename( $plugin_main_file );
		$constants->data = get_plugin_data( $plugin_main_file );

		return $constants;

	}

	/**
	 * Sets up actions to run at certain spots throughout WordPress 
	 * and the WPPronotronArtDirectionImages execution cycle
	 * @return void
	 */
	private function actions(){

		/**
		 * Init WPPronotronArtDirectionImages after themes have been setup,
		 * allowing for both plugins and themes to 
		 * register things before initialization of plugin
		 */
		add_action( 'after_setup_theme', function(){

			$instance = self::instance();
			$instance->add_module( 'module-art-direction-images' ); // Module DIR is "module id"

			do_action( 'pronotron_init', $instance );

		});

	}

	/**
	 * Adds a module and dependencies to WPPronotronArtDirectionImages
	 * @param string $module_name
	 * @return void
	 */
	public static function add_module( string $module_id ){
		
		$plugin_dir = OptionsUtils::get_constant( 'dir' );

		$module = require_once $plugin_dir . "build/{$module_id}/index.php";
		$module->init( $module_id );

		array_push( self::$instance::$modules, $module );

	}

}
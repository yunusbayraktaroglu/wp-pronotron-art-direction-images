<?php

namespace WPPronotronArtDirectionImages;

use WPPronotronArtDirectionImages\Utils\DevelopmentUtils;
use WPPronotronArtDirectionImages\Utils\DependencyUtils;
use WPPronotronArtDirectionImages\Utils\OptionsUtils;

/**
 * Creates an admin page for given module
 * @package WPPronotronArtDirectionImages\AdminPageWithoutMenu
 */
class AdminPageWithoutMenu {

	public string $page_name;
	public string $options_name;
	public string $module_id; 
	public array $module_data;
	
	/**
	 * @param string $module_id
	 * @param array $module_data
	 */
	public function __construct( $module_id, $module_data ){

		$this->module_id = $module_id;
		$this->module_data = $module_data;
		$this->page_name = $module_id;
		$this->options_name = "options-{$module_id}";
		$this->init();

	}

	/**
	 * Initialize a admin page for module
	 * @return void
	 */
	public function init(){

		$plugin_base = OptionsUtils::get_constant( 'base' );
		
		// Actions
		add_action( 'admin_menu', [ $this, 'add_admin_page' ] );
		add_action( 'admin_init', [ $this, 'init_admin' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );

		// Filters
		add_filter( "plugin_action_links_{$plugin_base}", [ $this, 'add_settings_link' ] );

	}

	/**
	 * Add the options page to the WP Admin
	 * @return void
	 */
	public function add_admin_page(){

		add_submenu_page(
			'tools.php',
			__( "Pronotron / {$this->module_data[ 'name' ]}", 'pronotron' ), // Page title
			__( "Pronotron / {$this->module_data[ 'name' ]}", 'pronotron' ), // Left menu title
			'manage_options', // Capability
			$this->module_id, // Url slug for page
			[ $this, 'wp_pronotron_settings_page' ], // Callable
			0
		);
		
	}

	/**
	 * Adds "settings" link to plugin page
	 */
	public function add_settings_link( $links ){

		// Build and escape the URL.
		$settings_url = esc_url( add_query_arg( 'page', $this->module_id, get_admin_url() . 'tools.php' ) );

		// Create the link.
		$settings_link = "<a href='$settings_url'>" . __( 'Settings' ) . '</a>';

		// Adds the link to the end of the array.
		array_push( $links, $settings_link );

		return $links;

	}

	public function print_desc(){
		$title = sprintf( '<p id="%1$s">%2$s</p>', 'test', $this->module_data[ 'description' ] );
		echo $title;
	}

	/**
	 * Register setting field named "$page_name" and create related table for
	 * each submodule that needs a admin section
	 * @return void
	 */
	public function init_admin(){
		
		register_setting( 
			$this->page_name, // Option group
			$this->options_name, // Option name
			[ $this, 'wp_pronotron_options_sanitize' ]
		);

		$module_name = $this->module_data[ 'name' ];
		$module_section = "{$this->module_id}_section";

		add_settings_section( 
			$module_section, // id
			$module_name, // title
			[ $this, 'print_desc' ], // callable
			$this->page_name // page
		);

		foreach ( $this->module_data[ 'settings' ] as $setting ){

			add_settings_field(
				$setting[ 'id' ], // id
				$setting[ 'description' ], // title
				[ $this, 'create_option_row' ], // callable
				$this->page_name, // page
				$module_section, // section
				$setting, // arguments to pass callable
			);

		}

	}

	/**
	 * Creates a row in module options form table for a single option
	 * @param array option
	 * @return void
	 */
	public function create_option_row( $option ){

		// Check if option has been customized by user
		$options 	= get_option( $this->options_name );

		$option_id	= $option[ 'id' ];
		$default 	= $option[ 'default' ];

		$inputs 	= $options[ $option_id ] ?? $default; // Use defaults if option have not been customized yet
		$option 	= "{$this->options_name}[$option_id]";
		$id 		= "{$this->options_name}_{$option_id}";
		
		$reset_button = submit_button( __( 'Reset' ), 'secondary reset-settings', "reset[$option_id]", false );
		$react_root = sprintf(
			'<div id="%1$s" data-default="%2$s" data-option="%3$s" data-active="%4$s"></div>',
			$id,
			esc_html( wp_json_encode( $inputs ) ),
			esc_html( wp_json_encode( $option ) ),
			isset( $options[ $option_id ] ) ? "1" : "0"
		);

		echo $reset_button . $react_root;

	}

	/**
	 * Sanitize "module_settings_form"
	 * @param array module_settings_form
	 * @return array module_settings_form
	*/
	public function wp_pronotron_options_sanitize( $module_settings_form ){

		/**
		 * $POST -> reset[upload_sizes]
		 * $POST -> reset[image_ratios]
		 * 
		 * $module_settings_form = array(
		 * 		'upload_sizes' => array(
		 * 			'width' => number,
		 * 			'height' => number,
		 * 			'data' => array(
		 * 				'force' => on,
		 * 				'only' => on
		 * 			)
		 * 		),
		 * 		'image_ratios' => array(
		 * 			'landscape_ratios' 	=> array,
		 * 			'portrait_ratios' 	=> array
		 * 		),
		 * 		'ratio_variations' => array( 0.5, 0.3, ... )
		 * )
		*/

		/** Remove all options */
		if ( isset( $_POST[ 'reset_all' ] ) ){

			add_settings_error( 
				"{$this->options_name}", 
				'reset', 
				__( 'All settings have been resetted and not active.', 'pronotron' ), 
				'warning'
			);
			delete_option( "{$this->options_name}" );
			return false;
			
		}

		/** Remove module option  */
		if ( isset( $_POST[ 'reset' ] ) ){

			$reset_groups = array_keys( $_POST[ 'reset' ] );
			
			foreach ( $reset_groups as $module_options ){
				unset( $module_settings_form[ $module_options ] );
			}

			add_settings_error( 
				"{$this->options_name}", 
				'reset', 
				__( 'Module settings has been changed to the defaults.', 'pronotron' ), 
				'warning'
			);

			// Do not return at that point, it causes following sanitizations skipped
			// unset already clears from settings data
			// return $module_settings_form;

		}

		/** Sanitization */
		foreach ( $module_settings_form as $sub_module => $module_option ){

			if ( "image_ratios" === $sub_module ){

				/** 
				 * Remove duplicates and fixes react forms extendable fieldset array key ordering
				 * (We are hiding if any key removed by user on react form)
				 * [0], [1], [5], [6] -> [0], [1], [2], [3] ...
				 */
				$module_option[ 'landscape_ratios' ] = array_values( 
					array_unique( $module_option[ 'landscape_ratios' ] ?? [], SORT_REGULAR )
				);
				$module_option[ 'portrait_ratios' ] = array_values( 
					array_unique( $module_option[ 'portrait_ratios' ] ?? [], SORT_REGULAR )
				);
				
				/** Deep map to convert all values to integer */
				$module_option = map_deep( $module_option, 'absint' );

				$module_settings_form[ 'image_ratios' ] = $module_option;

			}

			if ( "ratio_variations" === $sub_module ){

				// Convert strings to float
				$module_option = array_values( $module_option ?? [] );
				$module_option = map_deep( $module_option, 'floatval' );

				// Remove duplicates
				$module_option = array_unique( $module_option );

				// Fix value order, big to small
				usort( $module_option, function( $first, $second ){
					return $first > $second ? -1 : 1;
				});

				$module_settings_form[ 'ratio_variations' ] = $module_option;

			}

			if ( "upload_sizes" === $sub_module ){

				// Convert strings to integers
				$module_option = map_deep( $module_option, 'absint' );
				$module_settings_form[ 'upload_sizes' ] = $module_option;

			}

		}

		return $module_settings_form;

	}

	/**
	 * WP Pronotron admin page rendering
	 * @return html
	 */
	public function wp_pronotron_settings_page(){

		if ( ! current_user_can( 'manage_options' ) ){
			return;
		}

		/**
		 * Add error/update messages
		 * Check if the user have submitted the settings
		 * WordPress will add the "settings-updated" $_GET parameter to the url
		 */
		if ( isset( $_GET[ 'settings-updated' ] ) ){

			if ( empty( get_settings_errors( "{$this->options_name}" ) ) ){

				/** add settings saved message with the class of "updated" */
				add_settings_error( "{$this->options_name}", 'wp_pronotron_message', __( 'Settings Updated', 'pronotron' ), 'updated' );
			}
		}

		/**
		 * Show error/update messages
		 */
		settings_errors( "{$this->options_name}" );

		/**
		 * Render settings page
		 */
		?>
			<div class="wrap">
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
				<form action="options.php" method="post">
					<?php

					/**
					 * Output security fields for the registered setting "wp_pronotron"
					 */
					settings_fields( "{$this->page_name}" );

					/**
					 * Output setting sections and their fields
					 * (sections are registered for "wp_pronotron", each field is registered to a specific section)
					 */
					do_settings_sections( "{$this->page_name}" );

					/**
					 * Output save settings button
					 */
					submit_button();
					submit_button( __( 'Reset All', 'pronotron' ), 'secondary', 'reset_all', false );
					?>
				</form>
			</div>
		<?php
	}

	/**
	 * Load styles & scripts for admin page
	 * @return void
	 */
	public function enqueue_styles( string $hook_suffix ){

		// if ( 'toplevel_page_wpp-art-direction-images-settings' !== $hook_suffix ) {
		// 	return;
		// }
		
		if ( "tools_page_{$this->module_id}" !== $hook_suffix ){
			return;
		}
		
		$plugin = OptionsUtils::get_constants();

		$module_id = "admin";
		$module_dir = $plugin->dir . "build/{$this->module_id}/{$module_id}";
		$module_url = $plugin->url . "build/{$this->module_id}/{$module_id}";

		DependencyUtils::load_dependency( $module_id, $module_dir, $module_url );

	}

}
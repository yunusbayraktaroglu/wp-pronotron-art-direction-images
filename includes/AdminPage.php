<?php

namespace WPPronotronArtDirectionImages;

use WPPronotronArtDirectionImages\Utils\DevelopmentUtils;
use WPPronotronArtDirectionImages\Utils\DependencyUtils;

/**
 * Admin page for WPPronotronArtDirectionImages
 * @package WPPronotronArtDirectionImages\AdminPage
 */
class AdminPage {

	private $page_name 		= "wp_pronotron";
	private $options_name 	= "wp_pronotron_options";
	public $modules;
	
	/**
	 * Initialize Admin functionality for WPPronotronArtDirectionImages
	 * @return void
	 */
	public function init( $modules ){
		
		$this->modules = $modules;

		add_action( 'admin_menu', [ $this, 'add_admin_page' ] );
		add_action( 'admin_init', [ $this, 'init_admin' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );

	}

	/**
	 * Add the options page to the WP Admin
	 * @return void
	 */
	public function add_admin_page(){

		add_menu_page(
			__( 'WP Pronotron Options', 'pronotron' ), // Page title
			__( 'WP Pronotron', 'pronotron' ), // Left menu title
			'manage_options', // Capability
			'pronotron-settings', // Url slug for page
			[ $this, 'wp_pronotron_settings_page' ], // Callable
			"data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIHZpZXdCb3g9IjAgMCA1MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cGF0aCBmaWxsPSIjYTdhYWFkIiBkPSJNMzMgMGMxMCAwIDE3IDcgMTcgMTZ2NGMwIDktNyAxNi0xNyAxNi05IDAtMTYgNS0xNiAxMnMtNCAxMi05IDEyYy00IDAtOC03LTgtMTZWMTZDMCA3IDggMCAxNyAwaDE2eiI+PC9wYXRoPjwvc3ZnPg=="
		);
		
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

		foreach ( $this->modules as $module ){

			/**
			 * If any submodule needs admin section 
			 * create a section for main module
			 */
			if ( $module->needs_admin_section ){

				$module_id 			= $module->id();
				$module_name 		= $module->name();
				$module_description = $module->description();
				$module_section 	= "{$module_id}_section";

				add_settings_section( 
					$module_section, // id
					$module_name, // title
					[ $module, 'print_description' ], // callable
					$this->page_name // page
				);

				foreach( $module->submodules as $submodule ){

					if ( method_exists( $submodule, 'submodule_options' ) ) {

						$submodule_options = $submodule->submodule_options();

						add_settings_field(
							$submodule_options[ 'id' ], // id
							$submodule_options[ 'name' ], // title
							[ $this, 'get_submodule_options_table' ], // callable
							$this->page_name, // page
							$module_section, // section
							$submodule_options, // arguments to pass callable
						);
					}
				}
			}

			/** @fix */
			// DevelopmentUtils::console_log( get_registered_settings() );
		}

	}

	/**
	 * Create table for each submodule needs admin options
	 * @param array submodule_options
	 * @return void
	 */
	public function get_submodule_options_table( $submodule_options ){

		$options 	= get_option( $this->options_name );

		$label 		= $submodule_options[ 'id' ];
		$default 	= $submodule_options[ 'default' ];
		$inputs 	= $options[ $label ] ?? $default;
		$option 	= "wp_pronotron_options[$label]";
		
		$reset_button = submit_button( __( 'Reset' ), 'secondary reset-settings', "reset[$label]", false );
		$react_root = sprintf(
			'<div id="pronotron-module-settings" data-default="%1$s" data-option="%2$s"></div>',
			esc_html( wp_json_encode( $inputs ) ),
			esc_html( wp_json_encode( $option ) )
		);

		echo $reset_button . $react_root;

	}

	/**
	 * Sanitize "wp_pronotron_options"
	 * @param array wp_pronotron_options
	 * @return array wp_pronotron_options
	*/
	public function wp_pronotron_options_sanitize( $wp_pronotron_options ){

		/**
		 * $POST -> reset[control_image_sizes]
		 * $POST -> reset[other_module_options]
		 * 
		 * $wp_pronotron_options = array(
		 * 		'control_image_sizes' => array(
		 * 			'upload_sizes' 		=> array,
		 * 			'landscape_ratios' 	=> array,
		 * 			'portrait_ratios' 	=> array
		 * 		),
		 * 		'other_module_options' => array,
		 * 		'other_module_options' => array,
		 * )
		*/

		/** Remove all options */
		if ( isset( $_POST[ 'reset_all' ] ) ) {

			delete_option( "wp_pronotron_options" );
			return false;
		}

		/** Remove module option  */
		if ( isset( $_POST[ 'reset' ] ) ) {

			$reset_groups = array_keys( $_POST[ 'reset' ] );
			
			foreach ( $reset_groups as $module_options ){
				unset( $wp_pronotron_options[ $module_options ] );
			}

			add_settings_error( 
				'wp_pronotron_options', 
				'reset', 
				__( 'Module settings has been changed to the defaults.', 'pronotron' ), 
				'updated'
			);

			return $wp_pronotron_options;
		}

		/** Sanitization */
		foreach ( $wp_pronotron_options as $sub_module => $module_option ){

			if ( "control_image_sizes" === $sub_module ){

				/** 
				 * Fix React forms extendable fieldset array key ordering
				 * [0], [1], [5], [6] -> [0], [1], [2], [3] ...
				 */
				$module_option[ 'landscape_ratios' ] = array_values( $module_option[ 'landscape_ratios' ] ?? [] );
				$module_option[ 'portrait_ratios' ] = array_values( $module_option[ 'portrait_ratios' ] ?? [] );
				
				/** Deep map to convert all values to integer */
				$module_option = map_deep( $module_option, 'absint' );

				$wp_pronotron_options[ 'control_image_sizes' ] = $module_option;
			}
		}

		return $wp_pronotron_options;

	}

	/**
	 * WP Pronotron admin page rendering
	 * @return html
	 */
	public function wp_pronotron_settings_page(){

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		/**
		 * Add error/update messages
		 * Check if the user have submitted the settings
		 * WordPress will add the "settings-updated" $_GET parameter to the url
		 */
		if ( isset( $_GET[ 'settings-updated' ] ) ) {

			if ( empty( get_settings_errors( "wp_pronotron_options" ) ) ){

				/** add settings saved message with the class of "updated" */
				add_settings_error( 'wp_pronotron_options', 'wp_pronotron_message', __( 'Settings Updated', 'pronotron' ), 'updated' );
			}
		}

		/**
		 * Show error/update messages
		 */
		settings_errors( "wp_pronotron_options" );

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
					settings_fields( 'wp_pronotron' );

					/**
					 * Output setting sections and their fields
					 * (sections are registered for "wp_pronotron", each field is registered to a specific section)
					 */
					do_settings_sections( 'wp_pronotron' );

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

		if ( 'toplevel_page_pronotron-settings' !== $hook_suffix ) {
			return;
		}

		$module_id = "admin";
		$module_dir = WPPRONOTRON_BUILD_DIR . "/{$module_id}";
		$module_url = WPPRONOTRON_BUILD_URL . "/{$module_id}";

		DependencyUtils::load_dependency( $module_id, $module_dir, $module_url );

	}

}
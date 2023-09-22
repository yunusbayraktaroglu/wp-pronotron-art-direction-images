<?php

namespace WPPronotronArtDirectionImages;

use WPPronotronArtDirectionImages;
use WPPronotronArtDirectionImages\Utils\DevelopmentUtils;
use WPPronotronArtDirectionImages\AdminPageWithoutMenu;
use WPPronotronArtDirectionImages\Utils\OptionsUtils;

abstract class Module {

	public string $module_id;
	public $submodules = array();
	public ?AdminPageWithoutMenu $module_admin_page = null;

	/**
	 * Every module must have it's module data and init_module function
	 */
	abstract function module_data(): array;
	abstract function init_module(): void;

	/** Getters */
	final public function id(): string {
		return $this->module_id;
	}

	final public function name(): string {
		return $this->module_data()[ 'name' ];
	}

	final public function description(): string {
		return $this->module_data()[ 'description' ];
	}

	final public function print_description( $args ): void {
		$title = sprintf( '<p id="%1$s">%2$s</p>', $args[ 'id' ], $this->description() );
		echo $title;
	}

	final public function get_module_options(){
		if ( isset( $this->module_admin_page ) ){
			$options = get_option( $this->module_admin_page->options_name );
			return $options;
		} else {
			return false;
		}
	}

	/**
	 * Construct module
	 * 
	 * @param string $module_id
	 * @return void
	 */
	final public function init( string $module_id ): void {

		$this->module_id = $module_id;

		/** 
		 * First, init the admin page if needed because some functionalities of admin page needs to
		 * be executed while module initialization 
		 */
		if ( isset( $this->module_data()[ 'settings' ] ) ){

			$this->module_admin_page = new AdminPageWithoutMenu( $this->id(), $this->module_data() );

		}
		
		$this->init_module();

	}

	/**
	 * Add submodule to the module
	 * 
	 * @param string $submodule_id
	 * @return void
	 */
	final public function add_submodule( string $submodule_id ): void {

		$plugin = OptionsUtils::get_constants();

		$submodule_dir = $plugin->dir . "build/{$this->module_id}/{$submodule_id}";
		$submodule_url = $plugin->url . "build/{$this->module_id}/{$submodule_id}";

		/** Submodules returns a created object when required */
		$submodule = require_once "{$submodule_dir}/index.php";

		$submodule->submodule_id = $submodule_id;
		$submodule->submodule_dir = $submodule_dir;
		$submodule->submodule_url = $submodule_url;

		$submodule->init();

		array_push( $this->submodules, $submodule );
		
	}
	
}
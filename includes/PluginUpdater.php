<?php

namespace WPPronotronArtDirectionImages;

use WPPronotronArtDirectionImages\Utils\OptionsUtils;
use WPPronotronArtDirectionImages\Utils\DevelopmentUtils;
/**
 * Plugin update functionality for private plugins based on
 * works with rest api request to cloudflare workers
 * 
 * @package WPPronotronArtDirectionImages\PluginUpdater
 */
class PluginUpdater {

	function __construct() {

		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_updates' ] );
		add_filter( 'plugins_api', [ $this, 'update_info_page' ] , 20, 3 );
		
    }

	/**
	 * Plugin update information page
	 * 
	 * @return $res
	 */
	public function update_info_page( $res, $action, $args ){

		if ( 'plugin_information' !== $action ){
			return $res;
		}

		$plugin_slug = OptionsUtils::get_constant( 'slug' );

		if ( $plugin_slug !== $args->slug ){
			return $res;
		}

		/**
		 * Fetch remote changelog list from CF workers
		 * 
		 * $version_data->version
		 * $version_data->changelog
		 */
		$change_log = "";
		$version_data = wp_remote_get( 
			'https://pronotron-plugin-changelog.yunus-bayraktaroglu.workers.dev/',
			array(
				'timeout' => 20,
				'headers' => array(
					'Plugin' => $plugin_slug,
					'Accept' => 'application/json'
				)
			)
		);

		if( 
			is_wp_error( $version_data ) 
			|| 200 !== wp_remote_retrieve_response_code( $version_data )
			|| empty( wp_remote_retrieve_body( $version_data ) )
		){
			return $res;
		} else {

			$version_data = json_decode( wp_remote_retrieve_body( $version_data ) );

			foreach( $version_data as $version ){
				$change_log .= '<h4>' . $version->version . '</h4>';
				$change_log .= '<p>' . $version->changes . '</p>';
			}
			
		}

		$res = new \stdClass();
		$res->name = "Pronotron / Art Direction Images";
		$res->slug = $plugin_slug;
		$res->sections = array(
			'changelog' => $change_log
		);
		$res->banners = array(
			'low' 	=> "https://rudrastyh.com/wp-content/uploads/updater/banner-772x250.jpg",
			'high' 	=> "https://rudrastyh.com/wp-content/uploads/updater/banner-1544x500.jpg"
		);

		return $res;

	}

	/**
	 * Query private repo for updates
	 * 
	 * remote returns
	 * $version_data->version
	 * $version_data->url
	 * $version_data->changelog
	 * 
	 * @return $transient
	 */
	public function check_updates( $transient ){

		$needs_update = false;

		// example: wp-pronotron-art-direction-images/wp-pronotron-art-direction-images.php
		$plugin_base = OptionsUtils::get_constant( 'base' );
		$plugin_slug = OptionsUtils::get_constant( 'slug' );
		$plugin_version = OptionsUtils::get_constant( 'data' )[ 'Version' ];

		/**
		 * Fetch remote plugin data from CF workers
		 */
		$version_data = wp_remote_get( 
			'https://pronotron-plugin-update.yunus-bayraktaroglu.workers.dev/',
			array(
				'timeout' => 20,
				'headers' => array(
					'Plugin' => $plugin_slug,
					'Accept' => 'application/json'
				)
			)
		);

		/**
		 * Compare versions if fetch is OK
		 */
		if( 
			is_wp_error( $version_data ) 
			|| 200 !== wp_remote_retrieve_response_code( $version_data )
			|| empty( wp_remote_retrieve_body( $version_data ) )
		){

			$needs_update = false;

		} else {

			$version_data = json_decode( wp_remote_retrieve_body( $version_data ) );

			if ( "v{$plugin_version}" !== $version_data->version ){
				$needs_update = true;
			}

		}

		//$change_log = $version_data->changelog;



		if ( $needs_update ){
	
			$custom_updates = (object) array(
				'id'            => $plugin_base,
				'plugin'        => $plugin_base,
				'slug'          => $plugin_slug,
				'new_version'   => $version_data->version,
				'package'       => $version_data->url,
				'icons'         => array(),
				'banners'       => array(),
				'banners_rtl'   => array(),
				'tested'        => '',
				'requires_php'  => '',
				'compatibility' => new \stdClass(),
				"download_url" 	=> $version_data->url,
			);
	
			// Update is available.
			// $update should be an array containing all of the fields in $item below.
			$transient->response[ $plugin_base ] = $custom_updates;
			
		} else {

			// No update is available.
			$item = (object) array(
				'id'            => $plugin_base,
				'plugin'        => $plugin_base,
				'slug'          => $plugin_slug,
				'new_version'   => $plugin_version,
				'url'           => '',
				'package'       => '',
				'icons'         => array(),
				'banners'       => array(),
				'banners_rtl'   => array(),
				'tested'        => '',
				'requires_php'  => '',
				'compatibility' => new \stdClass(),
			);

			// Adding the "mock" item to the `no_update` property is required
			// for the enable/disable auto-updates links to correctly appear in UI.
			$transient->no_update[ $plugin_base ] = $item;
		}
		
		return $transient;

	}

}
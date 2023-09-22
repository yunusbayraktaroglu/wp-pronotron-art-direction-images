<?php

namespace WPPronotronArtDirectionImages\Utils;

/**
 * Class DevelopmentUtils
 * 
 * Helper functions for development purposes
 * @package WPPronotronArtDirectionImages\Utils
 * 
 */
class DevelopmentUtils {

    /**
     * Log to browser console
     */
    public static function console_log( $data, $context = 'Debug in Console' ){
        // Buffering to solve problems frameworks, like header() in this and not a solid return.
        ob_start();

        $output  = 'console.info(\'' . $context . ':\');';
        $output .= 'console.log(' . json_encode($data) . ');';
        $output  = sprintf( '<script id="phplogger">%s</script>', $output );

        echo $output;
    }

	/**
     * Log to debug.log file
     */
	public static function debug_log( $data ){
		if ( is_array( $data ) || is_object( $data ) ){
			error_log( print_r( $data, true ) );
		} else {
			error_log( $data );
		}
	}

}
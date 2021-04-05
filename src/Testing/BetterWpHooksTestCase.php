<?php
	
	namespace BetterWpHooks\Testing;
	
	use PHPUnit\Framework\TestCase;
	
	class BetterWpHooksTestCase extends TestCase {
	
		
		public function setUpWp($wp_root_path) {
			
//			if( substr($wp_root_path, -1) == '/') {
//				$wp_root_path = substr($wp_root_path, 0, -1);
//			}
//
//			$plugin_php = $wp_root_path . '/wp-includes/plugin.php';
//
			if ( ! file_exists( $wp_root_path ) ) {

				throw new \Exception('The file: ' . $wp_root_path . ' could not be found.');

			}
			
			require_once $wp_root_path;
			
			$GLOBALS['wp_filter']         = [];
			$GLOBALS['wp_actions']        = [];
			$GLOBALS['wp_current_filter'] = [];
			
			
		}
		
		public function tearDownWp() {
			
			$GLOBALS['wp_filter']         = [];
			$GLOBALS['wp_actions']        = [];
			$GLOBALS['wp_current_filter'] = [];
			
		}
		
		
	}
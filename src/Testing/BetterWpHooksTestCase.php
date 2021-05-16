<?php
	
	namespace BetterWpHooks\Testing;
	
	use PHPUnit\Framework\TestCase;
	
	class BetterWpHooksTestCase extends TestCase {
	
		
		public function setUpWp(string $custom_file = null) {


            $plugin_php = $custom_file ?? dirname( __DIR__, 2 ) . '/vendor/calvinalkan/wordpress-hook-api-clone/plugin.php';

			if ( ! file_exists( $plugin_php ) ) {

				throw new \Exception('The file: ../vendor/calvinalkan/wordpress-hook-api-clone/plugin.php does not exists.');

			}

			if ( ! class_exists(\WP_Hook::class) ) {

                require_once $plugin_php;


            }

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
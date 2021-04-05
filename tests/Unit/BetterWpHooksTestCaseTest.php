<?php
	
	namespace Tests\Unit;
	
	use Codeception\AssertThrows;
	use PHPUnit\Framework\Assert;
	use PHPUnit\Framework\AssertionFailedError;
	use PHPUnit\Framework\TestCase;
	use BetterWpHooks\Testing\BetterWpHooksTestCase;
	

	class BetterWpHooksTestCaseTest extends TestCase {
		
		use AssertThrows;
		
		private $test_case;
		
		public $root_path;
		
		protected function setUp(): void {
			
			parent::setUp();
			
			$plugin_php = dirname( __DIR__, 2 ) . '/vendor/calvinalkan/wordpress-hook-api-clone/plugin.php';
			
			
			$this->root_path = $plugin_php;
			
			$this->test_case = new BetterWpHooksTestCase();
			
		}
		
		
		/** @test */
		public function the_class_wp_hook_exists_when_loaded_the_test_case () {
			
			
			$this->test_case->setUpWp($this->root_path);
			
			self::assertTrue(class_exists(\WP_Hook::class), 'The class WP_Hook was not loaded');
			
		}
		
		
		/** @test */
		public function the_path_to_plugin_php_can_be_set() {
			
			try {
				
				$this->test_case->setUpWp($this->root_path);
				
				Assert::assertTrue(true, 'Exception handled');
				
			} catch ( \Throwable $e ) {
				
				throw new AssertionFailedError( $e->getMessage() . PHP_EOL .  $e->getTraceAsString() );
				
				
			}
			
			
		}
		
		
		/** @test */
		public function an_exception_is_thrown_when_the_file_cant_be_found() {
			
			try {
				
				$this->test_case->setUpWp('/wrong-file');
				
				$this->fail('Exception not thrown');
				
			} catch ( \Throwable $e ) {
				
				self::assertEquals('The file: /wrong-file could not be found.', $e->getMessage
				());
				
			}
			
			
		}
		
		
		
		
		/** @test */
		public function assert_that_globals_are_emptied_out_before_setUp() {
			
			
			$this->test_case->setUpWp($this->root_path);
			
			$this->assertEmpty( $GLOBALS['wp_filter'] );
			$this->assertEmpty( $GLOBALS['wp_actions'] );
			$this->assertEmpty( $GLOBALS['wp_current_filter'] );
			
		}
		
		/** @test */
		public function assert_that_globals_are_emptied_out_in_tear_down () {
			
			$this->test_case->setUpWp($this->root_path);
			
			add_action('foo', function () { return 'foo';}, 10, 1);
			add_filter('bar', function () { return 'bar';}, 10, 1);
			
			
			$this->test_case->tearDownWp();
			
			$this->assertEmpty( $GLOBALS['wp_filter'] );
			$this->assertEmpty( $GLOBALS['wp_actions'] );
			$this->assertEmpty( $GLOBALS['wp_current_filter'] );
			
		}
		
	
	}
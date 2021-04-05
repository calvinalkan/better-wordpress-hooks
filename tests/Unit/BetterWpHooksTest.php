<?php
	
	namespace Tests\Unit;
	
	use BetterWpHooks\Dispatchers\WordpressDispatcher;
	use BetterWpHooks\Exceptions\ConfigurationException;
	use BetterWpHooks\Exceptions\TestException;
	use BetterWpHooks\Exceptions\UnremovableListenerException;
	use BetterWpHooks\Testing\FakeDispatcher;
	use Codeception\AssertThrows;
	use PHPUnit\Framework\TestCase;
	use SniccoAdapter\BaseContainerAdapter;
	use Tests\Exceptions\DidAction;
	use Tests\TestListeners\ActionListener;
	use Tests\TestEvents\FilterableEvent;
	use Tests\TestStubs\DifferentContainer;
	use Tests\TestStubs\Plugin1;
	use Tests\TestStubs\Plugin2;
	
	
	class BetterWpHooksTest extends TestCase {
		
		use AssertThrows;
		
		public const default_priority = 10;
		
		protected function setUp(): void {
			
			
			parent::setUp();
			
			
			$plugin_php = dirname( __DIR__, 2 ) . '/vendor/calvinalkan/wordpress-hook-api-clone/plugin.php';
			
			require_once $plugin_php;
			
			$this->assertEmpty( $GLOBALS['wp_filter'] );
			$this->assertEmpty( $GLOBALS['wp_actions'] );
			$this->assertEmpty( $GLOBALS['wp_current_filter'] );
			
			
		}
		
		protected function tearDown(): void {
			
			parent::tearDown();
			
			$this->reset();
			
			self::assertNull( Plugin1::getInstance() );
			self::assertNull( Plugin2::getInstance() );
			
		}
		
		/**
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 * Configuration
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 */
		
		/** @test */
		public function two_instances_can_be_created_with_different_container_instances() {
			
			$container1 = new BaseContainerAdapter();
			$container2 = new BaseContainerAdapter();
			
			Plugin1::make( $container1 );
			Plugin2::make( $container2 );
			
			self::assertSame( $container1, Plugin1::getInstance()->container() );
			self::assertSame( $container2, Plugin2::getInstance()->container() );
			self::assertNotSame( Plugin1::getInstance()->container(), Plugin2::getInstance()->container() );
			self::assertNotSame( Plugin1::getInstance(), Plugin2::getInstance() );
			
			
		}
		
		/** @test */
		public function its_possible_two_use_completely_different_container_implementations_separately() {
			
			$container1 = new BaseContainerAdapter();
			$container2 = new DifferentContainer();
			
			Plugin1::make( $container1 );
			Plugin2::make( $container2 );
			
			self::assertInstanceOf( BaseContainerAdapter::class, Plugin1::container() );
			self::assertInstanceOf( DifferentContainer::class, Plugin2::container() );
			
			
		}
		
		/** @test */
		public function the_dispatcher_instance_can_be_swapped_out_for_testing_purposes() {
			
			$this->newPlugin1();
			
			self::assertInstanceOf( WordpressDispatcher::class, Plugin1::dispatcher() );
			
			Plugin1::fake();
			
			self::assertInstanceOf( FakeDispatcher::class, Plugin1::dispatcher() );
			
			
		}
		
		/** @test */
		public function an_instance_can_be_created_via_the_facade_without_passing_arguments() {
			
			Plugin1::make();
			
			self::assertInstanceOf( WordpressDispatcher::class, Plugin1::dispatcher() );
			self::assertInstanceOf( BaseContainerAdapter::class, Plugin1::container() );
			
			
		}
		
		/** @test */
		public function the_instance_can_be_configured_as_a_fluent_api() {
			
			$map = [
				
				'init' => [ Event1::class ],
			
			];
			
			$listen = [
				
				Event1::class => [
					
					function ( Event1 $event_1 ) {
						
						throw new TestException( $event_1->foobar );
						
					},
				
				],
			
			];
			
			Plugin1::make()->map( $map )->listeners( $listen )->boot();
			
			$this->assertThrowsWithMessage( TestException::class, 'foobar', function () {
				
				
				Event1::dispatch( [ 'foo', 'bar' ] );
				
			} );
			
			
		}
		
		
		/**
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 * Exceptions
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 */
		
		/** @test */
		public function an_exception_gets_thrown_if_any_facade_methods_get_called_on_the_base_class_before_its_booted() {
			
			
			$this->assertThrows( ConfigurationException::class, function () {
				
				Plugin1::container();
				
				
			} );
			
			$this->assertThrows( ConfigurationException::class, function () {
				
				Plugin1::dispatcher();
				
				
			} );
			
			$this->assertThrows( ConfigurationException::class, function () {
				
				Plugin1::listeners( [] );
				
				
			} );
			
			
		}
		
		/** @test */
		public function an_exception_gets_thrown_if_dispatch_gets_called_on_the_base_class_before_its_booted() {
			
			$this->expectException( ConfigurationException::class );
			
			Plugin1::dispatch( 'event1', 'foo' );
			
			
		}
		
		/** @test */
		public function an_exception_gets_thrown_when_invalid_data_gets_provided_for_event_mapping() {
			
			$this->newPlugin1();
			
			Plugin1::map( [ 'invalid ' ] );
			
			$this->assertThrowsWithMessage( ConfigurationException::class,
				'Invalid Data was provided for event-mapping: Invalid argument supplied for foreach()', function () {
					
					Plugin1::boot();
					
				} );
			
			
		}
		
		/** @test */
		public function an_exception_gets_thrown_when_invalid_data_gets_provided_for_event_listeners() {
			
			$this->newPlugin1();
			
			Plugin1::listeners( [ 'invalid' => 'invalid' ] );
			
			$this->assertThrowsWithMessage( ConfigurationException::class,
				'Invalid Data was provided for a listener: Invalid argument supplied for foreach()', function () {
					
					Plugin1::boot();
					
				} );
			
			
		}
		
		
		/**
		 * @test
		 *
		 * @noinspection PhpUndefinedMethodInspection
		 */
		public function an_exception_gets_thrown_when_an_undefined_static_method_is_called() {
			
			$this->newPlugin1();
			
			$this->assertThrowsWithMessage( \BadMethodCallException::class,
				'Method ' . get_class( Plugin1::getInstance() ) . '::unresolvableMethod() does not exist.', function () {
					
					Plugin1::unresolvableMethod();
					
				} );
			
			
		}
		
		/**
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 * Creating Listeners and Mappers
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 */
		
		/** @test */
		public function wordpress_and_plugin_hooks_can_be_mapped_to_custom_events() {
			
			
			$this->newPlugin1();
			
			self::assertFalse( has_filter( 'init', [ ActionListener::class, 'mapEvent' ] ) );
			self::assertFalse( has_filter( 'admin_init', [ ActionListener::class, 'mapEvent' ] ) );
			
			Plugin1::map( [
				
				'init' => [
					
					ActionListener::class,
				],
				
				'admin_init' => [
					
					[ ActionListener::class, 20 ],
				
				],
			
			] );
			
			Plugin1::boot();
			
			self::assertEquals( self::default_priority, has_filter( 'init', [ ActionListener::class, 'mapEvent' ] ) );
			self::assertEquals( 20, has_filter( 'admin_init', [ ActionListener::class, 'mapEvent' ] ) );
			
			self::assertEquals( 99, $GLOBALS['wp_filter']['init']->callbacks[10]['Tests\TestListeners\ActionListener::mapEvent']['accepted_args'] );
			
			
		}
		
		/** @test */
		public function listeners_can_be_registered_for_custom_events() {
			
			
			$this->newPlugin1();
			
			$d = Plugin1::dispatcher();
			
			self::assertFalse( $d->hasListenerFor( ActionListener::class, 'event1' ) );
			self::assertFalse( $d->hasListenerFor( ActionListener::class . '@foobar', 'event2' ) );
			
			Plugin1::listeners( [
				
				'event1' => [
					
					ActionListener::class,
				
				],
				
				'event2' => [
					
					[ ActionListener::class, 'foobar' ],
				
				],
			
			] );
			
			Plugin1::boot();
			
			self::assertTrue( $d->hasListenerFor( ActionListener::class, 'event1' ) );
			self::assertTrue( $d->hasListenerFor( ActionListener::class . '@foobar', 'event2' ) );
			
			
		}
		
		/**
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 * Dispatching Actions
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 */
		
		/** @test */
		public function the_facade_can_dispatch_regular_events_with_an_event_name_and_an_array_of_args() {
			
			$this->newPlugin1();
			
			Plugin1::listeners( [
				
				'event1' => [
					
					function ( $foo, $bar ) {
						
						throw new TestException( $foo . $bar );
						
						
					},
				
				],
			
			] );
			
			Plugin1::boot();
			
			$this->expectExceptionMessage( 'foobar' );
			
			Plugin1::dispatch( 'event1', [ 'foo', 'bar' ] );
			
			
		}
		
		/** @test */
		public function the_facade_can_dispatch_regular_events_with_an_event_name_and_various_params() {
			
			$this->newPlugin1();
			
			Plugin1::listeners( [
				
				'event1' => [
					
					function ( $foo, $bar, $baz ) {
						
						throw new TestException( $foo . $bar . $baz );
						
						
					},
				
				],
			
			] );
			
			Plugin1::boot();
			
			$this->expectExceptionMessage( 'foobarbaz' );
			
			Plugin1::dispatch( 'event1', 'foo', 'bar', 'baz' );
			
			
		}
		
		/** @test */
		public function the_facade_can_create_and_dispatch_object_events() {
			
			$this->newPlugin1();
			
			Plugin1::listeners( [
				
				Event1::class => [
					
					function ( Event1 $event ) {
						
						throw new TestException( $event->foobar );
						
						
					},
				
				],
			
			] );
			
			Plugin1::boot();
			
			$this->expectExceptionMessage( 'foobar' );
			
			Plugin1::dispatch( new Event1( 'foo', 'bar' ), 'does', 'not', 'matter' );
			
			
		}
		
		/** @test */
		public function object_event_can_be_dispatched_directly() {
			
			$this->newPlugin1();
			
			Plugin1::listeners( [
				
				Event1::class => [
					
					function ( Event1 $event ) {
						
						throw new TestException( $event->foobar );
						
						
					},
				
				],
			
			] );
			
			Plugin1::boot();
			
			$this->expectExceptionMessage( 'foobarbaz' );
			
			Event1::dispatch( [ 'foo', 'barbaz' ], 'doest', 'matter', 'what', 'comes', 'here' );
			
			
		}
		
		/** @test */
		public function an_event_can_be_dispatched_conditionally_by_event_objects() {
			
			
			$this->newPlugin1();
			
			$_SERVER['dispatch'] = TRUE;
			
			Plugin1::listeners( [
				
				Event1::class => [
					
					function ( Event1 $event ) {
						
						throw new TestException( $event->foobar );
						
						
					},
				
				],
			
			] );
			
			Plugin1::boot();
			
			$this->assertThrowsWithMessage( TestException::class, 'foobar', function () {
				
				Event1::dispatchIf( $_SERVER['dispatch'] ?? FALSE, 'foo', 'bar' );
				
			} );
			
			$this->newPlugin1();
			
			$_SERVER['dispatch'] = TRUE;
			
			Plugin1::listeners( [
				
				Event1::class => [
					
					function ( Event1 $event ) {
						
						throw new TestException( $event->foobar );
						
						
					},
				
				],
			
			] );
			
			Plugin1::boot();
			
			$this->assertThrowsWithMessage( TestException::class, 'foobar', function () {
				
				Event1::dispatchIf( $_SERVER['dispatch'] ?? FALSE, [ 'foo', 'bar' ] );
				
			} );
			
			$this->newPlugin1();
			
			$_SERVER['dispatch'] = FALSE;
			
			Plugin1::listeners( [
				
				Event1::class => [
					
					function ( Event1 $event ) {
						
						throw new TestException( $event->foobar );
						
						
					},
				
				],
			
			] );
			
			Plugin1::boot();
			
			$this->assertDoesNotThrow( TestException::class, function () {
				
				Event1::dispatchIf( $_SERVER['dispatch'] ?? FALSE, [ 'foo', 'bar' ] );
				
				
			} );
			
			
			unset($_SERVER['dispatch']);
			
		}
		
		/** @test */
		public function an_event_object_can_be_dispatched_unless_a_given_condition_is_true() {
			
			
			$this->newPlugin1();
			
			$_SERVER['dispatch'] = FALSE;
			
			Plugin1::listeners( [
				
				Event1::class => [
					
					function ( Event1 $event ) {
						
						throw new TestException( $event->foobar );
						
						
					},
				
				],
			
			] );
			
			Plugin1::boot();
			
			$this->assertThrowsWithMessage( TestException::class, 'foobar', function () {
				
				Event1::dispatchUnless( $_SERVER['dispatch'], 'foo', 'bar' );
				
			} );
			
			$this->newPlugin1();
			
			$_SERVER['dispatch'] = TRUE;
			
			Plugin1::listeners( [
				
				Event1::class => [
					
					function ( Event1 $event ) {
						
						throw new TestException( $event->foobar );
						
						
					},
				
				],
			
			] );
			
			Plugin1::boot();
			
			$this->assertDoesNotThrow( TestException::class, function () {
				
				Event1::dispatchUnless( $_SERVER['dispatch'], 'foo', 'bar' );
				
			} );
			
			unset($_SERVER['dispatch']);
			
			
		}
		
		/** @test */
		public function an_exception_gets_thrown_if_the_facade_tries_to_conditionally_dispatch_events() {
			
			$this->newPlugin1();
			
			$_SERVER['dispatch'] = TRUE;
			
			Plugin1::listeners( [
				
				Event1::class => [
					
					function ( Event1 $event ) {
						
						throw new TestException( $event->foobar );
						
						
					},
				
				],
			
			] );
			
			Plugin1::boot();
			
			$this->assertThrowsWithMessage( \BadMethodCallException::class, 'Doing it wrong: The Plugin facade is not meant to be used for conditional dispatching. You should use event objects instead', function () {
				
				Plugin1::dispatchIf( $_SERVER['dispatch'], 'foo', 'bar' );
				
			} );
			
			$this->assertThrowsWithMessage( \BadMethodCallException::class, 'Doing it wrong: The Plugin facade is not meant to be used for conditional dispatching. You should use event objects instead', function () {
				
				Plugin1::dispatchUnless( FALSE, 'foo', 'bar' );
				
			} );
			
			unset($_SERVER['dispatch']);
			
			
		}
		
		
		/**
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 * Dispatching Filters
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 */
		
		/** @test */
		public function the_facade_can_dispatch_filters() {
			
			$this->newPlugin1();
			
			$closure1 = function ( $foo ) {
				
				return $foo . 'bar';
				
			};
			
			$closure2 = function ( $foobar ) {
				
				return $foobar . 'biz';
				
			};
			
			Plugin1::listeners( [
				
				FilterableEvent::class => [
					
					$closure1,
					$closure2,
				],
			
			] );
			
			Plugin1::boot();
			
			$result = Plugin1::dispatch( FilterableEvent::class, 'foo' );
			
			$this->assertSame( 'foobarbiz', $result );
			
		}
		
		/** @test */
		public function the_facade_can_dispatch_standard_non_object_filters_with_default_return_values() {
			
			
			$this->newPlugin1();
			
			$result1 = Plugin1::dispatch( FilterableEvent::class, 'foo', 'bar' );
			
			$this->assertSame( 'foo', $result1 );
			
			$result2 = Plugin1::dispatch( FilterableEvent::class, [ 'foo', 'bar' ] );
			
			$this->assertSame( 'foo', $result2 );
			
			
		}
		
		/** @test */
		public function the_facade_can_dispatch_event_objects_that_return_a_default_value_if_no_listener_is_present() {
			
			$this->newPlugin1();
			
			$result1 = Plugin1::dispatch( new FilterableEvent( 'foo' ) );
			
			$this->assertSame( 'foo', $result1 );
			
			Plugin1::listen( FilterableEvent::class, function ( FilterableEvent $event ) {
				
				return $event->foo . 'bar';
				
			} );
			
			$result2 = Plugin1::dispatch( new FilterableEvent( 'foo' ) );
			
			$this->assertSame( 'foobar', $result2 );
			
			$result3 = Filterable::dispatch( [ 'foo', 'bar' ] );
			
			$this->assertSame( 'foobar', $result3 );
			
			
		}
		
		
		/**
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 * Public Dispatcher API
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 */
		
		/** @test */
		public function the_facade_can_forget_events() {
			
			
			$this->newPlugin1();
			
			$_SERVER['dispatch'] = FALSE;
			
			Plugin1::listeners( [
				
				Event1::class => [
					
					'closure' => function ( Event1 $event ) {
						
						throw new TestException( $event->foobar );
						
						
					},
				
				],
			
			] );
			
			Plugin1::boot();
			
			Plugin1::forgetOne( Event1::class, 'closure' );
			
			$this->assertDoesNotThrow( TestException::class, function () {
				
				Plugin1::dispatch( new Event1( 'foo', 'bar' ) );
				
			} );
			
			
		}
		
		/** @test */
		public function the_facade_can_listen_to_a_single_event_without_bootstrapping() {
			
			$this->newPlugin1();
			
			Plugin1::listen( 'event1', ActionListener::class );
			
			$this->assertThrowsWithMessage( DidAction::class, 'Tests\TestListeners\ActionListener@handleEvent => foo_handled', function () {
				
				Plugin1::dispatch( 'event1', 'foo' );
				
			} );
			
			
		}
		
		/** @test */
		public function the_facade_can_mark_a_listener_as_unremovable() {
			
			$this->newPlugin1();
			
			Plugin1::unremovable( 'event1', ActionListener::class );
			
			$this->assertThrowsWithMessage(
				UnremovableListenerException::class,
				'The Hook you tried to remove was marked as unremovable. You tried to remove the Hook: Tests\TestListeners\ActionListener@handleEvent',
				function () {
					
					Plugin1::forgetOne( 'event1', ActionListener::class );
					
				} );
			
			
		}
		
		/** @test */
		public function the_facade_can_get_information_about_the_registered_listeners() {
			
			
			$this->newPlugin1();
			
			Plugin1::listeners( [
				
				'event1' => [
					
					ActionListener::class,
				
				],
				
				'event2' => [
					
					ActionListener::class . '@foobar',
				
				],
			
			] );
			
			self::assertFalse( Plugin1::hasListeners( 'event1' ) );
			self::assertFalse( Plugin1::hasListeners( 'event2' ) );
			
			Plugin1::boot();
			
			self::assertTrue( Plugin1::hasListenerFor( ActionListener::class, 'event1' ) );
			self::assertTrue( Plugin1::hasListenerFor( ActionListener::class . '@foobar', 'event2' ) );
			
			
		}
		
		/**
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 * Wordpress interaction
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 */
		
		/** @test */
		public function a_wordpress_action_with_no_parameters_gets_mapped_correctly() {
			
			$this->newPlugin1();
			
			Plugin1::map( [
				
				'init' => [ WpLoaded::class ],
			
			] );
			
			Plugin1::listeners( [
				
				WpLoaded::class => [
					
					function () {
						
						throw new TestException( 'WpLoaded without Params' );
						
					},
				
				],
			
			] );
			
			Plugin1::boot();
			
			$this->assertThrowsWithMessage(
				TestException::class,
				'WpLoaded without Params',
				function () {
					
					do_action( 'init' );
					
				} );
			
			
		}
		
		/** @test */
		public function a_wordpress_action_with_several_parameters_gets_mapped_correctly() {
			
			$this->newPlugin1();
			
			Plugin1::map( [
				
				'current_screen' => [ CurrentScreen::class ],
			
			] );
			
			Plugin1::listeners( [
				
				CurrentScreen::class => [
					
					function ( CurrentScreen $screen ) {
						
						$method = $screen->method;
						$url    = $screen->url;
						
						$message = $method . $url;
						
						throw new TestException( $message );
						
					},
				
				],
			
			] );
			
			Plugin1::boot();
			
			$this->assertThrowsWithMessage(
				TestException::class,
				'GET:https://calvin-alkan.de/',
				function () {
					
					do_action( 'current_screen', 'GET:', 'https://calvin-alkan.de/' );
					
				} );
			
		}
		
		/** @test */
		public function a_wordpress_action_with_an_array_as_parameters_gets_mapped_correctly() {
			
			
			$this->newPlugin1();
			
			Plugin1::map( [
				
				'array_action' => [ ArrayAction::class ],
			
			] );
			
			Plugin1::listeners( [
				
				ArrayAction::class => [
					
					function ( ArrayAction $array ) {
						
						
						$message = implode( ':', $array->array_message );
						
						throw new TestException( $message );
						
					},
				
				],
			
			] );
			
			Plugin1::boot();
			
			$this->assertThrowsWithMessage(
				TestException::class,
				'GET:https://calvin-alkan.de/',
				function () {
					
					do_action( 'array_action', [ 'GET', 'https://calvin-alkan.de/' ] );
					
				} );
			
			
		}
		
		/** @test */
		public function a_wordpress_filter_with_one_parameter_gets_mapped_correctly() {
			
			$this->newPlugin1();
			
			Plugin1::map( [
				
				'blog_title' => [ BlogTitle::class ],
			
			] );
			
			Plugin1::listeners( [
				
				BlogTitle::class => [
					
					function ( BlogTitle $blog_title ) {
						
						return $blog_title->title .= '$';
						
					},
				
				],
			
			] );
			
			Plugin1::boot();
			
			
			add_filter('blog_title', function ( string $title ) {
				
				return $title .= '!';
				
			}, 20, 1 );
			
			$title = apply_filters('blog_title', 'My Site');
			
			$this->assertEquals('My Site$!', $title);
			
			
		}
		
		/** @test */
		public function a_wordpress_filter_with_several_parameters_gets_mapped_correctly () {
			
			$this->newPlugin1();
			
			Plugin1::map( [
				
				'checkout_price' => [ CheckoutPrice::class ],
			
			] );
			
			Plugin1::listeners( [

				CheckoutPrice::class => [

					function ( CheckoutPrice $checkout_price ) {

						return $checkout_price->label . '€';

					},

				],

			] );
			
			Plugin1::boot();
			
			
			add_filter('checkout_price', function ( $label, $currency ) {
				
				return $label . '$';
				
			}, 9, 2 );
			
			$result = apply_filters('checkout_price', 'Pay now 100', '!');
			
			$this->assertEquals('Pay now 100$€', $result);
		
		
		}
		
		/** @test */
		public function a_wordpress_filter_with_array_value_gets_mapped_correctly () {
			
			$this->newPlugin1();
			
			Plugin1::map( [
				
				'admin_users' => [ AdminUsers::class ],
			
			] );
			
			Plugin1::listeners( [
				
				AdminUsers::class => [
					
					function ( AdminUsers $users ) {
						
						$admins = $users->admins;
						
						$admins[] = 'foobaruser';
				
						return $admins;
						
					},
				
				],
			
			] );
			
			Plugin1::boot();
			
			
			$admins = apply_filters('admin_users', ['calvinalkan']);
			
			$this->assertEquals(['calvinalkan', 'foobaruser'], $admins);
			
			
		}
		
		/** @test */
		public function a_mapped_filter_with_no_registered_listeners_stays_untouched () {
			
			
			$this->newPlugin1();
			
			Plugin1::map( [
				
				'admin_users' => [ AdminUsers::class ],
			
			] );
			
			Plugin1::listeners( [
				
				//
				
			] );
			
			Plugin1::boot();
			
			$admins = apply_filters('admin_users', ['calvinalkan']);
			
			$this->assertEquals(['calvinalkan'], $admins);
			
			
			
		}
		
		
		
		private function newPlugin1() {
			
			$container1 = new BaseContainerAdapter();
			
			Plugin1::make( $container1 );
			
		}
		
		private function newPlugin2() {
			
			$container2 = new BaseContainerAdapter();
			
			Plugin2::make( $container2 );
			
		}
		
		private function reset(): void {
			
			
			$GLOBALS['wp_filter']         = [];
			$GLOBALS['wp_actions']        = [];
			$GLOBALS['wp_current_filter'] = [];
			$_SERVER['dispatch']          = NULL;
			
			Plugin1::setInstance( NULL );
			Plugin2::setInstance( NULL );
			
		}
		
	}
	
	class Event1 extends Plugin1 {
		
		public  $foobar;
		
		public function __construct( string $foo, string $bar ) {
			
			$this->foobar = $foo . $bar;
			
		}
		
		
	}
	
	class Filterable extends Plugin1 {
		
		
		public  $foobar;
		
		public function __construct( string $foo, string $bar ) {
			
			$this->foobar = $foo . $bar;
			
		}
		
		
		public function default() {
			
			return $this->foobar;
			
		}
		
	}
	
	class WpLoaded extends Plugin1 {
	
	
	}
	
	class CurrentScreen extends Plugin1 {
		
		
		public  $method;
		public  $url;
		
		public function __construct( $method, $url ) {
			
			$this->method = $method;
			$this->url    = $url;
			
		}
		
	}
	
	class ArrayAction extends Plugin1 {
		
		
		public  $array_message;
		
		public function __construct( array $array_message ) {
			
			$this->array_message = $array_message;
		}
		
	}
	
	class BlogTitle extends Plugin1 {
		
		
		public  $title;

		public function __construct( string $title ) {
			
			$this->title = $title;
		}
		
		
		public function default() {
			
			return 'test';
			
		}
		
		
		
	}
	
	class CheckoutPrice extends Plugin1 {
		
		
		public  $label;
		public  $currency;
		
		public function __construct( string $label, string $currency ) {
			
			$this->label = $label;
			$this->currency = $currency;
			
		}
		
		
		public function default() {
			
			return 'default';
			
		}
		
	}
	
	class AdminUsers extends Plugin1 {
		
		
		public $admins;
		
		public function __construct( array $admins ) {
			
			$this->admins = $admins;
		}
		
		public function default() {
		
			
			return $this->admins;
		
		
		}
		
	}
<?php
	
	namespace Tests\Unit;
	
	use BetterWpHooks\Dispatchers\WordpressDispatcher;
	use BetterWpHooks\Exceptions\DuplicateListenerException;
	use BetterWpHooks\Exceptions\InvalidListenerException;
	use BetterWpHooks\Exceptions\TestException;
	use BetterWpHooks\Exceptions\UnremovableListenerException;
	use BetterWpHooks\ListenerFactory;
    use BetterWpHooks\Testing\BetterWpHooksTestCase;
    use BetterWpHooks\WordpressApi;
	use Codeception\AssertThrows;
    use Illuminate\Support\Facades\Event;
    use SniccoAdapter\BaseContainerAdapter;
	use stdClass;
	use Tests\CustomAssertions;
	use Tests\TestDependencies\ComplexListener;
	use Tests\TestDependencies\ComplexMethodDependency;
	use Tests\TestEvents\ConditionalEvent;
    use Tests\TestEvents\EventFakeStub;
    use Tests\TestEvents\EventWithDefaultLogic;
    use Tests\TestEvents\EventWithDefaultNoTypeHit;
    use Tests\TestEvents\EventWithDefaults;
    use Tests\TestEvents\FilterableWithoutDefault;
	use Tests\TestListeners\ActionListener;
	use Exception;
	use Tests\TestListeners\ConditionalListener;
	use Tests\TestEvents\FilterableEvent;
	use Tests\TestListeners\ImpossibleListener;
	use Tests\TestListeners\ListenerWithReturn2;
	use Tests\TestListeners\ListenerWithReturn3;
	use Tests\TestListeners\ListenerWithReturnConditional;
	use Tests\TestListeners\StdClassListener;
	use Tests\TestListeners\StopListener;
	use Tests\TestListeners\ThrowExceptionListener;
	
	
	class WordpressDispatcherTest extends BetterWpHooksTestCase {
		
		use AssertThrows;
		use CustomAssertions;
		
		private $dispatcher;
		
		private $wp;
		
		
		protected function setUp(): void {
			
			
			parent::setUp();
			
			$this->setUpWp(VENDOR_DIR);
			
			$this->wp         = new WordpressApi();
			$this->dispatcher = new WordpressDispatcher(
				
				new ListenerFactory( new BaseContainerAdapter() ),
				$this->wp
			
			);
			
		}
		
		
		protected function tearDown(): void {
			
			parent::tearDown();
			
			$this->reset();
			
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
		 *
		 * Creating Events
		 *
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
		public function a_listener_can_be_created_as_an_object() {
			
			$action_listener = new ActionListener();
			
			$this->dispatcher->listen( 'event1', $action_listener );
			
			$this->dispatchAndAssertAction( 'event1', 'foo', [
				ActionListener::class . '@handleEvent',
				'foo_handled',
			] );
			
			
		}
		
		
		/** @test */
		public function a_listener_can_be_created_with_the_AT_sign() {
			
			
			$this->dispatcher->listen( 'event1', ActionListener::class . '@foobar' );
			
			$this->dispatchAndAssertAction( 'event1', 'foo', [ ActionListener::class . '@foobar', 'foobar' ] );
			
			
		}
		
		
		/** @test */
		public function a_listener_can_be_created_with_a_custom_key() {
			
			$this->dispatcher->listen( 'event1', [ 'custom_key' => ActionListener::class . '@foobar' ] );
			
			$this->assertHasListener( 'custom_key', 'event1' );
			
			$this->dispatcher->listen( 'event2', [ 'custom_key' => [ ActionListener::class, 'foobar' ] ] );
			
			$this->assertHasListener( 'custom_key', 'event2' );
			
		}
		
		
		/** @test */
		public function a_listener_can_be_created_without_any_method_definition_and_the_default_method_will_be_called() {
			
			
			$this->dispatcher->listen( 'event1', ActionListener::class );
			
			$this->dispatchAndAssertAction( 'event1', 'foo', [
				ActionListener::class . '@handleEvent',
				'foo_handled',
			] );
			
			$this->reset();
			
			$this->dispatcher->listen( 'event1', [ ActionListener::class ] );
			
			$this->dispatchAndAssertAction( 'event1', 'foo', [
				ActionListener::class . '@handleEvent',
				'foo_handled',
			] );
			
			
		}
		
		/** @test */
		public function a_listener_can_be_created_as_an_array() {
			
			$this->dispatcher->listen( 'event1', [ ActionListener::class, 'foobar' ] );
			
			$this->dispatchAndAssertAction( 'event1', 'foo', [ ActionListener::class . '@foobar', 'foobar' ] );
			
		}
		
		/** @test */
		public function a_listener_can_be_an_anonymous_function() {
			
			
			$closure = function ( $foo ) {
				
				$this->doClosureAction( $foo . 'bar' );
				
			};
			
			$this->dispatcher->listen( 'event1', $closure );
			
			$this->dispatchAndAssertClosure( 'event1', 'foo', [ $closure, 'foobar' ] );
			
			
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
		 *
		 * Finding Events
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 *
		 */
		
		public function how_to_search_for_a_listener_by_alias(): array {
			
			return [
				
				[ [ [ new ActionListener(), 'foobar' ], 'foobar' ] ],
				[ [ [ ActionListener::class, '*' ], 'foobar' ] ],
				[ [ ActionListener::class . '@*', 'foobar' ] ],
				[ [ ActionListener::class . '*', 'foobar' ] ],
				[ [ [ ActionListener::class, 'foobar' ], 'foobar' ] ],
				[ [ ActionListener::class . '@handleEvent', 'handleEvent' ] ],
				[ [ ActionListener::class, 'handleEvent' ] ],
				[ [ new ActionListener(), 'handleEvent' ] ],
				[ [ ActionListener::class, 'foobar' ] ],
			
			];
			
		}
		
		
		/**
		 *
		 * @dataProvider how_to_search_for_a_listener_by_alias
		 *
		 * @test
		 */
		public function it_can_be_checked_that_a_specific_listener_is_listening_to_an_event( array $class_callable ) {
			
			
			$this->assertFalse( $this->dispatcher->hasListenerFor( $class_callable[0], 'event1' ) );
			
			$this->dispatcher->listen( 'event1', [ ActionListener::class, $class_callable[1] ] );
			
			$this->assertTrue( $this->dispatcher->hasListenerFor( $class_callable[0], 'event1' ) );
			
		}
		
		
		/** @test */
		public function it_can_be_checked_that_an_event_has_any_listeners() {
			
			$this->assertFalse( $this->dispatcher->hasListeners( 'event1' ) );
			
			$this->dispatcher->listen( 'event1', ListenerWithReturnConditional::class . '@bar' );
			
			$this->assertTrue( $this->dispatcher->hasListeners( 'event1' ) );
			
		}
		
		
		/** @test */
		public function a_closure_can_be_found_after_registration() {
			
			$closure = function ( $foo ) {
				
				return $foo;
				
			};
			
			$this->assertNoListener( $closure, 'event' );
			
			$this->dispatcher->listen( 'event', $closure );
			
			$this->assertHasListener( $closure, 'event' );
			
			
		}
		
		
		/** @test */
		public function any_listener_can_be_found_by_a_custom_key() {
			
			$closure = function ( $foo ) {
				
				return $foo;
				
			};
			
			$this->assertNoListener( 'closure_key', 'event1' );
			$this->assertNoListener( 'class_listener', 'event1' );
			
			$this->dispatcher->listen( 'event1', [ 'closure_key' => $closure ] );
			$this->dispatcher->listen( 'event1', [ 'class_listener' => ActionListener::class ] );
			
			$this->assertHasListener( 'closure_key', 'event1' );
			$this->assertHasListener( 'class_listener', 'event1' );
			
			
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
		 *
		 */
		
		/** @test */
		public function a_class_listener_cant_be_added_two_times_for_the_same_event_no_matter_which_methods_are_used() {
			
			// different events, same method => OK
			$this->dispatcher->listen( 'event1', [ ActionListener::class, 'foobar' ] );
			$this->dispatcher->listen( 'event2', [ ActionListener::class, 'foobar' ] );
			
			$this->assertHasListener( ActionListener::class . '@foobar', 'event1' );
			$this->assertHasListener( ActionListener::class . '@foobar', 'event2' );
			
			$this->reset();
			
			$this->expectException( DuplicateListenerException::class );
			
			// same event, different methods => NOPE
			$this->dispatcher->listen( 'event1', [ ActionListener::class, 'foobar' ] );
			$this->dispatcher->listen( 'event1', [ ActionListener::class, 'handleEvent' ] );
			
			
		}
		
		/** @test */
		public function a_closure_cant_be_added_two_times_for_the_same_event() {
			
			
			$closure1 = function ( $bar ) {
				return $foo = 'foo' . $bar;
			};
			$closure3 = function ( $foo, $bar, $biz ) {
				return $foo = $foo . $bar . $biz;
			};
			
			$this->dispatcher->listen( 'event1', $closure1 );
			$this->dispatcher->listen( 'event1', $closure3 );
			
			$this->expectException( DuplicateListenerException::class );
			
			$this->dispatcher->listen( 'event1', $closure1 );
			
			
		}
		
		/** @test */
		public function an_exception_gets_thrown_when_an_invalid_listener_is_provided() {
			
			$this->expectException( InvalidListenerException::class );
			
			$this->dispatcher->listen( 'event', [ ActionListener::class, 'unresolvableMethod' ] );
			
			
		}
		
		/** @test */
		public function an_exception_gets_thrown_for_instantiated_classes_whose_methods_are_not_callable() {
			
			
			$this->dispatcher->listen( 'event', [ ActionListener::class, 'methodWithDependency' ] );
			
			$this->expectException( Exception::class );
			
			$this->dispatcher->dispatch( 'event', 'foo' );
			
			
		}
		
		/** @test */
		public function an_exception_gets_thrown_when_a_listener_has_unresolvable_constructor_dependencies() {
			
			$this->dispatcher->listen( 'event1', ImpossibleListener::class );
			
			$this->expectException( Exception::class );
			
			$this->dispatcher->dispatch( 'event1', 'foo' );
			
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
		 *
		 * Forgetting Events
		 *
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
		public function a_listener_can_be_forgotten_and_wont_be_executed() {
			
			// Not working if not the exact method
			$this->dispatcher->listen( 'event1', ActionListener::class );
			
			$this->assertHasListener( ActionListener::class . '@handleEvent', 'event1' );
			
			$this->dispatcher->forgetOne( 'event1', ActionListener::class . '@notTheSameMethod' );
			
			$this->assertHasListener( ActionListener::class . '@handleEvent', 'event1' );
			
			$this->dispatchAndAssertAction( 'event1', 'foo', [
				ActionListener::class . '@handleEvent',
				'foo_handled',
			] );
			
			$this->reset();
			
			// working if exact method
			$this->dispatcher->listen( 'event1', ActionListener::class );
			
			$this->assertHasListener( ActionListener::class . '@handleEvent', 'event1' );
			
			// Also works without @sign
			$this->dispatcher->forgetOne( 'event1', ActionListener::class );
			
			$this->assertNoListener( ActionListener::class . '@handleEvent', 'event1' );
			
			$this->dispatchAndAssertNoActionDone( 'event1', 'foo', [
				ActionListener::class . '@handleEvent',
				'foo_handled',
			] );
			
			
		}
		
		/** @test */
		public function a_listener_can_be_forgotten_when_it_registered_with_a_custom_key() {
			
			$this->dispatcher->listen( 'event1', [ 'custom_key' => ActionListener::class ] );
			
			$this->assertHasListener( 'custom_key', 'event1' );
			
			$this->dispatcher->forgetOne( 'event1', 'custom_key' );
			
			$this->assertNoListener( 'custom_key', 'event1' );
			
			
		}
		
		
		/** @test */
		public function a_listener_can_be_marked_as_unremovable_via_a_fluent_api() {
			
			$this->dispatcher->unremovable( 'event1', ActionListener::class );
			
			$this->assertThrows( UnremovableListenerException::class, function () {
				
				$this->dispatcher->forgetOne( 'event1', ActionListener::class );
				
			} );
			
			$this->assertHasListener( ActionListener::class, 'event1' );
			
			
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
		 *
		 * Payloads
		 *
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
		public function a_closure_can_receive_an_object_payload() {
			
			
			$event       = new stdClass();
			$event->name = 'calvin';
			
			$closure = function ( stdClass $event ) {
				
				$this->doClosureAction( $event->name . 'alkan' );
				
			};
			
			$this->dispatcher->listen( stdClass::class, $closure );
			
			$this->dispatchObjectAndAssertClosure( $event, [ $closure, 'calvinalkan' ] );
			
			
		}
		
		/** @test */
		public function a_class_listener_can_receive_an_object_payload() {
			
			$event       = new stdClass();
			$event->name = 'calvin';
			
			$this->dispatcher->listen( stdClass::class, StdClassListener::class );
			
			$this->dispatchObjectAndAssertAction( $event, [
				StdClassListener::class . '@handleEvent',
				'calvinalkan',
			] );
			
			
		}
		
		/** @test */
		public function an_instance_listener_can_receive_an_object_payload() {
			
			$event       = new stdClass();
			$event->name = 'calvin';
			
			$listener         = new StdClassListener();
			$listener->suffix = '$';
			
			$this->dispatcher->listen( stdClass::class, [ $listener, 'instance' ] );
			
			$this->dispatchObjectAndAssertAction( $event, [
				StdClassListener::class . '@instance',
				'calvinalkan$',
			] );
			
			
		}
		
		/** @test */
		public function a_closure_receives_an_array_payload_as_separated_arguments_when_dispatching_an_action() {
			
			$closure = function ( $foo, $bar, $biz ) {
				
				$this->doClosureAction( $foo . $bar . $biz );
				
			};
			
			$this->dispatcher->listen( 'event', $closure );
			
			$this->dispatchAndAssertClosure( 'event', [ 'foo', 'bar', 'biz' ], [ $closure, 'foobarbiz' ] );
			
			
		}
		
		/** @test */
		public function a_closure_receives_an_array_payload_as_separated_arguments_when_dispatching_a_filter_with_array_payload() {
			
			$closure = function ( $foo, $bar, $biz ) {
				
				return $foo . $bar . $biz;
				
			};
			
			$this->dispatcher->listen( FilterableEvent::class, $closure );
			$result = $this->dispatcher->dispatch( FilterableEvent::class, [ 'foo', 'bar', 'biz' ] );
			
			self::assertSame( 'foobarbiz', $result );
			
			
		}
		
		/** @test */
		public function a_class_listener_receives_an_array_payload_as_separated_arguments_when_dispatching_an_action_with_array_payload() {
			
			
			$this->dispatcher->listen( 'event', ActionListener::class . '@foobarbiz' );
			
			$this->dispatchAndAssertAction( 'event', [ 'fooo', 'baar', 'biiz' ], [
				ActionListener::class . '@foobarbiz',
				'fooobaarbiizalkan',
			] );
			
			
		}
		
		/** @test */
		public function when_and_event_is_dispatched_with_multiple_parameters_they_get_converted_into_an_array_payload() {
			
			$this->dispatcher->listen( FilterableEvent::class, ListenerWithReturn2::class . '@foobarbiz' );
			
			$result = $this->dispatcher->dispatch( FilterableEvent::class, 'foo', 'bar', 'biz' );
			
			self::assertSame( 'foobarbizalkan', $result );
			
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
		 *
		 * Stopping Propagation
		 *
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
		public function no_further_events_get_triggered_when_the_fired_event_uses_the_stop_propagation_trait() {
			
			
			$this->dispatcher->listen( 'event1', ThrowExceptionListener::class );
			
			$this->assertThrows( TestException::class, function () {
				
				$this->dispatcher->dispatch( 'event1', 'foo' );
				
				
			} );
			
			$this->reset();
			
			// No Exception will be thrown.
			$this->dispatcher->listen( 'event1', ThrowExceptionListener::class );
			$this->dispatcher->listen( 'event1', StopListener::class . '@foobar' );
			
			$this->dispatchAndAssertAction( 'event1', 'foo', [
				StopListener::class . '@foobar',
				'foobar',
			] );
			
			
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
		 *
		 * Conditional Dispatching
		 *
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
		public function an_event_can_be_dispatched_conditionally_as_an_object() {
			
			$_SERVER['dispatch'] = TRUE;
			
			$event1       = new ConditionalEvent();
			$event1->name = 'calvinalkan';
			
			$this->dispatcher->listen( ConditionalEvent::class, [
				ActionListener::class,
				'conditionalEvent',
			] );
			
			$this->dispatchObjectAndAssertAction( $event1, [
				ActionListener::class . '@conditionalEvent',
				'calvinalkan',
			] );
			
			$this->reset();
			
			$_SERVER['dispatch'] = FALSE;
			
			$event2       = new ConditionalEvent();
			$event2->name = 'calvinalkan';
			
			$this->dispatcher->listen( ConditionalEvent::class, [
				ActionListener::class,
				'conditionalEvent',
			] );
			
			$this->dispatchObjectAndAssertNoAction( $event2, [
				ActionListener::class . '@conditionalEvent',
				'calvinalkan',
			] );
			
			unset( $_SERVER['dispatch'] );
			
			
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
		 *
		 * Conditional Listening
		 *
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
		public function a_listener_can_handle_an_event_conditionally_based_on_runtime_decisions() {
			
			
			// Class Listener
			$_SERVER['should_handle'] = TRUE;
			
			$this->dispatcher->listen( 'event', [ ConditionalListener::class, 'foobar' ] );
			
			$this->dispatchAndAssertAction( 'event', 'foo', [ ConditionalListener::class . '@foobar', 'foobar' ] );
			
			$this->reset();
			
			// Instance Listener
			$_SERVER['should_handle'] = TRUE;
			
			$this->dispatcher->listen( 'event', [ new ConditionalListener(), 'foobar' ] );
			
			$this->dispatchAndAssertAction( 'event', 'foo', [ ConditionalListener::class . '@foobar', 'foobar' ] );
			
			$this->reset();
			
			// Nothing
			$_SERVER['should_handle'] = FALSE;
			
			$this->dispatcher->listen( 'event', [ ConditionalListener::class, 'foobar' ] );
			
			$this->dispatchAndAssertNoActionDone( 'event', 'foo', [
				ConditionalListener::class . '@foobar',
				'foobar',
			] );
			
			unset( $_SERVER['should_handle'] );
			
			
		}

		/** @test */
		public function the_payload_is_not_changed_when_a_listener_does_not_listen_at_runtime() {
			
			$_SERVER['should_handle'] = FALSE;
			
			$this->dispatcher->listen( FilterableEvent::class, [ ListenerWithReturnConditional::class, 'bar' ] );
			$this->dispatcher->listen( FilterableEvent::class, [ ListenerWithReturn3::class, 'bar' ] );
			
			$result = $this->dispatcher->dispatch( new FilterableEvent( 'foo' ) );
			
			$this->assertSame( 'foobar', $result );
			
			unset( $_SERVER['should_handle'] );
			
			
		}

		/** @test */
        public function should_listen_gets_called_even_when_the_listener_got_created_as_a_string_callable()
        {

            // Class Listener
            $_SERVER['should_handle'] = TRUE;

            $this->dispatcher->listen( 'event', ConditionalListener::class . '@foobar' );

            $this->dispatchAndAssertAction( 'event', 'foo', [ ConditionalListener::class . '@foobar', 'foobar' ] );

            $this->reset();


            // Nothing
            $_SERVER['should_handle'] = FALSE;

            $this->dispatcher->listen( 'event',  ConditionalListener::class . '@foobar'  );

            $this->dispatchAndAssertNoActionDone( 'event', 'foo', [
                ConditionalListener::class . '@foobar',
                'foobar',
            ] );

            unset( $_SERVER['should_handle'] );

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
		 *
		 * Dependency Resolution
		 *
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
		public function a_complex_class_listener_gets_build_correctly() {
			
			$event1         = new FilterableEvent( 'foo' );
			$event1->suffix = '$$$';
			
			$this->dispatcher->listen( FilterableEvent::class, ComplexListener::class . '@objectEvent' );
			
			$result = $this->dispatcher->dispatch( $event1 );
			
			$this->assertSame( 'calvinalkan$$$', $result );
			
			$this->reset();
			
			$this->dispatcher->listen( FilterableEvent::class, ComplexListener::class . '@handleEvent' );
			
			$result = $this->dispatcher->dispatch( FilterableEvent::class, '!!!' );
			
			$this->assertSame( 'calvinalkan!!!', $result );
			
			
		}
		
		
		/** @test */
		public function a_closure_can_have_complex_dependencies_as_well() {
			
			$closure = function ( $payload, ComplexMethodDependency $dependency ) {
				
				return $payload . $dependency->get_simple_dependency()->name;
				
			};
			
			$this->dispatcher->listen( FilterableEvent::class, $closure );
			$result = $this->dispatcher->dispatch( FilterableEvent::class, 'calvin' );
			
			$this->assertSame( 'calvinalkan', $result );
			
			
		}
		
		/** @test */
		public function its_possible_for_an_action_listener_to_accept_no_arguments() {
			
			$this->dispatcher->listen( 'event1', ActionListener::class . '@noArgs' );
			
			$this->dispatchAndAssertAction( 'event1', 'unused_payload', [
				ActionListener::class . '@noArgs',
				'Executed without arguments.',
			] );
			
			$this->reset();
			
			$this->dispatcher->listen( 'event1', ActionListener::class . '@noArgs' );
			
			$this->dispatchAndAssertAction( 'event1', [ 'foo', 'bar', 'biz' ], [
				ActionListener::class . '@noArgs',
				'Executed without arguments.',
			] );
			
			$this->reset();
			
			$this->dispatcher->listen( 'event1', ActionListener::class . '@noArgs' );
			
			$this->dispatchAndAssertAction( 'event1', NULL, [
				ActionListener::class . '@noArgs',
				'Executed without arguments.',
			] );
			
			
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
		 *
		 * Filterable Events
		 *
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
		public function filterable_events_get_executed_in_the_correct_order() {
			
			
			$this->dispatcher->listen( FilterableEvent::class, [ ListenerWithReturn2::class, 'biz' ] );
			$this->dispatcher->listen( FilterableEvent::class, [ ListenerWithReturn3::class, 'bar' ] );
			
			$result = $this->dispatcher->dispatch( new FilterableEvent( 'foo' ) );
			
			$this->assertSame( 'foobizbar', $result );
			
		}
		
		/** @test */
		public function closures_can_be_filterable() {
			
			$closure1 = function ( $foo ) {
				
				return $foo . 'bar';
				
			};
			
			$closure2 = function ( $foobar ) {
				
				return $foobar . 'biz';
				
			};
			
			$this->dispatcher->listen( FilterableEvent::class, $closure1 );
			$this->dispatcher->listen( FilterableEvent::class, $closure2 );
			
			$result = $this->dispatcher->dispatch( FilterableEvent::class, 'foo' );
			
			$this->assertSame( 'foobarbiz', $result );
			
			
		}
		
		/** @test */
		public function object_based_filters_can_have_a_default_return_value_when_no_listener_is_specified() {
			
			$result = $this->dispatcher->dispatch( new FilterableEvent( 'foobar' ) );
			
			$this->assertSame( 'foobar', $result );
			
			
		}
		
		/** @test */
		public function if_the_default_method_is_not_specified_the_event_object_itself_is_returned() {
			
			$event = new FilterableWithoutDefault( 'foo', 'bar' );
			
			$returned_value = $this->dispatcher->dispatch( $event );
			
			$this->assertSame( $event, $returned_value );
			
			$this->assertSame( 'foobar', $returned_value->content );
			
		}
		
		/** @test */
		public function if_no_return_value_can_be_created_from_the_dispatched_object_the_first_argument_is_returned() {
			
			$value1 = $this->dispatcher->dispatch( 'event', 'foo', 'bar', 'baz' );
			
			$this->assertEquals( 'foo', $value1 );
			
			$value2 = $this->dispatcher->dispatch( 'event', [ 'foo', 'bar', 'baz' ] );
			
			$this->assertEquals( 'foo', $value2 );
			
			
		}

		/** @test */
        public function if_listeners_are_present_but_none_of_them_handles_the_event_conditionally_the_default_value_is_returned_for_object_events()
        {

            // Class Listener
            $_SERVER['should_handle'] = FALSE;

            $this->dispatcher->listen( FilterableEvent::class, ConditionalListener::class . '@foobar' );

            $result = $this->dispatcher->dispatch( new FilterableEvent( 'foobar' ) );

            $this->assertSame( 'foobar', $result );


        }

        /** @test */
        public function if_listeners_are_present_but_none_of_them_handles_the_event_conditionally_the_default_value_is_returned_for_non_object_events()
        {

            // Class Listener
            $_SERVER['should_handle'] = FALSE;

            $this->dispatcher->listen( 'event', ConditionalListener::class . '@foobar' );

            $value1 = $this->dispatcher->dispatch( 'event', 'foo', 'bar', 'baz' );

            $this->assertEquals( 'foo', $value1 );


        }

        /** @test */
		public function if_filtered_value_and_payload_are_the_same_event_objects_get_the_possibility_to_filter_the_return_value () {

		    $this->dispatcher->listen(EventWithDefaults::class, function ( EventWithDefaults $defaults) {

		        return $defaults;

            });

		    $event = new EventWithDefaults();

		    $return = $this->dispatcher->dispatch($event);

		    $this->assertSame('foo', $return);

		    $this->reset();

            $this->dispatcher->listen(EventWithDefaults::class, function ( EventWithDefaults $defaults) {

                return 'bar';

            });

            $event = new EventWithDefaults();

            $return = $this->dispatcher->dispatch($event);

            $this->assertSame('bar', $return);


        }

        /** @test */
		public function the_default_value_can_be_customized_if_the_returned_value_doesnt_match_the_type_hint_on_the_default_method () {

            $this->dispatcher->listen(EventWithDefaults::class, function () {

                return 1;

            });
            $event = new EventWithDefaults();
            $return = $this->dispatcher->dispatch($event);
            $this->assertSame('foo', $return);

            $this->reset();

            $this->dispatcher->listen(EventWithDefaults::class, function () {

                return ['foo'];

            });
            $event = new EventWithDefaults();
            $return = $this->dispatcher->dispatch($event);
            $this->assertSame('foo', $return);

            $this->reset();

            $this->dispatcher->listen(EventWithDefaults::class, function () {

                return 'bar';

            });
            $event = new EventWithDefaults();
            $return = $this->dispatcher->dispatch($event);
            $this->assertSame('bar', $return);

        }

        /** @test */
		public function the_object_default_value_is_only_respected_if_properly_type_hinted () {

            $this->dispatcher->listen(EventWithDefaultNoTypeHit::class, function () {

                return 1;

            });
            $event = new EventWithDefaultNoTypeHit();
            $return = $this->dispatcher->dispatch($event);
            $this->assertSame(1, $return);

        }

        /** @test */
		public function the_original_value_and_the_filtered_value_are_passed_to_the_object_for_custom_logic () {

            $this->dispatcher->listen(EventWithDefaultLogic::class, function () {

                return 1;

            });
            $event = new EventWithDefaultLogic();
            $return = $this->dispatcher->dispatch($event);
            $this->assertSame('toString:1', $return);

            $this->reset();

            $this->expectExceptionMessage('Return value is not valid for');
            $this->dispatcher->listen(EventWithDefaultLogic::class, function () {

                return ['Make it fail'];

            });
            $event = new EventWithDefaultLogic();
            $this->dispatcher->dispatch($event);


        }

        /** @test */
        public function wordpress_actions_work_and_receive_the_same_payload_without_needing_a_return_value () {

            $event = new EventFakeStub();

            $closure1 = function (EventFakeStub $event_object) use ($event) {

                $this->assertSame($event_object, $event);

            };

            $closure2 = function (EventFakeStub $event_object) use ($event) {

                $this->assertSame($event_object, $event);


            };

            $this->dispatcher->listen(EventFakeStub::class, $closure1);
            $this->dispatcher->listen(EventFakeStub::class, $closure2);

            $this->dispatcher->dispatch( $event);

        }

		private function reset(): void {
			
			$this->tearDownWp();
			
			$this->dispatcher = new WordpressDispatcher(
				
				new ListenerFactory(),
				$this->wp
			
			);
			
			$this->assertEmpty( $this->dispatcher->getListeners() );
			
			
		}
		
		
	}
	
	
	
<?php


    declare(strict_types = 1);


    namespace Tests\Unit;

    use BetterWpHooks\Dispatchers\WordpressDispatcher;
    use BetterWpHooks\Testing\FakeDispatcher;
    use PHPUnit\Framework\Constraint\ExceptionMessage;
    use PHPUnit\Framework\ExpectationFailedException;
    use PHPUnit\Framework\TestCase;
    use \Mockery as m;
    use SniccoAdapter\BaseContainerAdapter;
    use Tests\TestDependencies\Dependency;
    use Tests\TestEvents\ActionEvent;
    use Tests\TestEvents\ActionEvent2;
    use Tests\TestEvents\EventFakeStub;
    use Tests\TestStubs\Plugin1;

    class FakeDispatcherTest extends TestCase
    {

        /**
         * @var FakeDispatcher
         */
        private $fake;

        private const class_namespace = EventFakeStub::class;

        protected function setUp () : void
        {

            parent::setUp();
            $this->fake = new FakeDispatcher( m::mock( WordpressDispatcher::class ) );

        }

        public function testAssertDispatched ()
        {

            try {

                $this->fake->assertDispatched( EventFakeStub::class );
                $this->fail();

            }
            catch ( ExpectationFailedException $e ) {

                $this->assertThat(
                    $e, new ExceptionMessage(
                    'The expected [' . self::class_namespace . '] event was not dispatched.'
                )
                );

            }

            $this->fake->dispatch( EventFakeStub::class );

            $this->fake->assertDispatched( EventFakeStub::class );

        }

        public function testAssertDispatchedWithClosure ()
        {

            $event_stub = new EventFakeStub();
            $event_stub->creator = 'calvinalkan';

            $this->fake->dispatch( $event_stub );

            $this->fake->assertDispatched(
                function( EventFakeStub $event ) use ( $event_stub ) {

                    return 'calvinalkan' === $event_stub->creator;

                }
            );

        }

        public function testAssertDispatchedWithCallbackInt ()
        {

            $this->fake->dispatch( EventFakeStub::class );
            $this->fake->dispatch( EventFakeStub::class );

            try {
                $this->fake->assertDispatched( EventFakeStub::class, 1 );
                $this->fail();
            }
            catch ( ExpectationFailedException $e ) {
                $this->assertThat(
                    $e, new ExceptionMessage(
                    'The expected [' . self::class_namespace . '] event was dispatched 2 times instead of 1 times.'
                )
                );
            }

            $this->fake->assertDispatched( EventFakeStub::class, 2 );

        }

        public function testAssertDispatchedTimes ()
        {

            $this->fake->dispatch( EventFakeStub::class );
            $this->fake->dispatch( EventFakeStub::class );

            try {
                $this->fake->assertDispatchedTimes( EventFakeStub::class, 1 );
                $this->fail();
            }
            catch ( ExpectationFailedException $e ) {
                $this->assertThat(
                    $e, new ExceptionMessage(
                    'The expected [' . self::class_namespace . '] event was dispatched 2 times instead of 1 times.'
                )
                );
            }

            $this->fake->assertDispatchedTimes( EventFakeStub::class, 2 );
        }

        public function testAssertNotDispatched ()
        {

            $this->fake->assertNotDispatched( EventFakeStub::class );

            $this->fake->dispatch( EventFakeStub::class );

            try {
                $this->fake->assertNotDispatched( EventFakeStub::class );
                $this->fail();
            }
            catch ( ExpectationFailedException $e ) {
                $this->assertThat(
                    $e, new ExceptionMessage(
                    'The unexpected [' . self::class_namespace . '] event was dispatched.'
                )
                );
            }
        }

        public function testAssertNotDispatchedWithClosure ()
        {

            $event_stub = new EventFakeStub();
            $event_stub->creator = 'calvinalkan';

            $this->fake->dispatch( $event_stub );

            try {

                $this->fake->assertNotDispatched(
                    function( EventFakeStub $event ) use ( $event_stub ) {

                        return 'calvinalkan' === $event_stub->creator;

                    }
                );

                $this->fail();

            }
            catch ( ExpectationFailedException $e ) {

                $this->assertThat(
                    $e, new ExceptionMessage(
                    'The unexpected [' . self::class_namespace . '] event was dispatched.'
                )
                );

            }
        }

        public function testAssertNothingDispatched ()
        {

            $this->fake->assertNothingDispatched();

            $this->fake->dispatch( EventFakeStub::class );
            $this->fake->dispatch( EventFakeStub::class );

            try {
                $this->fake->assertNothingDispatched();
                $this->fail();
            }
            catch ( ExpectationFailedException $e ) {
                $this->assertThat(
                    $e, new ExceptionMessage( '2 unexpected events were dispatched.' )
                );
            }


        }

        public function testAssertDispatchedWithIgnore ()
        {

            $dispatcher = m::spy( WordpressDispatcher::class );

            $fake_for = [

                'Foo',
                function( $event, $payload ) {

                    return $event === 'Bar' && $payload['id'] === 1;
                },

            ];

            $event_fake = new FakeDispatcher( $dispatcher, $fake_for );

            $event_fake->dispatch( 'Foo' );
            $event_fake->dispatch( 'Bar', [ 'id' => 1 ] );
            $event_fake->dispatch( 'Baz' );

            $event_fake->assertDispatched( 'Foo' );
            $event_fake->assertDispatched( 'Bar' );
            $event_fake->assertDispatched( 'Baz' );
            $event_fake->assertNotDispatched( 'Biz' );

            $dispatcher->shouldHaveReceived( 'dispatch' )->once();


        }

        public function testWithCustomErrorMessage()
        {

            try {

                $this->fake->assertDispatched( EventFakeStub::class, null ,'custom error message' );
                $this->fail();

            }
            catch ( ExpectationFailedException $e ) {

                $this->assertThat(
                    $e, new ExceptionMessage(
                        'custom error message'
                    )
                );

            }

            $this->fake->dispatch( EventFakeStub::class );

            $this->fake->assertDispatched( EventFakeStub::class );

        }

        public function testGetAllDispatchedEvents() {

            $this->fake->dispatch( $e1 = new EventFakeStub() );
            $this->fake->dispatch( $e2 = new EventFakeStub() );
            $this->fake->dispatch( $e3 = new ActionEvent() );
            $this->fake->dispatch( $e4 = new ActionEvent() );

            $all = $this->fake->allDispatchedEvents();

            $this->assertCount(2, $all);
            $this->assertCount(2, $all[EventFakeStub::class]);
            $this->assertCount(2, $all[ActionEvent::class]);

            $this->assertSame($e1, $all[EventFakeStub::class][0][0]);
            $this->assertSame($e2, $all[EventFakeStub::class][1][0]);
            $this->assertSame($e3, $all[ActionEvent::class][0][0]);
            $this->assertSame($e4, $all[ActionEvent::class][1][0]);

        }

        public function testGetAllOfType()
        {
            $this->fake->dispatch( $e1 = new EventFakeStub() );
            $this->fake->dispatch( $e2 = new EventFakeStub() );
            $this->fake->dispatch( $e3 = new ActionEvent() );
            $this->fake->dispatch( $e4 = new ActionEvent() );

            $all = $this->fake->allOfType(EventFakeStub::class);
            $this->assertCount(2, $all);
            $this->assertSame($e1, $all[0]);
            $this->assertSame($e2, $all[1]);
        }

        public function testClearAll() {

            $this->fake->dispatch( $e1 = new EventFakeStub() );
            $this->fake->dispatch( $e2 = new EventFakeStub() );

            $all = $this->fake->allDispatchedEvents();
            $this->assertCount(2, $all[EventFakeStub::class]);

            $this->fake->clearDispatchedEvents();

            $all = $this->fake->allDispatchedEvents();
            $this->assertCount(0, $all);

        }

        /** @test */
        public function testWithFacadeEvent () {

            Plugin1::make(new BaseContainerAdapter());
            Plugin1::fake();
            Plugin1::dispatch(ActionEvent2::class , $d= new Dependency());

            $all = Plugin1::dispatcher()->allDispatchedEvents();
            $this->assertCount(1, $all);

            $type = Plugin1::dispatcher()->allOfType(ActionEvent2::class);
            $this->assertCount(1, $type);
            $this->assertSame([
                ActionEvent2::class,
                [$d]
            ], $type[0]);
        }

        /** @test */
        public function ignored_events_get_forwarded_to_the_real_dispatcher_but_also_get_marked_as_dispatched ()
        {


            $dispatcher = m::spy( WordpressDispatcher::class );

            $fake_for = [

                'Foo',
                function( $event, $payload ) {

                    return $event === 'Bar' && $payload['id'] === 1;
                },

            ];

            $event_fake = new FakeDispatcher( $dispatcher, $fake_for );

            $event_fake->dispatch( 'Foo' );
            $event_fake->dispatch( 'Bar', [ 'id' => 1 ] );

            $event_fake->dispatch( 'Baz', 'BazBaz' );

            $event_fake->assertDispatched( 'Foo' );
            $event_fake->assertDispatched( 'Bar' );
            $event_fake->assertDispatched( 'Baz' );

            $dispatcher->shouldHaveReceived( 'dispatch' )->once()->with( 'Baz', 'BazBaz' );


        }

    }

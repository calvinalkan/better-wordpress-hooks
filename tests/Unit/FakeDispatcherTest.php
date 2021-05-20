<?php


    namespace Tests\Unit;

    use BetterWpHooks\Dispatchers\WordpressDispatcher;
    use BetterWpHooks\Testing\FakeDispatcher;
    use PHPUnit\Framework\Constraint\ExceptionMessage;
    use PHPUnit\Framework\ExpectationFailedException;
    use PHPUnit\Framework\TestCase;
    use \Mockery as m;
    use Tests\TestEvents\EventFakeStub;

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

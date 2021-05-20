<?php


    namespace Tests\Unit;

    use BetterWpHooks\BetterWpHooks;
    use BetterWpHooks\Contracts\Dispatcher;
    use BetterWpHooks\Exceptions\ConfigurationException;
    use BetterWpHooks\Mappers\WordpressEventMapper;
    use BetterWpHooks\Testing\FakeDispatcher;
    use PHPUnit\Framework\Constraint\ExceptionMessage;
    use PHPUnit\Framework\ExpectationFailedException;
    use PHPUnit\Framework\TestCase;
    use SniccoAdapter\BaseContainerAdapter;
    use Tests\TestEvents\EventFakeStub;
    use Tests\TestStubs\Plugin1;
    use BetterWpHooks\Dispatchers\WordpressDispatcher;
    use Mockery as m;

    class BetterWpHooksFacadeTestingTest extends TestCase
    {

        private const class_namespace = EventFakeStub::class;

        protected function setUp() : void
        {

            parent::setUp();
            $this->newPlugin1();
        }

        protected function tearDown() : void
        {

            parent::tearDown();
        }

        public function testAssertDispatched()
        {

            try {

                Plugin1::fake();
                Plugin1::assertDispatched(EventFakeStub::class);
                $this->fail();

            }
            catch (ExpectationFailedException $e) {

                $this->assertThat(
                    $e,
                    new ExceptionMessage(
                        'The expected ['.self::class_namespace.'] event was not dispatched.'
                    )
                );

            }

            Plugin1::dispatch(EventFakeStub::class);

            Plugin1::assertDispatched(EventFakeStub::class);

        }

        public function testAssertDispatchedWithClosure()
        {

            Plugin1::fake();

            $event_stub = new EventFakeStub();
            $event_stub->creator = 'calvinalkan';

            Plugin1::dispatch($event_stub);

            Plugin1::assertDispatched(
                function (EventFakeStub $event) {

                    return $event->creator === 'calvinalkan';
                }
            );

        }

        public function testAssertDispatchedWithCallbackInt()
        {

            Plugin1::fake();

            Plugin1::dispatch(EventFakeStub::class);
            Plugin1::dispatch(EventFakeStub::class);

            try {

                Plugin1::assertDispatched(EventFakeStub::class, 1);
                $this->fail();

            }
            catch (ExpectationFailedException $e) {

                $this->assertThat(
                    $e,
                    new ExceptionMessage(
                        'The expected ['.self::class_namespace.'] event was dispatched 2 times instead of 1 times.'
                    )
                );

            }

            Plugin1::assertDispatched(EventFakeStub::class, 2);

        }

        public function testAssertDispatchedTimes()
        {

            Plugin1::fake();

            Plugin1::dispatch(EventFakeStub::class);
            Plugin1::dispatch(EventFakeStub::class);

            try {
                Plugin1::assertDispatchedTimes(EventFakeStub::class, 1);
                $this->fail();
            }
            catch (ExpectationFailedException $e) {
                $this->assertThat(
                    $e, new ExceptionMessage(
                        'The expected ['.self::class_namespace.'] event was dispatched 2 times instead of 1 times.'
                    )
                );
            }

            Plugin1::assertDispatchedTimes(EventFakeStub::class, 2);
        }

        public function testAssertNotDispatched()
        {

            Plugin1::fake();

            Plugin1::assertNotDispatched(EventFakeStub::class);

            Plugin1::dispatch(EventFakeStub::class);

            try {
                Plugin1::assertNotDispatched(EventFakeStub::class);
                $this->fail();
            }
            catch (ExpectationFailedException $e) {

                $this->assertThat(
                    $e, new ExceptionMessage(
                        'The unexpected ['.self::class_namespace.'] event was dispatched.'
                    )
                );
            }
        }

        public function testAssertNotDispatchedWithClosure()
        {


            Plugin1::fake();

            $event_stub = new EventFakeStub();
            $event_stub->creator = 'calvinalkan';

            Plugin1::dispatch($event_stub);

            try {

                Plugin1::assertNotDispatched(
                    function (EventFakeStub $event) use ($event_stub) {

                        return 'calvinalkan' === $event_stub->creator;

                    }
                );

                $this->fail();

            }
            catch (ExpectationFailedException $e) {

                $this->assertThat(
                    $e, new ExceptionMessage(
                        'The unexpected ['.self::class_namespace.'] event was dispatched.'
                    )
                );

            }
        }

        public function testAssertNothingDispatched()
        {


            Plugin1::fake();

            Plugin1::assertNothingDispatched();

            Plugin1::dispatch(EventFakeStub::class);
            Plugin1::dispatch(EventFakeStub::class);

            try {
                Plugin1::assertNothingDispatched();
                $this->fail();
            }
            catch (ExpectationFailedException $e) {
                $this->assertThat(
                    $e, new ExceptionMessage('2 unexpected events were dispatched.')
                );
            }


        }

        public function testAssertDispatchedWithIgnore()
        {

            $dispatcher = m::spy(WordpressDispatcher::class);
            $event_mapper = m::mock(WordpressEventMapper::class);
            $container = new BaseContainerAdapter();

            $events_to_fake = [

                'Foo',
                function ($event, $payload) {

                    return $event === 'Bar' && $payload['id'] === 1;
                },

            ];

            Plugin1::setInstance(
                new BetterWpHooks(
                    $container, $dispatcher, $event_mapper
                )
            );

            Plugin1::fake($events_to_fake);

            // Should not dispatch
            Plugin1::dispatch('Foo');
            // Should not dispatch
            Plugin1::dispatch('Bar', ['id' => 1]);
            // Should dispatch
            Plugin1::dispatch('Baz', ['id' => 1]);

            Plugin1::assertDispatched('Foo');
            Plugin1::assertDispatched('Bar');
            Plugin1::assertDispatched('Baz');
            Plugin1::assertNotDispatched('Biz');

            $dispatcher->shouldHaveReceived('dispatch')->once()->with('Baz', ['id' => 1]);


        }

        public function testExceptionWhenInstanceWasNotFakes()
        {

            try {

                Plugin1::assertDispatched(EventFakeStub::class);
                $this->fail();

            }

            catch (ConfigurationException $e) {

                $this->assertSame(
                    'Did you forget to set up the FakeDispatcher with {YourFacadeClass}::fake()?',
                    $e->getMessage()
                );

            }

            try {

                Plugin1::assertDispatchedTimes(EventFakeStub::class, 1);
                $this->fail();

            }

            catch (ConfigurationException $e) {

                $this->assertSame(
                    'Did you forget to set up the FakeDispatcher with {YourFacadeClass}::fake()?',
                    $e->getMessage()
                );

            }

            try {

                Plugin1::assertNotDispatched(EventFakeStub::class);
                $this->fail();

            }

            catch (ConfigurationException $e) {

                $this->assertSame(
                    'Did you forget to set up the FakeDispatcher with {YourFacadeClass}::fake()?',
                    $e->getMessage()
                );

            }

            try {

                Plugin1::assertNothingDispatched();
                $this->fail();

            }

            catch (ConfigurationException $e) {

                $this->assertSame(
                    'Did you forget to set up the FakeDispatcher with {YourFacadeClass}::fake()?',
                    $e->getMessage()
                );

            }


        }

        /** @test */
        public function the_container_dispatcher_instance_gets_swapped_by_default_when_events_are_faked()
        {

            $container = Plugin1::container();

            $container->instance(
                WordpressDispatcher::class,
                Plugin1::dispatcher()
            );

            $container->instance(
                Dispatcher::class,
                Plugin1::dispatcher()
            );

            $this->assertInstanceOf(
                WordpressDispatcher::class,
                $container->make(WordpressDispatcher::class)
            );

             $this->assertInstanceOf(
                 WordpressDispatcher::class,
                $container->make(Dispatcher::class)
            );


            Plugin1::fake();

            $this->assertInstanceOf(
                FakeDispatcher::class,
                $container->make(WordpressDispatcher::class)
            );

             $this->assertInstanceOf(
                FakeDispatcher::class,
                $container->make(Dispatcher::class)
            );




        }

        /** @test */
        public function swapping_in_the_container_can_be_disabled_with_a_flag()
        {

            $container = Plugin1::container();

            $container->instance(
                WordpressDispatcher::class,
                Plugin1::dispatcher()
            );

            $container->instance(
                Dispatcher::class,
                Plugin1::dispatcher()
            );

            $this->assertInstanceOf(
                WordpressDispatcher::class,
                $container->make(WordpressDispatcher::class)
            );

             $this->assertInstanceOf(
                 WordpressDispatcher::class,
                $container->make(Dispatcher::class)
            );


            Plugin1::fake([], false);

            $this->assertInstanceOf(
                WordpressDispatcher::class,
                $container->make(WordpressDispatcher::class)
            );

             $this->assertInstanceOf(
                 WordpressDispatcher::class,
                $container->make(Dispatcher::class)
            );




        }



        private function newPlugin1()
        {

            $container1 = new BaseContainerAdapter();

            Plugin1::make($container1);

        }


    }
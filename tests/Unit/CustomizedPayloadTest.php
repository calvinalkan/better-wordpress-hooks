<?php


    declare(strict_types = 1);


    namespace Tests\Unit;

    use BetterWpHooks\Dispatchers\WordpressDispatcher;
    use BetterWpHooks\ListenerFactory;
    use BetterWpHooks\Testing\BetterWpHooksTestCase;
    use BetterWpHooks\WordpressApi;
    use Codeception\AssertThrows;
    use PHPUnit\Framework\TestCase;
    use SniccoAdapter\BaseContainerAdapter;
    use Tests\CustomAssertions;
    use Tests\TestEvents\WithCustomPayload;
    use Tests\TestEvents\WithoutCustomPayload;

    class CustomizedPayloadTest extends BetterWpHooksTestCase
    {

        use AssertThrows;
        use CustomAssertions;

        private $dispatcher;

        protected function setUp() : void
        {


            parent::setUp();

            $this->setUpWp(VENDOR_DIR);

            $wp = new WordpressApi();
            $this->dispatcher = new WordpressDispatcher(

                new ListenerFactory(new BaseContainerAdapter()),
                $wp

            );

        }

        protected function tearDown() : void
        {

            $this->tearDownWp();


            parent::tearDown();


        }


        /** @test */
        public function if_an_event_object_provides_a_payload_method_the_return_value_is_used_when_parsing_the_payload()
        {

            $closure = function (WithoutCustomPayload $event) {

                $this->assertInstanceOf(WithoutCustomPayload::class, $event, 'Wrong payload provided');

            };
            $this->dispatcher->listen(WithoutCustomPayload::class, $closure);
            $this->dispatcher->dispatch(new WithoutCustomPayload());

            $closure = function (string  $custom_payload) {

                $this->assertSame('PAYLOAD', $custom_payload, 'Wrong payload provided');

            };
            $this->dispatcher->listen(WithCustomPayload::class, $closure);
            $this->dispatcher->dispatch(new WithCustomPayload());


        }

        /** @test */
        public function default_values_work_when_the_event_object_has_a_customized_payload_and_no_listeners () {

            $event = new WithCustomPayload();

            $return_value = $this->dispatcher->dispatch($event);

            $this->assertSame('PAYLOAD', $return_value);

        }

        /** @test */
        public function default_values_work_with_smart_return_values () {

            $event = new WithCustomPayload();
            $GLOBALS['test']['closure_run'] = false;

            $closure = function (string $payload) {

                $GLOBALS['test']['closure_run'] = true;

                return [$payload];

            };

            $this->dispatcher->listen(WithCustomPayload::class, $closure);
            $filtered = $this->dispatcher->dispatch($event);

            $this->assertSame('PAYLOAD', $filtered);
            $this->assertTrue($GLOBALS['test']['closure_run']);

        }

    }


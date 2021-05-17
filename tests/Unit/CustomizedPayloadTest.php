<?php


    declare(strict_types = 1);


    namespace Tests\Unit;

    use BetterWpHooks\Dispatchers\WordpressDispatcher;
    use BetterWpHooks\ListenerFactory;
    use BetterWpHooks\WordpressApi;
    use Codeception\AssertThrows;
    use PHPUnit\Framework\TestCase;
    use SniccoAdapter\BaseContainerAdapter;
    use Tests\CustomAssertions;
    use Tests\TestEvents\WithCustomPayload;
    use Tests\TestEvents\WithoutCustomPayload;

    class CustomizedPayloadTest extends TestCase
    {

        use AssertThrows;
        use CustomAssertions;

        private $dispatcher;

        private $wp;

        protected function setUp() : void
        {


            parent::setUp();

            $plugin_php = dirname(__DIR__, 2).'/vendor/calvinalkan/wordpress-hook-api-clone/plugin.php';

            require_once $plugin_php;

            $this->assertEmpty($GLOBALS['wp_filter']);
            $this->assertEmpty($GLOBALS['wp_actions']);
            $this->assertEmpty($GLOBALS['wp_current_filter']);

            $this->wp = new WordpressApi();
            $this->dispatcher = new WordpressDispatcher(

                new ListenerFactory(new BaseContainerAdapter()),
                $this->wp

            );

        }

        protected function tearDown() : void
        {

            parent::tearDown();

            $this->reset();

        }

        private function reset() : void
        {


            $GLOBALS['wp_filter'] = [];
            $GLOBALS['wp_actions'] = [];
            $GLOBALS['wp_current_filter'] = [];

            $this->dispatcher = new WordpressDispatcher(

                new ListenerFactory(),
                $this->wp

            );

            $this->assertEmpty($this->dispatcher->getListeners());


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

    }
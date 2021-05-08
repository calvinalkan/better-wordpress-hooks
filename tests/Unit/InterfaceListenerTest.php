<?php


    namespace Tests\Unit;

    use BetterWpHooks\Dispatchers\WordpressDispatcher;
    use BetterWpHooks\ListenerFactory;
    use BetterWpHooks\WordpressApi;
    use Codeception\AssertThrows;
    use PHPUnit\Framework\TestCase;
    use SniccoAdapter\BaseContainerAdapter;
    use Tests\CustomAssertions;
    use Tests\TestListeners\ListenerInterface;

    class InterfaceListenerTest extends TestCase
    {


        use AssertThrows;
        use CustomAssertions;

        private $dispatcher;

        private $wp;

        /**
         * @var BaseContainerAdapter
         */
        private $container;

        /** @test */
        public function a_listener_can_be_an_interface_if_bound_in_the_container () {

            $this->container->singleton(ListenerInterface::class, function () {

                return new InterfaceImplementation();

            });

            $this->dispatcher->listen('event1', ListenerInterface::class . '@handle');

            $value = $this->dispatcher->dispatch('event1', 'foo');

            $this->assertSame('foo', $value);

        }

        protected function setUp(): void {


            parent::setUp();

            $plugin_php = dirname( __DIR__, 2 ) . '/vendor/calvinalkan/wordpress-hook-api-clone/plugin.php';

            require_once $plugin_php;

            $this->assertEmpty( $GLOBALS['wp_filter'] );
            $this->assertEmpty( $GLOBALS['wp_actions'] );
            $this->assertEmpty( $GLOBALS['wp_current_filter'] );

            $this->wp         = new WordpressApi();
            $this->dispatcher = new WordpressDispatcher(

                new ListenerFactory( $container = new BaseContainerAdapter() ),
                $this->wp

            );

            $this->container = $container;

        }

        protected function tearDown(): void {

            parent::tearDown();

            $this->reset();

        }

        private function reset(): void {


            $GLOBALS['wp_filter']         = [];
            $GLOBALS['wp_actions']        = [];
            $GLOBALS['wp_current_filter'] = [];

            $this->dispatcher = new WordpressDispatcher(

                new ListenerFactory(),
                $this->wp

            );

            $this->assertEmpty( $this->dispatcher->getListeners() );


        }


    }

    class InterfaceImplementation implements ListenerInterface {

        public function handleEvent($event)
        {
            return 'foo';
        }

    }
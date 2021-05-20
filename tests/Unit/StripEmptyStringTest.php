<?php


    declare(strict_types = 1);


    namespace Tests\Unit;

    use BetterWpHooks\Dispatchers\WordpressDispatcher;
    use BetterWpHooks\ListenerFactory;
    use BetterWpHooks\Testing\BetterWpHooksTestCase;
    use BetterWpHooks\WordpressApi;
    use SniccoAdapter\BaseContainerAdapter;
    use Tests\TestDependencies\SimpleClass;

    class StripEmptyStringTest extends BetterWpHooksTestCase
    {

        /**
         * @var WordpressApi
         */
        private $wp;

        /**
         * @var WordpressDispatcher
         */
        private $dispatcher;

        protected function setUp() : void
        {

            parent::setUp();

            $this->setUpWp(VENDOR_DIR);

            $this->wp         = new WordpressApi();
            $this->dispatcher = new WordpressDispatcher(

                new ListenerFactory( new BaseContainerAdapter() ),
                $this->wp

            );

        }

        protected function tearDown() : void
        {

            parent::tearDown();

            $this->tearDownWp();

        }

        /** @test */
        public function empty_strings_are_striped_from_hooks () {

            $this->dispatcher->listen('init', function ( SimpleClass $class ) {

                throw new \Exception($class->message);

            });

            $this->expectExceptionMessage('simple class.');

            do_action('init');


        }

    }
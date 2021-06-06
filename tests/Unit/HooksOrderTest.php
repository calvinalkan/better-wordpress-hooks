<?php


    declare(strict_types = 1);


    namespace Tests\Unit;

    use BetterWpHooks\Dispatchers\WordpressDispatcher;
    use BetterWpHooks\Exceptions\UnremovableListenerException;
    use BetterWpHooks\ListenerFactory;
    use BetterWpHooks\Testing\BetterWpHooksTestCase;
    use BetterWpHooks\WordpressApi;
    use SniccoAdapter\BaseContainerAdapter;

    class HooksOrderTest extends BetterWpHooksTestCase
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
        public function a_hook_that_always_runs_first_can_be_created () {

            $GLOBALS['test']['init_low_priority'] = 'not-run';
            $GLOBALS['test']['ensure_first'] = 'not-run';


            add_action('init', function () {

                $GLOBALS['test']['init_low_priority'] = 'run';

            }, PHP_INT_MIN );

            $this->dispatcher->ensureFirst('init', function () {

                $GLOBALS['test']['ensure_first'] = 'run';
                $this->assertSame('not-run', $GLOBALS['test']['init_low_priority'], 'The hook was not run first.');

            });


            do_action('init');

            $this->assertSame('run',$GLOBALS['test']['init_low_priority'] );
            $this->assertSame('run',$GLOBALS['test']['ensure_first'] );



        }

        /** @test */
        public function a_hook_that_always_runs_first_is_not_removeable_by_default () {

            $GLOBALS['test']['init_high_priority'] = 'not-run';
            $GLOBALS['test']['ensure_first'] = 'not-run';


            $this->dispatcher->ensureFirst('init', $c = function () {

                $GLOBALS['test']['ensure_first'] = 'run';
                $this->assertSame('not-run', $GLOBALS['test']['init_high_priority'], 'The hook was not run first.');

            });

            $this->expectException(UnremovableListenerException::class);

            $this->dispatcher->forgetOne('init', $c);


        }

        /** @test */
        public function a_hook_that_always_runs_last_can_be_created () {

            $GLOBALS['test']['init_high_priority'] = 'not-run';
            $GLOBALS['test']['ensure_first'] = 'not-run';

            $this->dispatcher->ensureLast('init', function () {

                $GLOBALS['test']['ensure_first'] = 'run';
                $this->assertSame('run', $GLOBALS['test']['init_high_priority'], 'The hook was not run last.');

            });

            add_action('init', function () {

                $this->assertSame('not-run', $GLOBALS['test']['init_high_priority'], 'The hook was not run last.');
                $GLOBALS['test']['init_high_priority'] = 'run';


            }, PHP_INT_MAX );
            add_action('init', function () {});


            do_action('init');

            $this->assertSame('run',$GLOBALS['test']['init_high_priority'], 'the wordpress hook did not run.' );
            $this->assertSame('run',$GLOBALS['test']['ensure_first'], 'The dispatcher hook did not run.' );

        }

    }
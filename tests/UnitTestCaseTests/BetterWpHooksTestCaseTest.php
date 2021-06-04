<?php


    declare(strict_types = 1);


    namespace Tests\UnitTestCaseTests;

    use Codeception\AssertThrows;
    use PHPUnit\Framework\Assert;
    use PHPUnit\Framework\AssertionFailedError;
    use PHPUnit\Framework\TestCase;
    use BetterWpHooks\Testing\BetterWpHooksTestCase;

    use function add_action;
    use function add_filter;

    use const VENDOR_DIR;

    class BetterWpHooksTestCaseTest extends TestCase
    {

        use AssertThrows;

        private $test_case;

        /**
         * @var string
         */
        private $vendor_dir;


        protected function setUp() : void
        {

            parent::setUp();

            $this->test_case = new BetterWpHooksTestCase();

            $this->vendor_dir = VENDOR_DIR;

        }


        /** @test */
        public function the_class_wp_hook_exists_when_loaded_the_test_case()
        {

            $this->test_case->setUpWp($this->vendor_dir);

            self::assertTrue(class_exists(\WP_Hook::class), 'The class WP_Hook was not loaded');

        }


        /** @test */
        public function the_path_to_plugin_php_can_be_set()
        {

            try {

                $this->test_case->setUpWp($this->vendor_dir);

                Assert::assertTrue(true);

            }
            catch (\Throwable $e) {

                throw new AssertionFailedError($e->getMessage().PHP_EOL.$e->getTraceAsString());


            }


        }


        /** @test */
        public function assert_that_globals_are_emptied_out_before_setUp()
        {


            $GLOBALS['wp_filter']['init'][10] = [
                [
                    'function' => function () {
                    }, 'accepted_args' => 1,
                ],
            ];

            $this->test_case->setUpWp($this->vendor_dir);

            $this->assertEmpty($GLOBALS['wp_filter']);
            $this->assertEmpty($GLOBALS['wp_actions']);
            $this->assertEmpty($GLOBALS['wp_current_filter']);

        }


        /** @test */
        public function assert_that_globals_are_emptied_out_in_tear_down()
        {

            $this->test_case->setUpWp($this->vendor_dir);

            add_action('foo', function () {

                return 'foo';
            }, 10, 1);
            add_filter('bar', function () {

                return 'bar';
            }, 10, 1);

            $this->test_case->tearDownWp();

            $this->assertEmpty($GLOBALS['wp_filter']);
            $this->assertEmpty($GLOBALS['wp_actions']);
            $this->assertEmpty($GLOBALS['wp_current_filter']);

        }

        /** @test */
        public function the_class_wont_be_loaded_twice()
        {

            try {

                $this->test_case->setUpWp($this->vendor_dir);
                $this->test_case->setUpWp($this->vendor_dir);

                Assert::assertTrue(true);

            }
            catch (\Throwable $e) {

                throw new AssertionFailedError($e->getMessage().PHP_EOL.$e->getTraceAsString());


            }


        }


    }
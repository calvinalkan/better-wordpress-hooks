<?php


    namespace BetterWpHooks\Traits;

    use BetterWpHooks\BetterWpHooks;
    use BetterWpHooks\Contracts\Dispatcher;
    use BetterWpHooks\Dispatchers\WordpressDispatcher;
    use BetterWpHooks\Exceptions\ConfigurationException;
    use BetterWpHooks\ListenerFactory;
    use BetterWpHooks\Mappers\WordpressEventMapper;
    use BetterWpHooks\Testing\FakeDispatcher;
    use BetterWpHooks\WordpressApi;
    use Contracts\ContainerAdapter;
    use Illuminate\Support\Arr;
    use SniccoAdapter\BaseContainerAdapter;

    use function BetterWpHooks\Functions\hasTrait;

    /**
     * Trait BetterWpHooksFacade
     *
     * @mixin \BetterWpHooks\Mixin
     * @see \BetterWpHooks\Mixin for the public api provided.
     */
    trait BetterWpHooksFacade
    {

        use ReflectsCallable;

        /**
         * @var null|BetterWpHooks
         */
        public static $instance = null;

        public static function make(ContainerAdapter $container_adapter = null) : ?BetterWpHooks
        {


            static::setInstance($instance = static::createInstance($container_adapter));

            return $instance;

        }

        public static function getInstance() : ?BetterWpHooks
        {

            return static::$instance;


        }

        public static function setInstance(?BetterWpHooks $better_wp_hooks)
        {

            static::$instance = $better_wp_hooks;

        }

        /**
         * Invoke any matching instance method for the static method being called.
         *
         * @param  string  $method
         * @param  array  $parameters
         *
         * @return mixed
         * @throws ConfigurationException
         */
        public static function __callStatic(string $method, array $parameters)
        {

            self::checkConfiguration();

            $instance = self::getInstance();

            $callable = [$instance, $method];

            if ( ! is_callable($callable)) {

                $callable = [$instance->dispatcher(), $method];

                if (is_callable($callable)) {

                    return call_user_func_array($callable, $parameters);


                }

                if (self::isTestAssertion($method)) {

                    throw new ConfigurationException(
                        'Did you forget to set up the FakeDispatcher with {YourFacadeClass}::fake()?'
                    );

                }

                throw new \BadMethodCallException(
                    'Method '.get_class($instance).'::'.$method.'() does not exist.'
                );

            }

            return call_user_func_array($callable, $parameters);


        }

        /**
         * @throws ConfigurationException
         */
        public static function dispatch()
        {

            self::checkConfiguration();

            $arguments = func_get_args();

            $dispatcher = self::$instance->dispatcher();

            if (self::isEventObject(static::class)) {

                return static::tryAsObject($arguments, $dispatcher);

            }

            if ( is_array($arguments[0])) {

                throw new ConfigurationException(
                    'You are trying to dispatch an event with the facade but the first argument is not a string.'
                );

            }


            return $dispatcher->dispatch(

                array_shift($arguments),
                self::unwrapIfMultidimensional($arguments)

            );


        }

        public static function tryAsObject(array $arguments, Dispatcher $dispatcher)
        {


            $class = new \ReflectionClass(static::class);
            $constructor_args = ($constructor = $class->getConstructor()) ? $constructor->getNumberOfParameters() : null;

            if ( ! $constructor_args) {

                return $dispatcher->dispatch($class->newInstanceArgs());

            }

            if ( is_object($arguments[0]) ) {

                return $dispatcher->dispatch($arguments[0]);
            }

            if (is_array($arguments[0])) {

                return $dispatcher->dispatch($class->newInstanceArgs($arguments[0]));

            }

            throw new ConfigurationException('event is not instantiable as object');

        }

        public static function mapEvent(...$args_from_wp)
        {

            return self::dispatch(new static(...$args_from_wp));

        }

        /**
         * Accepts a boolean as the first parameter and only
         * dispatches the event when the truth test evaluates to true.
         *
         * @param  boolean  $condition
         * @param  mixed  ...$args
         *
         * @return mixed
         */
        public static function dispatchIf(bool $condition, ...$args)
        {

            if ($condition === true) {

                $args = self::unwrapIfMultidimensional($args);

                $event_object = new static(...$args);

                if ( ! self::isEventObject($event_object)) {

                    throw new \BadMethodCallException(
                        'Doing it wrong: The Plugin facade is not meant to be used for conditional dispatching. You should use event objects instead'
                    );

                }

                return static::dispatcher()->dispatch($event_object);

            }


        }

        /**
         * Accepts a boolean or a closure as the first parameter and only
         * dispatches the event unless the truth test evaluates to true.
         *
         * @param  boolean  $condition
         * @param  mixed  ...$args
         *
         * @return mixed
         */
        public static function dispatchUnless(bool $condition, ...$args)
        {


            return self::dispatchIf( ! $condition, self::unwrapIfMultidimensional($args));


        }

        /**
         * Replace the bound instance with a fake.
         *
         * @param  array|string  $eventsToFake
         *
         * @return FakeDispatcher
         */
        public static function fake($eventsToFake = []) : FakeDispatcher
        {

            $event_fake = new FakeDispatcher(self::$instance->dispatcher(), $eventsToFake);

            self::$instance->swapDispatcher($event_fake);

            return $event_fake;


        }

        private static function createInstance(ContainerAdapter $container_adapter = null) : BetterWpHooks
        {

            $container_adapter = $container_adapter ?? new BaseContainerAdapter();

            return new BetterWpHooks(

                $container_adapter,

                $dispatcher = new WordpressDispatcher(

                    new ListenerFactory($container_adapter),
                    $wp_api = new WordpressApi()
                ),

                new WordpressEventMapper($container_adapter ,$dispatcher, $wp_api)

            );


        }

        private static function checkConfiguration()
        {

            if ( ! self::getInstance()) {

                throw new ConfigurationException(
                    'BetterWpHooks instance not created in '.static::class.'. '.
                    'Did you miss to call '.static::class.'::make()?'
                );

            }

        }

        private static function isTestAssertion(string $method) : bool
        {


            $methods = [

                'assertDispatched',
                'assertDispatchedTimes',
                'assertNotDispatched',
                'assertNothingDispatched',

            ];

            return in_array($method, $methods);

        }

        private static function unwrapIfMultidimensional(array $array) : array
        {

            return ( ! empty($array) && is_array($array[0])) ? $array[0] : $array;
        }

        private static function isEventObject($event_object) : bool
        {

            return ! hasTrait(BetterWpHooksFacade::class, $event_object);

        }


    }
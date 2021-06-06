<?php


    declare(strict_types = 1);


    namespace BetterWpHooks;

    use BetterWpHooks\Dispatchers\WordpressDispatcher;
    use BetterWpHooks\Testing\FakeDispatcher;
    use Closure;
    use Contracts\ContainerAdapter;
    use Exception;

    /**
     * @codeCoverageIgnore
     *
     * Can be applied to a class via a "@mixin" annotation for better IDE support.
     * This class is not meant to be used in any other capacity.
     */
    final class Mixin
    {

        /**
         * Prevent class instantiation.
         */
        private function __construct()
        {
        }


        /**
         * Returns the Ioc Container Instance
         */
        public static function container() : ContainerAdapter
        {
        }

        /**
         *
         * Registers an array of listeners with the dispatcher instance
         * The provided array has to be an associative of triggers and listeners.
         *
         * @param  array  $listeners
         */
        public static function listeners(array $listeners)
        {
        }

        /**
         * Accepts an array of key value pairs where the key is the
         * name of a wordpress defined hook and the value is the full
         * class name of an event object.
         *
         * @param  array  $mapped_events
         */
        public static function map(array $mapped_events)
        {
        }

        /**
         * Bootstraps the instance of BetterWpHooks
         * and registers all provided listeners
         */
        public static function boot()
        {
        }


        /**
         * Returns the dispatcher instance
         *
         * @return WordpressDispatcher|FakeDispatcher
         */
        public static function dispatcher()
        {
        }


        /**
         * Register an event listener with the dispatcher.
         *
         * @param  string  $event
         * @param  string|array|Closure|callable|object  $callable
         *
         * @return void
         * @throws Exception
         */
        public static function listen(string $event, $callable) : void
        {
        }

        /**
         * Register an event listener with the dispatcher that
         * can not be removed.
         *
         * @param  string  $event
         * @param  string|array  $callable
         *
         * @return void
         */
        public static function unremovable(string $event, $callable)
        {
        }


        /** Checks if an Event has any registered callbacks.
         *
         * @param  string  $eventName
         *
         * @return bool
         */
        public static function hasListeners(string $eventName) : bool
        {
        }


        /**
         *
         * Check if a specific listener was created through the WordpressDispatcher
         *
         * @param  object|string|Closure  $listener
         * @param  string|object  $event
         *
         * @return bool
         */
        public static function hasListenerFor($listener, $event) : bool
        {
        }


        /** Remove one listener for a given event from the dispatcher.
         *
         * @param  string  $event
         * @param  string|object  $listener
         *
         * @return void
         */
        public static function forgetOne(string $event, $listener) :void
        {
        }

        /**
         * Assert if an event was dispatched based on a truth-test callback.
         *
         * @param  string|Closure  $event
         * @param  callable|int|null  $callback
         *
         * @return void
         */
        public static function assertDispatched($event, $callback = null) :void
        {
        }

        /**
         * Assert if an event was dispatched a number of times.
         *
         * @param  string  $event
         * @param  int  $times
         *
         */
        public static function assertDispatchedTimes(string $event, int $times = 1) :bool
        {
        }


        /**
         * Determine if an event was dispatched based on a truth-test callback.
         *
         * @param  string|Closure  $event
         * @param  callable|null  $callback
         *
         * @return void
         */
        public static function assertNotDispatched($event, callable $callback = null) :void
        {

        }

        /**
         * Assert that no events were dispatched.
         *
         * @return void
         */
        public static function assertNothingDispatched() :void
        {


        }

    }
	
	
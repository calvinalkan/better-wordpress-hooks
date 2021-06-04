<?php


    declare(strict_types = 1);


    namespace BetterWpHooks\Dispatchers;

    use BetterWpHooks\Alias;
    use BetterWpHooks\Contracts\Dispatcher;
    use BetterWpHooks\Exceptions\DuplicateListenerException;
    use BetterWpHooks\Exceptions\UnremovableListenerException;
    use BetterWpHooks\Key;
    use BetterWpHooks\ListenerFactory;
    use BetterWpHooks\Traits\DispatchesConditionally;
    use BetterWpHooks\Traits\StopsPropagation;
    use BetterWpHooks\WordpressApi;
    use Closure;
    use Exception;
    use Illuminate\Support\Arr;
    use Illuminate\Support\Str;
    use ReflectionException;

    use ReflectionMethod;

    use function BetterWpHooks\Functions\arrayFirst;
    use function BetterWpHooks\Functions\classNameIfClassExists;
    use function BetterWpHooks\Functions\hasTrait;
    use function BetterWpHooks\Functions\isClosure;
    use function BetterWpHooks\Functions\normalizeClassMethod;
    use function BetterWpHooks\Functions\resolveListenerFromClosure;

    class WordpressDispatcher implements Dispatcher
    {


        private $listener_factory;
        private $hook_api;

        private $listeners = [];

        private $aliases = [];

        private $unremovable = [];


        public function __construct(ListenerFactory $listener_factory, WordpressApi $hook_api = null)
        {

            $this->listener_factory = $listener_factory;
            $this->hook_api = $hook_api ?? new WordpressApi();

        }


        /**
         * @param  string  $eventName
         *
         * @return bool
         * @api
         *
         * Checks if an Event has any registered callbacks.
         *
         */
        public function hasListeners(string $eventName) : bool
        {

            return isset($this->listeners[$eventName]) || $this->hook_api->hasFilterFor($eventName);

        }

        /**
         * @param  null  $event_name
         *
         * @return array
         * @api
         *
         * Returns all Listeners that were registered via the
         * WordpressDispatcher
         *
         */
        public function getListeners($event_name = null) : array
        {

            if ( ! $event_name) {

                return $this->listeners;

            }

            return $this->listeners[$event_name] ?? [];


        }

        /**
         * @param  object|string|Closure  $listener
         * @param                          $event
         *
         * @return bool
         * @api
         *
         * Check if a listener was created through the WordpressDispatcher
         *
         */
        public function hasListenerFor($callable, $event) : bool
        {

            $key = $this->resolveAlias($event, $callable);

            return isset($this->listeners[$event][$key])
                && $this->hook_api->hasFilterFor(
                    $event,
                    $this->findClosureByAlias($event, $key)
                );

        }

        /**
         * @param  string  $event
         * @param  string|array|Closure|callable|object  $callable
         *
         * @return Closure
         * @throws ReflectionException
         * @api
         *
         * Register an event listener with the dispatcher.
         */
        public function listen(string $event, $callable, int $priority = 10) : Closure
        {

            $callable = Arr::wrap($callable);

            if (is_array(arrayFirst($callable))) {

                $callable = [$key = array_key_first($callable) => normalizeClassMethod($callable[$key])];

            }

            $this->hook_api->addFilter(

                $event,
                $callable = $this->createListener(
                    $event, array_key_first($callable), $callable
                ),
                $priority

            );

            return $callable;


        }

        /**
         * Dispatch an event and call all the listeners.
         *
         * @param  string|object  $event
         * @param  array  $payload
         *
         * @return mixed|void
         * @throws Exception
         * @api
         *
         */
        public function dispatch($event, ...$payload)
        {

            $payload = ( ! empty($payload) && is_array($payload[0])) ? $payload[0] : $payload;

            if ( ! $this->shouldDispatch($event)) {
                return;
            }

            [$event, $payload, $original_event_object ] = $this->parseEventAndPayload($event, $payload);

            $this->maybeStopPropagation($event);

            if ( ! $this->hasListeners( $event ) ) {

                return $this->determineDefault($payload, null, $original_event_object);

            }

            $filtered = $this->hook_api->applyFilter( $event, $payload );


            if ( $filtered === $payload || ! $this->isCorrectReturnValue($payload, $filtered, $original_event_object) ) {

                return $this->determineDefault($payload, $filtered, $original_event_object);

            }

            return $filtered;


        }


        /**
         *
         * Remove one listener for a given event from the dispatcher.
         *
         * @param  string  $event
         * @param  string|object  $listener
         *
         * @return void
         * @throws Exception
         * @api
         *
         *
         */
        public function forgetOne(string $event, $listener)
        {

            $key = $this->resolveAlias($event, $listener);

            if ((array_key_exists($key, $this->listeners[$event]))) {

                $closure = $this->listeners[$event][$key];

                $this->isRemovable($closure, $event);

                $this->hook_api->removeFilter($event, $closure);

                unset($this->listeners[$event][$key]);
                unset($this->aliases[$event] [array_search($key, $this->aliases[$event])]);


            }

        }

        /**
         *
         * Can be chained to the listen function to mark an event as unremovable
         *
         * @api
         */
        public function unremovable(string $event, $callable)
        {

            $closure = $this->listen($event, $callable);

            $this->unremovable[] = spl_object_hash($closure);

        }

        /**
         *
         * Checks if a registered closure is marked as unremovable
         * and throws exception if true.
         *
         * @param  Closure  $closure
         * @param  string  $event
         *
         * @throws UnremovableListenerException
         */
        private function isRemovable(Closure $closure, string $event)
        {

            if (collect($this->unremovable)->contains($hash = spl_object_hash($closure))) {

                throw new UnremovableListenerException(
                    'The Hook you tried to remove was marked as unremovable. You tried to remove the Hook: '.$this->findAliasByObjectHash($hash, $event));

            }


        }

        private function findAliasByObjectHash(string $obj_hash, string $event)
        {

            return collect($this->aliases[$event])
                ->filter(function ($key) use ($obj_hash) {

                    return $key === $obj_hash;

                })->keys()->first();

        }

        /**
         *
         * Searches the first registered listener that implements
         * the StopPropagation Trait.
         * If found all listeners are cleared and only the found one
         * is readded before the event gets dispatched.
         *
         * @param $event
         *
         * @throws Exception
         */
        private function maybeStopPropagation($event) : void
        {


            foreach ($this->getListeners($event) as $listener) {

                $underlying_callable = resolveListenerFromClosure($listener);

                if ($this->stopsPropagation($underlying_callable[0])) {

                    $this->forgetAllExpect($event, $listener);

                    break;


                }


            }

        }

        /**
         *
         * Checks if a provided listener uses
         * the StopPropagation Trait
         *
         * @param  string|object|Closure  $listener
         *
         * @return bool
         */
        private function stopsPropagation($listener) : bool
        {

            return hasTrait(StopsPropagation::class, $listener);


        }

        /**
         * @param $event
         * @param $listener
         *
         * @return bool
         * @throws ReflectionException
         */
        private function isDuplicate($event, $listener) : bool
        {

            if ( ! isset ($this->listeners[$event])) {
                return false;
            }

            $closure_args = resolveListenerFromClosure($listener);

            $matching_events = collect($this->listeners[$event])
                ->filter(function ($closure) use ($closure_args) {

                    return resolveListenerFromClosure($closure)[0] === $closure_args[0];

                })
                ->count();

            return $matching_events > 0;


        }

        /**
         *
         * Determine if an event should dispatch when
         * it uses DispatchesConditionally
         *
         * @param $event
         *
         * @return bool
         */
        private function shouldDispatch($event) : bool
        {

            if ( ! hasTrait(DispatchesConditionally::class, $event) || ! is_object($event)) {
                return true;
            }

            return call_user_func([$event, 'shouldDispatch']);

        }

        /**
         *
         * Determines how an alias is stored for a listener
         * and returns it.
         *
         * @param $event
         * @param $callable
         *
         * @return mixed|object|string
         */
        private function resolveAlias($event, $callable)
        {

            $key = $callable;

            if (is_array($callable)) {

                $key = normalizeClassMethod($callable);

            }

            if (is_array($callable) && Str::contains($callable[1], '*')) {

                $key = $callable[0];

            }

            if (is_string($callable) && Str::contains($callable, '*')) {

                $key = Str::before($callable, '*');

            }

            if (is_string($callable) && Str::contains($callable, '@*')) {

                $key = Str::before($callable, '@*');

            }

            if (is_object($callable) && ! isClosure($callable)) {

                $key = get_class($callable);

            }

            if (isClosure($callable)) {

                return spl_object_hash($callable);

            }

            return $this->aliases[$event][$key] ?? classNameIfClassExists($callable);

        }

        /**
         * Uses the alias of the listener to find and return
         * the object hash of the listener callable
         *
         * @param $event
         * @param $alias
         *
         * @return string
         */
        private function findClosureByAlias($event, $alias) : string
        {

            return spl_object_hash($this->listeners[$event][$alias]);

        }

        private function isCorrectReturnValue($payload, $filtered, ?object $original_event_object) : bool
        {
            $event_object = is_object($payload)
                ? $payload
                : $original_event_object;

            if ( $event_object === null ) {
                return true;
            }

            if ( ! is_callable([$event_object, 'default'])) {

                return true;

            }

            $method = new ReflectionMethod($event_object, 'default');

            if ( ! $method->hasReturnType()) {

                return true;

            }

            $expected = $method->getReturnType()->getName();

            return $expected === gettype($filtered);

        }

        private function determineDefault($payload, $filtered, ?object $original_event_object)
        {

            if (is_callable([$payload, 'default'])) {

                return $payload->default($payload, $filtered);

            }

            if ( $original_event_object && is_callable([$original_event_object, 'default'] ) ) {

                return $original_event_object->default($payload, $filtered);

            }

            return is_object($payload) ? $payload : $payload[0];

        }

        /**
         * Parse the given event and payload and prepare them for dispatching.
         * If the event that got dispatched is an object we will use the classname as the event and
         * object as the payload
         *
         * @param  mixed  $event
         * @param  mixed  $payload
         *
         * @return array
         */
        private function parseEventAndPayload($event, $payload) : array
        {
            $original_event_object = null;

            if (is_object($event)) {

                $payload = method_exists($event, 'payload') ? $event->payload() :$event;

                if ( ! is_object( $payload ) ) {
                    $original_event_object = $event;
                }

                [$payload, $event] = [$payload, get_class($event)];

            }

            return [$event, $payload, $original_event_object];


        }

        /**
         *
         * Removes all Listeners for the the event expect the provided one.
         *
         * @param $event
         * @param $expect_listener
         *
         * @throws Exception
         */
        private function forgetAllExpect($event, $expect_listener)
        {


            foreach ($this->listeners[$event] as $listener) {

                if ($listener === $expect_listener) {
                    continue;
                }

                $this->forgetOne($event, $listener);


            }


        }

        /**
         *
         * Creates a AbstractListener if its not a duplicate
         *
         * @param         $event
         * @param         $key
         * @param  array  $listener
         *
         * @return Closure
         * @throws ReflectionException|DuplicateListenerException
         */
        private function createListener($event, $key, array $listener) : Closure
        {

            $listener = $this->listener_factory->create($listener);

            if ($this->isDuplicate($event, $listener)) {

                throw new DuplicateListenerException('You cant register two identical callbacks for the same event.');

            }

            $key = $this->createKey($event, $listener, $key);

            return $this->listeners[$event][$key] = $listener;


        }

        /**
         *
         * Create a key and alias for a closure that can later be retrieved
         *
         * @param  string  $event
         * @param  Closure  $listener
         * @param  string|int  $key
         *
         * @return string
         */
        private function createKey(string $event, Closure $listener, $key) : string
        {


            $aliases = collect(Alias::create($listener, $key));
            $key = Key::create($listener, $key);

            $aliases->each(function ($alias) use ($event, $key) {

                $this->aliases[$event][$alias] = $key;

            });

            return $key;

        }

    }
<?php


    declare(strict_types = 1);


    namespace BetterWpHooks\Testing;

    use BetterWpHooks\Contracts\Dispatcher;
    use Closure;
    use Illuminate\Support\Arr;
    use Illuminate\Support\Collection;
    use Illuminate\Support\Traits\ReflectsClosures;
    use PHPUnit\Framework\Assert as PHPUnit;
    use ReflectionException;

    class FakeDispatcher implements Dispatcher
    {

        use ReflectsClosures;

        /**
         * The original dispatcher
         *
         * @var Dispatcher
         */
        private $dispatcher;


        /**
         * The events that should be faked
         *
         * @var array
         */
        private $events_to_fake;

        /**
         * All of the events that have been intercepted keyed by type.
         *
         * @var array
         */
        private $events = [];

        public function __construct(Dispatcher $dispatcher, array $events_to_fake = [])
        {

            $this->dispatcher = $dispatcher;

            $this->events_to_fake = $events_to_fake;
        }

        /**
         * Fire an event and call the listeners.
         *
         * @param  string|object  $event
         * @param  mixed  $payload
         *
         *
         * @return mixed|null
         *
         */
        public function dispatch($event, $payload = [])
        {

            $name = is_object($event) ? get_class($event) : (string) $event;

            $this->events[$name][] = func_get_args();

            if ( ! $this->shouldFakeEvent($name, $payload)) {

                return $this->dispatcher->dispatch($event, $payload);

            }

        }

        public function allDispatchedEvents() : array
        {

            return $this->events;
        }

        /**
         * @param  string  $class
         *
         * @return array
         */
        public function allOfType(string $class) : array
        {

            $events = collect($this->events)
                ->filter(function ($events, $name) use ($class) {

                    return $name === $class;

                })
                ->flatten(1)
                ->map(function (array $event) use ($class) {

                    if ( ! $event[0] instanceof $class && $event[0] !== $class ) {
                        return null;
                    }

                    if (  $event[0] instanceof $class) {
                        return  $event[0];
                    }

                    return $event;



                });

            return  $events->all();
        }

        /**
         * Assert if an event was dispatched based on a truth-test callback.
         *
         * @param  string|Closure  $event
         * @param  callable|int|null  $callback
         *
         * @return void
         * @throws ReflectionException
         */
        public function assertDispatched($event, $callback = null, string $message = null)
        {

            if ($event instanceof Closure) {
                [$event, $callback] = [$this->firstClosureParameterType($event), $event];
            }

            if (is_int($callback)) {

                $this->assertDispatchedTimes($event, $callback);

                return;

            }

            PHPUnit::assertTrue(
                $this->dispatched($event, $callback)->count() > 0,
                $message ?? "The expected [{$event}] event was not dispatched."
            );

        }

        /**
         * Assert if an event was dispatched a number of times.
         *
         * @param  string  $event
         * @param  int  $times
         *
         */
        public function assertDispatchedTimes($event, $times = 1, string $message = null)
        {

            $count = $this->dispatched($event)->count();

            PHPUnit::assertSame(
                $times, $count,
                $message ?? "The expected [{$event}] event was dispatched {$count} times instead of {$times} times."
            );

        }

        /**
         * Determine if an event was dispatched based on a truth-test callback.
         *
         * @param  string|Closure  $event
         * @param  callable|null  $callback
         *
         * @return void
         * @throws ReflectionException
         */
        public function assertNotDispatched($event, $callback = null, string $message = null)
        {

            if ($event instanceof Closure) {
                [$event, $callback] = [$this->firstClosureParameterType($event), $event];
            }

            PHPUnit::assertCount(
                0, $this->dispatched($event, $callback),
                $message ?? "The unexpected [{$event}] event was dispatched."
            );
        }

        /**
         * Assert that no events were dispatched.
         *
         * @return void
         */
        public function assertNothingDispatched()
        {

            $count = count(Arr::flatten($this->events));

            PHPUnit::assertSame(
                0, $count,
                "{$count} unexpected events were dispatched."
            );
        }

        /**
         * Determine if an event should be faked or actually dispatched.
         *
         * @param  string  $eventName
         * @param  mixed  $payload
         *
         * @return bool
         */
        private function shouldFakeEvent(string $eventName, $payload) : bool
        {

            if (empty($this->events_to_fake)) {

                return true;

            }

            return collect($this->events_to_fake)
                ->filter(function ($event) use ($eventName, $payload) {

                    return $event instanceof Closure
                        ? $event($eventName, $payload)
                        : $event === $eventName;

                })
                ->isNotEmpty();
        }

        /**
         * Determine if the given event has been dispatched.
         *
         * @param  string  $event
         *
         * @return bool
         */
        private function hasDispatched($event) : bool
        {

            return isset($this->events[$event]) && ! empty($this->events[$event]);
        }

        /**
         * Get all of the events matching a truth-test callback.
         *
         * @param  string  $event
         * @param  callable|null  $callback
         *
         * @return Collection
         */
        private function dispatched(string $event, $callback = null) : Collection
        {

            if ( ! $this->hasDispatched($event)) {
                return collect();
            }

            $callback = $callback ? : function () {

                return true;
            };

            return collect($this->events[$event])->filter(function ($arguments) use ($callback) {

                return $callback(...$arguments);
            });
        }

        public function clearDispatchedEvents()
        {
            $this->events = [];
        }


    }
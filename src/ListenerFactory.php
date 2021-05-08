<?php


    namespace BetterWpHooks;

    use BetterWpHooks\Contracts\AbstractListener;
    use BetterWpHooks\Contracts\ErrorHandler;
    use BetterWpHooks\Exceptions\InvalidListenerException;
    use BetterWpHooks\Exceptions\ExecutionErrorHandler;
    use BetterWpHooks\Listeners\ClassListener;

    use BetterWpHooks\Listeners\ClosureListener;
    use BetterWpHooks\Listeners\InstanceListener;
    use BetterWpHooks\Listeners\InterfaceListener;
    use BetterWpHooks\Traits\ReflectsCallable;
    use Closure;
    use Contracts\ContainerAdapter;
    use Illuminate\Support\Arr;
    use Illuminate\Support\Reflector;
    use Illuminate\Support\Str;
    use SniccoAdapter\BaseContainerAdapter;

    use function BetterWpHooks\Functions\arrayFirst;
    use function BetterWpHooks\Functions\isClosure;
    use function BetterWpHooks\Functions\isInitializedClass;
    use function BetterWpHooks\Functions\normalizeClassMethod;

    class ListenerFactory
    {

        use ReflectsCallable;

        private $container;

        /**
         * @var null|\BetterWpHooks\Contracts\ErrorHandler
         */
        private $error_handler;


        public function __construct(ContainerAdapter $container = null, ErrorHandler $error_handler = null)
        {

            $this->container = $container ?? new BaseContainerAdapter();

            $this->error_handler = $error_handler ?? new ExecutionErrorHandler();

        }

        /**
         *
         * Factory Method that builds a new Instance of an
         * AbstractListener
         *
         * @param  array  $listener
         *
         * @return \Closure
         * @throws \Exception
         */
        public function create(array $listener) : Closure
        {


            if ($this->isInterfaceListener($listener)) {

                return $this->wrap(new InterfaceListener($listener, $this->container));

            }

            if (isInitializedClass(arrayFirst($listener))) {

                return $this->wrap(new InstanceListener($listener, $this->container));

            }

            if (isClosure($closure = arrayFirst($listener))) {

                return $this->wrap(new ClosureListener($closure, $this->container));

            }

            if ($this->isClassListener($listener)) {

                return $this->wrap(new ClassListener($listener, $this->container));

            }

            throw new InvalidListenerException('Invalid listener ['.implode(', ', Arr::flatten($listener)).'] provided.');


        }


        /**
         *
         * Check if the provided array has the format of
         * a class listener
         *
         * @param  array  $listener
         *
         * @return bool
         */
        private function isClassListener(array $listener) : bool
        {


            $callable = normalizeClassMethod($listener, 'handleEvent');

            return Reflector::isCallable(Str::parseCallback($callable));

        }


        /**
         * Wraps the created abstract listener in a closure.
         * The Wordpress Hook Api will save this closure as
         * the Hook Callback and execute it at runtime.
         *
         * @param  \BetterWpHooks\Contracts\AbstractListener  $listener
         *
         * @return \Closure
         */
        private function wrap(AbstractListener $listener) : Closure
        {

            return function ($payload) use ($listener) {

                try {

                    return $listener->shouldHandle($payload) ? $listener->execute($payload) : $payload;

                }
                catch (\Throwable $e) {

                    $this->error_handler->handle($e);

                }


            };

        }

        private function isInterfaceListener(array $listener) : bool
        {

            $callable = Str::parseCallback(normalizeClassMethod($listener, 'handleEvent'));

            if ( ! interface_exists($callable[0]) || ! $this->container->offsetExists($callable[0])) {

                throw new InvalidListenerException(
                    'Invalid interface listener ['.implode(', ', Arr::flatten($callable)).'] provided.'
                );

            }

            return true;

        }


    }
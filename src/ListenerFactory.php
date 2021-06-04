<?php


    namespace BetterWpHooks;

    use BetterWpHooks\Contracts\AbstractListener;
    use BetterWpHooks\Contracts\ErrorHandler;
    use BetterWpHooks\Exceptions\InvalidListenerException;
    use BetterWpHooks\Exceptions\ExecutionErrorHandler;
    use BetterWpHooks\Listeners\ClassListener;

    use BetterWpHooks\Listeners\ClosureListener;
    use BetterWpHooks\Listeners\InstanceListener;
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
         * @return Closure
         * @throws \Exception
         */
        public function create(array $listener) : Closure
        {


            if (isInitializedClass(arrayFirst($listener))) {

                return $this->wrap(new InstanceListener($listener, $this->container));

            }

            if (isClosure($closure = arrayFirst($listener))) {

                return $this->wrap(new ClosureListener($closure, $this->container));

            }

            if ($this->isClassListener($listener)) {

                return $this->wrap(new ClassListener($listener, $this->container));

            }

            if ($this->isInvokeable($listener)) {

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
         * @param  AbstractListener  $listener
         *
         * @return Closure
         */
        private function wrap(AbstractListener $listener) : Closure
        {

            return function ($payload) use ($listener) {

                try {

                    $payload = $this->stripEmptyString($payload);

                    return $listener->shouldHandle($payload) ? $listener->execute($payload) : $payload;

                }
                catch (\Throwable $e) {

                    $this->error_handler->handle($e);

                }


            };

        }

        /**
         *
         * Wordpress adds an empty string to hooks args without parameters like do_action('init').
         * This will result in the payload being [0=>''] which might mess up our dynamic payload
         * building in some edge cases.
         *
         * @see https://github.com/WordPress/WordPress/blob/master/wp-includes/plugin.php#L464
         *
         * @param $payload
         *
         * @return array|mixed
         */
        private function stripEmptyString($payload)
        {

            if ($payload === '') {

                return [];

            }

            return $payload;

        }

        private function isInvokeable(array &$listener) : bool
        {

            $class = arrayFirst($listener);

            if ( ! method_exists($class, '__invoke') ) {
                return false;
            }

            $listener[] = '__invoke';

            return true;

        }


    }
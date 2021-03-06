<?php


    declare(strict_types = 1);


    namespace BetterWpHooks\Listeners;

    use BetterWpHooks\Traits\BetterWpHooksFacade;
    use BetterWpHooks\WordpressApi;
    use Closure;
    use Illuminate\Support\Arr;
    use WP_Hook;

    class ContainedListener
    {

        /**
         * @var Closure
         */
        private $listener;

        /**
         * @var string
         */
        private $event;
        /**
         * @var int
         */
        private $min_priority;

        /**
         * @var string
         */
        private $identifier;

        /** @var WordpressApi */
        private $hook_api;

        private $raw_listener;

        public function __construct(string $event, Closure $listener_executable, $raw_listener, $hook_api)
        {

            $this->listener = $listener_executable;
            $this->event = $event;
            $this->min_priority = PHP_INT_MIN;
            $this->identifier = spl_object_hash($this).'__invoke';
            $this->hook_api = $hook_api;
            $this->raw_listener = $raw_listener;
        }


        public function __invoke(...$args_from_wp)
        {

            if ( $this->isMappedToEventObject() ) {

                $event = $this->raw_listener[0];
                return call_user_func_array([$event, 'mapEvent'], $args_from_wp);

            }

            return call_user_func_array($this->listener, $args_from_wp);

        }

        public function registerFirst()
        {

            /** @var WP_Hook $hook */
            $hook = $this->getCurrentHookObject();

            $this->hook_api->addFilter($this->event, [$this, '__invoke'], $this->min_priority);

            $callbacks = $hook->callbacks[$this->min_priority] ?? [];

            // No hook other hook registered for PHP_MIN_INT
            // Every hook that might get registered for this priority will
            // run later by default.
            if ( ! $hook || count($callbacks) === 1) {

                return;

            }

            $cb_to_remove = Arr::except($callbacks, $this->identifier);

            $this->removeAll($cb_to_remove);
            $this->reAdd($cb_to_remove);


        }

        public function registerLast()
        {

            $this->hook_api->addFilter($this->event, [$this, 'reorderAtRuntime'], $this->min_priority);

        }

        public function reorderAtRuntime(...$args_from_wp)
        {

            $hook = $this->getCurrentHookObject();
            $callbacks = $hook->callbacks;


            $current_highest_priority = array_key_last($callbacks);

            if ( $current_highest_priority !== PHP_INT_MIN ) {

                $cb_to_remove = Arr::get($callbacks, $current_highest_priority, []);
                $this->removeAll($cb_to_remove, $current_highest_priority);
                $this->reAdd($cb_to_remove, $current_highest_priority - 1 );

            }

            $priority = $current_highest_priority === PHP_INT_MIN ? 10 : $current_highest_priority;

            $this->hook_api->addFilter(
                $this->event,
                [$this, '__invoke'],
                $priority,
            );

            return $args_from_wp[0];

        }

        private function getCurrentHookObject()
        {

            return $GLOBALS['wp_filter'][$this->event] ?? null;

        }

        private function removeAll(array $callbacks, ?int $priority = null ) {

            foreach ($callbacks as $callback) {

                $this->hook_api->removeFilter(
                    $this->event,
                    $callback['function'],
                    $priority ?? $this->min_priority
                );

            }

        }

        private function reAdd(array $callbacks, ?int $priority = null) {

            foreach ($callbacks as $callback ) {

                $this->hook_api->addFilter(
                    $this->event,
                    $callback['function'],
                    $priority ?? $this->min_priority,
                    $callback['accepted_args']
                );
            }

        }

        private function isMappedToEventObject() :bool {

            if ( is_callable( [$this->raw_listener[0], 'mapEvent'] ) ) {

                return true;

            }

            return false;


        }

    }
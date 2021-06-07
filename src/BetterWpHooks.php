<?php


    declare(strict_types = 1);


    namespace BetterWpHooks;

    use BetterWpHooks\Contracts\EventMapper;
    use BetterWpHooks\Contracts\Dispatcher;
    use BetterWpHooks\Dispatchers\WordpressDispatcher;
    use BetterWpHooks\Exceptions\ConfigurationException;
    use BetterWpHooks\Mappers\WordpressEventMapper;
    use Contracts\ContainerAdapter;
    use Throwable;

    class BetterWpHooks
    {


        /**
         * @var ContainerAdapter
         */
        private $container_adapter;

        /**
         * @var WordpressDispatcher
         */
        private $dispatcher;

        /**
         * @var WordpressEventMapper
         */
        private $event_mapper;

        private $listen        = [];
        private $mapped_events = [];
        private $ensure_first  = [];
        private $ensure_last   = [];

        public function __construct(
            ContainerAdapter $container_adapter, Dispatcher $dispatcher,
            EventMapper $event_mapper
        ) {

            $this->container_adapter = $container_adapter;
            $this->dispatcher = $dispatcher;
            $this->event_mapper = $event_mapper;

        }

        public function listeners(array $listeners = []) : BetterWpHooks
        {

            $this->listen = $listeners ?? [];

            return $this;

        }

        public function map(array $mapped_events = []) : BetterWpHooks
        {

            $this->mapped_events = $mapped_events ?? [];

            return $this;

        }

        public function ensureFirst(array $mapped_events) : BetterWpHooks
        {

            $this->ensure_first = $mapped_events ?? [];

            return $this;

        }

        public function ensureLast(array $mapped_events) : BetterWpHooks
        {

            $this->ensure_last = $mapped_events ?? [];

            return $this;

        }

        public function boot()
        {
            $this->mapEvents();
            $this->mapEnsureFirst();
            $this->mapEnsureLast();
            $this->registerListeners();
        }

        public function container() : ContainerAdapter
        {

            return $this->container_adapter;

        }

        public function dispatcher() : Dispatcher
        {

            return $this->dispatcher;

        }

        public function swapDispatcher(Dispatcher $new_dispatcher, bool $swap_in_container)
        {

            $this->dispatcher = $new_dispatcher;

            if ( ! $swap_in_container) {

                return;

            }

            if ($this->container()->offsetExists(WordpressDispatcher::class)) {

                $this->container()->instance(WordpressDispatcher::class, $new_dispatcher);

            }

            if ($this->container()->offsetExists(Dispatcher::class)) {

                $this->container()->instance(Dispatcher::class, $new_dispatcher);

            }


        }

        private function mapEvents()
        {

            try {

                foreach ($this->mapped_events as $hook_name => $mapped_events) {

                    if ( ! $hook_name) {

                        throw new ConfigurationException('No hook provided for event.');

                    }

                    $mapped_events = is_array($mapped_events) ? $mapped_events : [$mapped_events];

                    foreach ($mapped_events as $mapped_event) {

                        $this->event_mapper->map($hook_name, $mapped_event);


                    }


                }

            }
            catch (Throwable $e) {

                throw new ConfigurationException('Invalid Data was provided for event-mapping:'.PHP_EOL.$e->getMessage());

            }


        }

        private function registerListeners()
        {

            try {

                foreach ($this->listen as $event => $listeners) {

                    foreach ($listeners as $alias => $listener) {

                        $this->dispatcher->listen($event, [$alias => $listener]);

                    }


                }

            }

            catch (Throwable $e) {

                throw new ConfigurationException('Invalid Data was provided for a listener: '.$e->getMessage());

            }


        }

        private function mapEnsureFirst()
        {

            foreach ($this->ensure_first as $hook => $event_objects ) {

                $event_objects = is_array($event_objects) ? $event_objects : [$event_objects];

                foreach ($event_objects as $event_object) {

                    $this->event_mapper->mapFirst($hook, $event_object);

                }


            }


        }

        private function mapEnsureLast()
        {

            foreach ($this->ensure_last as $hook => $event_objects ) {

                $event_objects = is_array($event_objects) ? $event_objects : [$event_objects];

                foreach ($event_objects as $event_object) {

                    $this->event_mapper->mapLast($hook, $event_object);


                }


            }

        }



    }
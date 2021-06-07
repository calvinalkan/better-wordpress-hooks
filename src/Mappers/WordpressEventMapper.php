<?php


    declare(strict_types = 1);


    namespace BetterWpHooks\Mappers;
	
	use BetterWpHooks\Contracts\Dispatcher;
    use BetterWpHooks\Contracts\EventMapper;
    use BetterWpHooks\Dispatchers\WordpressDispatcher;
    use BetterWpHooks\Exceptions\ConfigurationException;
    use BetterWpHooks\Traits\IsAction;
    use BetterWpHooks\WordpressApi;
    use Closure;
    use Contracts\ContainerAdapter;
    use Illuminate\Support\Arr;
    use ReflectionPayload\ReflectionPayload;

    use function BetterWpHooks\Functions\arrayFirst;

    class WordpressEventMapper implements EventMapper {


        /**
		 * @var WordpressApi
		 */
		private  $wp_api;

        /**
         * @var ContainerAdapter
         */
        private $container;

        /**
         * @var WordpressDispatcher
         */
        private $dispatcher;

        /** @var array */
        private $mapped_events;

        public function __construct( ContainerAdapter $container, Dispatcher $dispatcher, WordpressApi $wp_api = null ) {
			
			$this->wp_api = $wp_api ?? new WordpressApi();
            $this->container = $container;
            $this->dispatcher = $dispatcher;
        }

        /**
         *
         * @param  string  $hook_name
         * @param  array|callable  $event
         * @param  int  $priority
         *
         * @throws ConfigurationException
         */
		public function map( string $hook_name, $event, int $priority = 10 ) {


            $event = $this->normalize($event);

            if ( isset( $this->mapped_events[ $hook_name ] )) {

                $this->checkHookCompatibility($hook_name, $event[0]);

            }

            $priority = $event[1] ?? $priority;

            $this->wp_api->addFilter($hook_name, [$event[0], 'mapEvent'], $priority);

			$this->mapped_events[$hook_name][] = $event[0];

		}

		public function mapLast ( string $hook_name, string $event ) {

            if ( isset( $this->mapped_events[ $hook_name ] )) {

                $this->checkHookCompatibility($hook_name, $event);

            }

            $this->dispatcher->ensureLast($hook_name, [$event, 'mapEvent']);

        }

		public function mapFirst ( string $hook_name, string $event ) {

            if ( isset( $this->mapped_events[ $hook_name ] )) {

                $this->checkHookCompatibility($hook_name, $event);

            }

		    $this->dispatcher->ensureFirst($hook_name, [$event, 'mapEvent']);

        }

        private function normalize ( $mapped_event ) : array
        {

            $mapped_event = Arr::wrap($mapped_event);

            return is_array($mapped_event[0]) ? $mapped_event[0] : $mapped_event;

        }

        private function checkHookCompatibility(string $hook_name, $event_to_be_mapped)
        {

            if ( ! $this->wp_api->isAction( $event_to_be_mapped ) ) {

                throw new ConfigurationException(
                    "You are trying to map more than one event to the hook [$hook_name] but the event: [$event_to_be_mapped] is not an action."
                );

            }

            if ( ! $this->wp_api->isAction(arrayFirst($first = $this->mapped_events[$hook_name][0] ) ) ) {

                throw new ConfigurationException(
                    "You are trying to map a second event to the hook [$hook_name] but the first event: [$first] is a WP filter."
                );

            }

        }



    }
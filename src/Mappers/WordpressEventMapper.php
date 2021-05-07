<?php
	
	namespace BetterWpHooks\Mappers;
	
	use BetterWpHooks\Contracts\Dispatcher;
    use BetterWpHooks\Contracts\EventMapper;
    use BetterWpHooks\Traits\ReflectsCallable;
    use BetterWpHooks\WordpressApi;
    use Contracts\ContainerAdapter;
    use Illuminate\Support\Arr;

    class WordpressEventMapper implements EventMapper {

        use ReflectsCallable;

        const resolve_key = 'resolve';


        /**
		 * @var WordpressApi
		 */
		private  $wp_api;

        /**
         * @var ContainerAdapter
         */
        private $container;

        /** @var Dispatcher */
        private $dispatcher;

        public function __construct( ContainerAdapter $container, WordpressApi $wp_api = null ) {
			
			$this->wp_api = $wp_api ?? new WordpressApi();
            $this->container = $container;
        }
		
		
		/**
		 *
		 * @param  string  $hook_name
		 * @param  array|callable  $event
		 * @param  int     $priority
		 */
		public function listen( string $hook_name, $event, int $priority = 10 ) {

		    $event = $this->normalize($event);

			$priority = $event[1] ?? $priority;
			
			$hook = $this->makeHook( $hook_name, $event[0] , $priority );

			$this->wp_api->addFilter(...$hook);
			
			
		}
		
		
		private function makeHook( $hook_name, $event, $priority ): array {

		    $callable = is_callable($event) ? $event : [ $event, 'mapEvent'];

			return [  $hook_name, $callable , $priority, 99   ];
			
		}

        private function buildResolvableForMappedEvent(string $hook_name, array $mapped_event)
        {


            $resolvable = function (...$args_from_wp) use ($class) {

                $payload = $this->buildNamedConstructorArgs($class, $args_from_wp);

                $event_object = $this->container->make($class, $payload);

                return $this->dispatcher()->dispatch($event_object);

            };


        }

        private function normalize ( $mapped_event ) {

            $mapped_event = Arr::wrap($mapped_event);

            return is_array($mapped_event[0]) ? $mapped_event[0] : $mapped_event;

        }
		
		
		
	}
<?php
	
	namespace BetterWpHooks\Mappers;
	
	use BetterWpHooks\Contracts\Dispatcher;
    use BetterWpHooks\Contracts\EventMapper;
    use BetterWpHooks\Traits\ReflectsCallable;
    use BetterWpHooks\WordpressApi;
    use Closure;
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

        /**
         * @var Dispatcher
         */
        private $dispatcher;

        public function __construct( ContainerAdapter $container, Dispatcher $dispatcher, WordpressApi $wp_api = null ) {
			
			$this->wp_api = $wp_api ?? new WordpressApi();
            $this->container = $container;
            $this->dispatcher = $dispatcher;
        }
		
		
		/**
		 *
		 * @param  string  $hook_name
		 * @param  array|callable  $event
		 * @param  int     $priority
		 */
		public function listen( string $hook_name, $event, int $priority = 10 ) {

            $event = $this->normalize($event);

		    if ( $resolve_from_container = $event[0] === self::resolve_key ) {

		        array_shift($event);

            }


			$priority = $event[1] ?? $priority;
			
			$callable = $this->makeCallable( $event[0] , $resolve_from_container );

			$this->wp_api->addFilter($hook_name, $callable, $priority);
			
			
		}

        /**
         * @param  string  $event
         * @param  bool  $from_container
         *
         * @return Closure|array|
         */
		private function makeCallable( string $event, bool $from_container = false  ) {

		    if ( ! $from_container ) {

		        return [ $event, 'mapEvent'];

            }

		    return $this->buildResolvableForMappedEvent($event);

			
		}

        private function buildResolvableForMappedEvent(string $event) : Closure
        {

            return function (...$args_from_wp) use ($event) {

                $payload = $this->buildNamedConstructorArgs($event, $args_from_wp);

                $event_object = $this->container->make($event, $payload);

                return $this->dispatcher->dispatch($event_object);

            };



        }

        private function normalize ( $mapped_event ) : array
        {

            $mapped_event = Arr::wrap($mapped_event);

            return is_array($mapped_event[0]) ? $mapped_event[0] : $mapped_event;

        }
		
		
		
	}
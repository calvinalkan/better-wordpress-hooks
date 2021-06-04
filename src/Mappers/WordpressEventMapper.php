<?php


    declare(strict_types = 1);


    namespace BetterWpHooks\Mappers;
	
	use BetterWpHooks\Contracts\Dispatcher;
    use BetterWpHooks\Contracts\EventMapper;
    use BetterWpHooks\WordpressApi;
    use Closure;
    use Contracts\ContainerAdapter;
    use Illuminate\Support\Arr;
    use ReflectionPayload\ReflectionPayload;

    class WordpressEventMapper implements EventMapper {


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

                $args = collect($args_from_wp)->reject(function ( $arg )  {

                    return empty($arg);

                });

                $payload = new ReflectionPayload($event, $args->all());

                $event_object = $this->container->make($event, $payload->build());

                return $this->dispatcher->dispatch($event_object);

            };



        }

        private function normalize ( $mapped_event ) : array
        {

            $mapped_event = Arr::wrap($mapped_event);

            return is_array($mapped_event[0]) ? $mapped_event[0] : $mapped_event;

        }
		
		
		
	}
<?php


    declare(strict_types = 1);


    namespace BetterWpHooks\Listeners;
	
	use BetterWpHooks\Contracts\AbstractListener;
	use Contracts\ContainerAdapter;
    use ReflectionPayload\ReflectionPayload;

    class InstanceListener extends AbstractListener {
		

		/**
		 * @var array
		 */
		private $instance;
		
		/**
		 * @var ContainerAdapter
		 */
		private $container;
		
		public function __construct( array $instance, ContainerAdapter $container ) {
			
			$this->instance  = array_values( $instance );
			$this->container = $container;
			
		}
		
		private function callInstanceMethod( $payload, $method = NULL ) {
			
			$method = $method ?? $this->instance[1] ?? 'handleEvent';

			$payload = new ReflectionPayload([$this->instance[0], $method], $payload);
			$payload = $payload->build();

			return $this->container->call(
			    [ $this->instance[0], $method ],
                $payload
			);
			
		}
		
		public function execute( $payload ) {
			
			return $this->callInstanceMethod( $payload );
			
		}
		
		public function toArray(): array {
			
			return $this->instance;
			
		}
		
		public function aliases(): array {
			
			return [ spl_object_hash( $this->instance[0] ), get_class( $this->instance[0] ) ];
			
		}
		
		public function shouldHandle( $payload ): bool {
			
			if ( ! $hasTrait = $this->hasConditionalTrait( $this->instance[0] ) ) {
				return TRUE;
			}
			
			return $hasTrait && $this->callInstanceMethod( $payload, 'shouldHandle' );
			
		}
		
		
	}
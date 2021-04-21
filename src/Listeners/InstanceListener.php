<?php
	
	namespace BetterWpHooks\Listeners;
	
	use BetterWpHooks\Contracts\AbstractListener;
	use BetterWpHooks\Traits\ReflectsCallable;
	use Contracts\ContainerAdapter;
	
	class InstanceListener extends AbstractListener {
		
		use ReflectsCallable;
		
		/**
		 * @var array
		 */
		private $instance;
		
		/**
		 * @var \Contracts\ContainerAdapter
		 */
		private $container;
		
		public function __construct( array $instance, ContainerAdapter $container ) {
			
			$this->instance  = array_values( $instance );
			$this->container = $container;
			
		}
		
		private function callInstanceMethod( $payload, $method = NULL ) {
			
			$method = $method ?? $this->instance[1] ?? 'handleEvent';
			
			return $this->container->call( $cb = [ $this->instance[0], $method ], $this->buildParameterNames($cb, $payload)
			
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
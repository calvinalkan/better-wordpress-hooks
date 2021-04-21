<?php
	
	namespace BetterWpHooks\Listeners;
	
	use BetterWpHooks\Contracts\AbstractListener;
	use BetterWpHooks\Traits\ReflectsCallable;
	use Contracts\ContainerAdapter;
	
	use function BetterWpHooks\Functions\normalizeClassMethod;
	
	class ClassListener extends AbstractListener {
		
		use ReflectsCallable;
		
		/**
		 * @var array
		 */
		private $class_callable;
		
		/**
		 * @var \Contracts\ContainerAdapter
		 */
		private $container;
		
		public function __construct( array $listener, ContainerAdapter $container ) {
			
			$this->class_callable = array_values( $listener );
			$this->container      = $container;
			
		}
		
		
		public function toArray(): array {
			
			return $this->class_callable;
			
		}
		
		
		public function execute( $payload ) {
			
			return $this->callClassMethod( $this->class_callable, $payload );
			
		}
		
		
		public function aliases(): array {
			
			return [
				
				normalizeClassMethod( $this->class_callable, 'handleEvent' ),
				$this->class_callable[0],
			
			];
			
		}
		
		
		public function shouldHandle( $payload ): bool {
			
			if ( ! $hasTrait = $this->hasConditionalTrait( $this->class_callable[0] ) ) {
				return TRUE;
			}
			
			return $hasTrait && $this->callClassMethod( [ $this->class_callable[0], 'shouldHandle' ], $payload );
			
			
		}
		
		
		private function callClassMethod( array $class_callable, $payload ) {
			
			$parameters = $this->buildParameterNames( $class_callable, $payload );
			
			return $this->container->call( normalizeClassMethod( $class_callable, 'handleEvent' ), $parameters );
			
			
		}
		
		
	}
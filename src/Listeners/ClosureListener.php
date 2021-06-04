<?php
	
	namespace BetterWpHooks\Listeners;
	
	use BetterWpHooks\Contracts\AbstractListener;
	use Contracts\ContainerAdapter;
    use ReflectionPayload\ReflectionPayload;

    class ClosureListener extends AbstractListener {
		

		/**
		 * @var \Closure
		 */
		private  $closure;
		
		/**
		 * @var \Contracts\ContainerAdapter
		 */
		private  $container;
		
		public function __construct( \Closure $closure, ContainerAdapter $container) {
			
			$this->closure = $closure;
			$this->container = $container;
			
		}
		
		public function toArray(): array {
			
			return [
				
				$this->closure,
				NULL,
			
			];
			
		}
		
		public function execute( $payload ) {
			
			$closure = $this->closure;

			$reflection_payload = new ReflectionPayload($closure, $payload);
            $payload = $reflection_payload->build();

			return $this->container->call( $closure , $payload );

			
		}
		
		public function aliases(): array {
			
			return [ spl_object_hash( $this->closure ) ];
			
		}
		
		public function shouldHandle( $payload ): bool {
			
			return TRUE;
			
		}
		
		
		
	}
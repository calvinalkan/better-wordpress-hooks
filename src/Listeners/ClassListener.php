<?php


    declare(strict_types = 1);


    namespace BetterWpHooks\Listeners;
	
	use BetterWpHooks\Contracts\AbstractListener;
	use Contracts\ContainerAdapter;

    use Illuminate\Support\Str;

    use ReflectionPayload\ReflectionPayload;

    use function BetterWpHooks\Functions\normalizeClassMethod;

    class ClassListener extends AbstractListener {
		

		/**
		 * @var array
		 */
		private $class_callable;
		
		/**
		 * @var ContainerAdapter
		 */
		private $container;
		
		public function __construct( array $listener, ContainerAdapter $container ) {
			
			$this->class_callable = $this->toArrayCallable( $listener );
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
			
            $reflection_payload = new ReflectionPayload($class_callable, $payload);
            $parameters = $reflection_payload->build();

			return $this->container->call( normalizeClassMethod( $class_callable, 'handleEvent' ), $parameters );
			
			
		}

		private function toArrayCallable( array $listener ) : array
        {

		    $listener = array_values($listener);

		    if ( ! isset($listener[1]) ) {

		        $listener[1] = 'handleEvent';

            }

		    if (Str::contains($listener[0],'@')) {

		        $listener = Str::parseCallback($listener[0]);

            }

		    return $listener;

        }
		
	}
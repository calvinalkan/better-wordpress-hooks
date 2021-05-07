<?php
	
	namespace BetterWpHooks\Traits;
	
	use Illuminate\Support\Str;
    use ReflectionException;
    use ReflectionMethod;
	
	use function BetterWpHooks\Functions\classExists;
	use function BetterWpHooks\Functions\isClosure;
	
	trait ReflectsCallable {

        /**
         * @throws ReflectionException
         */
        private function getCallReflector( $callback , $default_method) {
			
			if ( isClosure($callback ) ) return new \ReflectionFunction($callback);
			
			[ $class, $method ] = ( classExists($callback[0]) )
				? [ $callback[0], $callback[1] ?? $default_method  ]
				: Str::parseCallback($callback[0], $default_method);
			
			return new ReflectionMethod( $class , $method );
			
		}


        /**
         * @throws ReflectionException
         */
        private function buildParameterNames( $class_callable, $payload ) :array {
			
			$payload =  ( ! is_array($payload ) ) ? [ $payload ] : $payload;
			
			$call_reflector = $this->getCallReflector( $class_callable, 'handleEvent' );
			
			$params = collect( $call_reflector->getParameters() );
			
			$parameter_names = $params->map( function ( $param ) {
				return $param->getName();
			} );
			
			if ( $parameter_names->isEmpty() ) {
				
				return $payload;
				
			}
			
			$reduced = $parameter_names->slice( 0, count(  ($payload) ) );
			
			$payload = $reduced->combine( $payload );
			
			return $payload->toArray();
			
			
		}
		
	}
<?php
	
	namespace BetterWpHooks;
	
	use Closure;
	
	use function BetterWpHooks\Functions\getStaticClosureArguments;
	
	/**
	 * Class Alias
	 *
	 * @package BetterWpHooks
	 *
	 * Since the dispatcher saves listeners as $key => $value pairs
	 * where the value is the closure and the key is the object hash of the closure
	 * we need to save an alias for every key so clients dont need to know the
	 * object hash to target a specific closure listener in the dispatcher.
	 *
	 */
	class Alias {
		
		
		public static function create( Closure $callable, $key ): array {
			
			// User provided custom key.
			if ( is_string( $key ) ) {
				
				return [ $key ];
				
			}
			
			$listener = getStaticClosureArguments( new \ReflectionFunction( $callable ) );
			
			
			return $listener->aliases();
			
			
		}
		
	}
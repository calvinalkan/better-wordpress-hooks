<?php
	
	namespace BetterWpHooks;
	
	use Closure;
	use Illuminate\Support\Arr;
	use function BetterWpHooks\Functions\resolveListenerFromClosure;
	
	/**
	 *
	 * Class Key
	 *
	 * @package BetterWpHooks
	 *
	 * This class contains the logic for the generation of keys. 
	 * The keys will reference the registered closures in the dispatcher. 
	 * Wordpress stores keys for closures using spl_object_hash(). 
	 * 
	 * The generated key will point to a specific closure in the dispatcher.
	 * This allows to reference the Wordpress Hook Api with closures ( deleting/checking existence etc )
	 * which would not be possible without tracking the spl_object_hash()
	 * 
	 */
	class Key {
		
		
		/**
		 *
		 * Create a Key for the passed closure.
		 * This key has to be accessible by consumers of the api so
		 * we cant just use the object hash of the wrapping closure
		 * Instead we just the underlying callable under the closure to point back to
		 * wrapping closure.
		 *
		 * We cant delegate this behaviour to the AbstractListener Implementation
		 * because we need access to the object_hash of the wrapping closure.
		 *
		 *
		 * @param  \Closure  $callable
		 * @param            $key
		 *
		 * @return string
		 *
		 */
		public static function create( Closure $callable, $key ): string {
			
			
			// User provided a custom key. 
			if ( is_string( $key ) ) {
				
				return spl_object_hash( $callable );
				
			}
			
			$listener = Arr::wrap( resolveListenerFromClosure( $callable ) );
			
			if ( is_string( $listener[0] ) ) {
				
				return spl_object_hash( $callable );
				
			}
			
			return spl_object_hash( $listener[0] );
			
			
		}
		
		
		
	}
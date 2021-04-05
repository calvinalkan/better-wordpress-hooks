<?php
	
	namespace BetterWpHooks;
	
	/**
	 *
	 * Simple Wrapper Class around the Wordpress Plugin Api
	 * Used to allow swapping during testing.
	 *
	 * Class WordpressApi
	 *
	 * @package BetterWpHooks
	 */
	class WordpressApi {
		
		public function applyFilter( $event, ...$payload ) {
			
			return apply_filters( $event, ...$payload );
			
		}
		
		
		public function hasFilterFor( $event, $callback = NULL ): bool {
			
			// WP returns the hook priority if a hook exists and a cb is passed
			if ( $callback ) {
				
				return is_numeric( has_filter( $event, $callback ) );
				
			}
			
			return has_filter( $event );
			
		}
		
		public function removeFilter( $event, $listener, $priority = 10 ): bool {
			
			return remove_filter( $event, $listener, $priority );
			
		}
		
		public function addFilter( $event, $listener, $priority = 10, $args = 1 ) {
			
			add_filter( $event, $listener, $priority, $args );
			
		}
		
		
	}
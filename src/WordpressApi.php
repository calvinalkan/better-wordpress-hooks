<?php


    declare(strict_types = 1);


    namespace BetterWpHooks;
	

    use BetterWpHooks\Traits\IsAction;

    use function BetterWpHooks\Functions\classExists;


	class WordpressApi {

		public function applyFilter( $event , ...$payload ) {

		    if ( $this->isDispatchingAction($event) ) {

		        do_action($event, ...$payload);

		        // stay close to WP which also return '' for do_action()
		        return '';

            }

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
		
		public function addFilter( $event, $listener, $priority = 10, $args = 99 ) {
			
			add_filter( $event, $listener, $priority, $args );
			
		}

		private function isDispatchingAction( $event ) : bool
        {

            if ( classExists($event) && in_array(IsAction::class, class_uses($event) ) ) {

                return true;

            }

            return false;

        }
		
	}
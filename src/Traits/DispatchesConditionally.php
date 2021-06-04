<?php


    declare(strict_types = 1);


    namespace BetterWpHooks\Traits;
	
	trait DispatchesConditionally {
		
		/**
		 *
		 * Determine at runtime if the event should be
		 * auto-dispatched.
		 *
		 *
		 *
		 * @return bool
		 */
		abstract public function shouldDispatch(): bool;
		
		
		
		
	}
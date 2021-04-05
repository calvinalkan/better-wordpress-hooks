<?php
	
	namespace BetterWpHooks\Traits;
	
	trait ListensConditionally {
		
		
		/**
		 *
		 * Determine at runtime if a listener should handle the
		 * dispatched event.
		 *
		 *
		 * @overwritte
		 *
		 * @return bool
		 */
		abstract public function shouldHandle(): bool;
		
		
	}
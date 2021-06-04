<?php


    declare(strict_types = 1);


    namespace Tests\TestListeners;
	
	use BetterWpHooks\Traits\ListensConditionally;
	
	class ListenerWithReturnConditional {
		
		use ListensConditionally;
		
		public function bar( \Tests\TestEvents\FilterableEvent $foo ) {
			
			$foo->foo .= 'bar';
			
			return $foo;
			
		}
		
		public function shouldHandle(): bool {
			
			return $_SERVER['should_handle'] ?? FALSE;
			
		}
		
	}
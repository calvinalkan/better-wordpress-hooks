<?php


    declare(strict_types = 1);


    namespace Tests\TestListeners;
	
	use Tests\TestEvents\FilterableEvent;
	
	class ListenerWithReturn3 {
		
		public function bar( FilterableEvent $foo ) {
			
			return $foo->foo .= 'bar';
			
		}
		
		
	}
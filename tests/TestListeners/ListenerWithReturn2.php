<?php
	
	namespace Tests\TestListeners;
	
	use Tests\TestDependencies\SimpleMethodDependency;
	use Tests\TestEvents\FilterableEvent;
	
	class ListenerWithReturn2 {
		
		public function biz( FilterableEvent $foo ) {
			
			$foo->foo .= 'biz';
			
			return $foo;
		}
		
		public function foobarbiz( $foo, $bar, $biz, SimpleMethodDependency $dependency ) {
			
			return $foo . $bar . $biz . $dependency->name;
			
		}
		
		
	}
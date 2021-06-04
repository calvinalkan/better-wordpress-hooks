<?php


    declare(strict_types = 1);


    namespace Tests\TestListeners;
	
	use BetterWpHooks\Exceptions\TestException;
	
	class ThrowExceptionListener {
		
		public function handleEvent( $foo ) {
			
			throw new TestException( $foo );
			
		}
		
	}
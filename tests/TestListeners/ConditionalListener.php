<?php


    declare(strict_types = 1);


    namespace Tests\TestListeners;
	
	use BetterWpHooks\Traits\ListensConditionally;
	use Tests\Exceptions\DidAction;
	use Tests\StackInfo;
	
	class ConditionalListener {
		
		use StackInfo;
		use ListensConditionally;
		
		public function foobar( $foo ) {
			
			throw new DidAction( $this->getStackInfo(), $foo . 'bar' );
			
		}
		
		public function shouldHandle(): bool {
			
			return $_SERVER['should_handle'] ?? FALSE;
			
		}
		
	}
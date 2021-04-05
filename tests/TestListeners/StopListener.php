<?php
	
	namespace Tests\TestListeners;
	
	use BetterWpHooks\Traits\StopsPropagation;
	use Tests\Exceptions\DidAction;
	use Tests\StackInfo;
	
	class StopListener {
		
		use StackInfo;
		use StopsPropagation;
		
		public function foobar( $foo ) {
			
			throw new DidAction( $this->getStackInfo(), $foo . 'bar' );
			
		}
		
		
	}
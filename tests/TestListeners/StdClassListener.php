<?php


    declare(strict_types = 1);


    namespace Tests\TestListeners;
	
	use stdClass;
	use Tests\Exceptions\DidAction;
	use Tests\StackInfo;
	
	class StdClassListener {
		
		use StackInfo;
		
		public function handleEvent( stdClass $event ) {
			
			throw new DidAction( $this->getStackInfo(), $event->name .= 'alkan' );
			
		}
		
		public function instance( stdClass $event ) {
			
			throw new DidAction( $this->getStackInfo(), $event->name .= 'alkan' . $this->suffix );
			
		}
		
		
	}
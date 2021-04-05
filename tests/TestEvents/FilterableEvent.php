<?php
	
	namespace Tests\TestEvents;
	
	
	class FilterableEvent {
		
		
		public  $foo;
		
		public function __construct( string $foo ) {
			
			$this->foo = $foo;
		}
		
		public function default(): string {

			return $this->foo;

		}
		
	}
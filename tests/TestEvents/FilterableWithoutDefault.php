<?php
	
	namespace Tests\TestEvents;
	
	class FilterableWithoutDefault {
		
		
		public  $content;
	
		
		public function __construct( string $foo, string $bar ) {
			
			$this->content = $foo. $bar;
			
		}
		
	}
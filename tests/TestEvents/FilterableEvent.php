<?php


    declare(strict_types = 1);


    namespace Tests\TestEvents;
	
	
	use Tests\TestStubs\Plugin1;

    class FilterableEvent extends Plugin1 {
		
		
		public  $foo;
		
		public function __construct( string $foo ) {
			
			$this->foo = $foo;
		}
		
		public function default(): string {

			return $this->foo;

		}
		
	}
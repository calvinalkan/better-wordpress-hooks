<?php


    declare(strict_types = 1);


    namespace Tests\TestDependencies;
	
	use Tests\TestEvents\FilterableEvent;
	
	class ComplexListener {
		
		
		/**
		 * @var ComplexConstructorDependency
		 */
		private  $complex_constructor_dependency;
		
		public function __construct( ComplexConstructorDependency $complex_constructor_dependency ) {
			
			$this->complex_constructor_dependency = $complex_constructor_dependency;
		}
		
		public function handleEvent( $payload, ComplexMethodDependency $method_dependency ): string {
			
			return $this->complex_constructor_dependency->get_simple_dependency()->name .
			       $method_dependency->get_simple_dependency()->name . $payload;
			
		}
		
		public function objectEvent( FilterableEvent $payload, ComplexMethodDependency $method_dependency ): string {
			
			return $this->complex_constructor_dependency->get_simple_dependency()->name .
			       $method_dependency->get_simple_dependency()->name . $payload->suffix;
			
		}
		
		
		public function default() {
			
			return 'default';
			
		}
		
	}
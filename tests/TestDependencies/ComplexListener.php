<?php
	
	namespace Tests\TestDependencies;
	
	use Tests\TestEvents\FilterableEvent;
	
	class ComplexListener {
		
		
		/**
		 * @var \Tests\TestDependencies\ComplexConstructorDependency
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
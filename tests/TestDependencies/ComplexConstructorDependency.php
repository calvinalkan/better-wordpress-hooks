<?php


    declare(strict_types = 1);


    namespace Tests\TestDependencies;
	
	class ComplexConstructorDependency {
		
		
		/**
		 * @var SimpleConstructorDependency
		 */
		private $simple_dependency;
		
		public function __construct( SimpleConstructorDependency $simple_dependency ) {
			
			$this->simple_dependency = $simple_dependency;
		}
		
		public function get_simple_dependency(): SimpleConstructorDependency {
			return $this->simple_dependency;
		}
		
	}
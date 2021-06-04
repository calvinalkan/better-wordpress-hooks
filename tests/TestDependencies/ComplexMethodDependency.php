<?php


    declare(strict_types = 1);


    namespace Tests\TestDependencies;
	
	class ComplexMethodDependency {
		
		
		/**
		 * @var SimpleMethodDependency
		 */
		private  $simple_dependency;
		
		public function __construct( SimpleMethodDependency $simple_dependency ) {
			
			$this->simple_dependency = $simple_dependency;
		}
		
		public function get_simple_dependency(): SimpleMethodDependency {
			return $this->simple_dependency;
		}
		
	}
<?php


    declare(strict_types = 1);


    namespace BetterWpHooks\Exceptions;
	
	use BetterWpHooks\Contracts\ErrorHandler;
	use Illuminate\Contracts\Container\BindingResolutionException;
	
	class ExecutionErrorHandler implements ErrorHandler {
		
		public function handle(\Throwable $e) {
			
			if ( $e instanceof TestException ) {
				
				throw $e;
				
			}
			
			if ( $e instanceof BindingResolutionException ) {
				
				throw new \Exception('It was not possible to resolve your listener using the Illuminate Container' .
				                     PHP_EOL . $e->getMessage()
				);
				
			}
			
			if ( $e instanceof \Error) {
				
				throw new \Exception($e->getMessage());
				
				
			}
			
			throw $e;
			
		}
		
	}
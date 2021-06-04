<?php


    declare(strict_types = 1);


    namespace Tests\Exceptions;
	
	
	use BetterWpHooks\Exceptions\TestException;
	
	
	/**
	 *
	 * This Exception is needed because the is no other way
	 * to test the response by a wordpress action hook
	 * other then to throw an exception
	 *
	 * Class DidAction
	 *
	 * @package Tests\Unit\Exceptions
	 *
	 */
	class DidClosureAction  extends TestException {
		
		public $listener;
		public $response;
		
		public function __construct( \Closure $listener, $response ) {
			
			parent::__construct();
			
			$this->listener = spl_object_hash( $listener );
			$this->response = $response;
			$this->setMessage();
			
		}
		
		private function setMessage() {
			
			$this->message = $this->listener . ' => ' . $this->response;
			
		}
		
		
		
	}
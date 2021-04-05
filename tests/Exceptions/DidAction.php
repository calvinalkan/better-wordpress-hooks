<?php
	
	namespace Tests\Exceptions;
	
	use BetterWpHooks\Exceptions\TestException;
	use Illuminate\Support\Arr;
	
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
	class DidAction extends TestException {
		
		
		public $listener;
		public $response;
		
		public function __construct( string $listener, $response ) {
			
			parent::__construct();
			
			$this->listener = $listener;
			$this->response = is_object( $response ) ? get_class( $response ) : implode( '', Arr::wrap( $response ) );
			$this->setMessage();
			
		}
		
		private function setMessage() {
			
			$this->message = $this->listener . ' => ' . $this->response;
			
		}
		
		
	}
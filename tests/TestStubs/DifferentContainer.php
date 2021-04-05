<?php
	
	namespace Tests\TestStubs;
	
	use Contracts\SniccoContainerAdapter;
	
	class DifferentContainer implements SniccoContainerAdapter {
		
		/**
		 * @param  mixed  $offset
		 *
		 * @return bool
		 */
		public function offsetExists( $offset ) {
			//
		}
		
		/**
		 * @param  mixed  $offset
		 *
		 * @return mixed
		 */
		public function offsetGet( $offset ) {
			//
		}
		
		/**
		 * @param  mixed  $offset
		 * @param  mixed  $value
		 */
		public function offsetSet( $offset, $value ) {
			//
		}
		
		/**
		 * @param  mixed  $offset
		 */
		public function offsetUnset( $offset ) {
		
		}
		
		/**
		 * @param  string  $abstract
		 * @param  array   $parameters
		 *
		 * @return mixed
		 */
		public function make( $abstract, array $parameters = [] ) {
		
		}
		
		/**
		 * @param $abstract
		 * @param $concrete
		 *
		 * @return mixed
		 */
		public function swapInstance( $abstract, $concrete ) {
		
		}
		
		/**
		 * @param  string  $abstract
		 * @param  mixed   $instance
		 *
		 * @return mixed
		 */
		public function instance( $abstract, $instance ) {
		
		}
		
		/**
		 * @param         $callable
		 * @param  array  $parameters
		 *
		 * @return mixed
		 */
		public function call( $callable, array $parameters = [] ) {
		
		}
		
	}
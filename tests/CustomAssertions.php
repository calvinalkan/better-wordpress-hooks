<?php
	
	namespace Tests;
	
	use BetterWpHooks\Exceptions\TestException;
	use Illuminate\Support\Arr;
	use PHPUnit\Framework\Assert;
	use PHPUnit\Framework\AssertionFailedError;
	use Tests\Exceptions\DidAction;
	use Tests\Exceptions\DidClosureAction;
	use \Throwable;
	use function BetterWpHooks\Functions\classNameIfClassExists;
	
	/**
	 * Trait CustomAssertions
	 *
	 * @property \BetterWpHooks\Dispatchers\WordpressDispatcher $dispatcher;
	 *
	 * @package Tests\Unit
	 */
	trait CustomAssertions {
		
		/**
		 * @param  string|null  $message
		 * @param  Throwable    $exception
		 */
		private function checkAssertions( ?string $message, Throwable $exception ) {
			
			
			$actualMessage = $exception->getMessage();
			
			if ( ! $message || $message === $actualMessage ) {
				return;
			}
			
			throw new AssertionFailedError(
				sprintf(
					'%s was expected.' . PHP_EOL . '%s was received.',
					$message,
					$actualMessage
				)
			);
		}
		
		/**
		 * Asserts that callback throws an exception with a message
		 *
		 * @param  string|Throwable  $throws
		 * @param  string|null       $message
		 * @param  callable          $func
		 *
		 * @throws Throwable
		 */
		private function assertDidCallbackWithException( $throws, ?string $message, callable $func ) {
			
			
			try {
				
				call_user_func( $func );
				
			} catch ( AssertionFailedError $exception ) {
				
				
				if ( $throws !== get_class( $exception ) ) {
					
					throw $exception;
				}
				
				$this->checkAssertions( $message, $exception );
				
			} catch ( Throwable $exception ) {
				
				if ( ! $throws ) {
					
					throw $exception;
					
				}
				
				$actualThrows = get_class( $exception );
				
				if ( $throws !== $actualThrows ) {
					
					throw new AssertionFailedError(
						sprintf(
							"Exception '%s' was expected, but '%s' was thrown with message '%s'",
							$throws,
							get_class( $exception ),
							$exception->getMessage()
						)
					);
				}
				
				$this->checkAssertions( $message, $exception );
				
			}
			
			if ( ! $throws ) {
				return;
			}
			
			if ( isset( $exception ) ) {
				
				Assert::assertTrue( TRUE, 'Exception handled' );
				
				return;
			}
			
			throw new AssertionFailedError(
				
				sprintf( "Exception '%s' was not thrown as expected", $throws )
				
			);
		}

		/**
		 * Asserts that callback does not throws an exception with a message
		 *
		 * @param  null|string|Throwable  $throws
		 * @param  string|null            $message
		 * @param  callable               $func
		 */
		private function assertDidNoCallbackWithException( $throws, ?string $message, callable $func ) {
			
			
			try {
				
				call_user_func( $func );
				
			} catch ( Throwable $exception ) {
				
				if ( $exception instanceof TestException ) {
					
					throw new AssertionFailedError( 'An unexpected Listener was executed: ' . PHP_EOL .
					                                'Executed: ' . $exception->getMessage() );
					
				}
				
				$actualThrows = get_class( $exception );
				
				if ( $throws != $actualThrows ) {
					
					Assert::assertNotSame( $throws, $actualThrows );
					
					return;
					
				}
				
				if ( ! $message ) {
					
					throw new AssertionFailedError( 'An unexpected Listener was exectued: ' . PHP_EOL .
					                                'Found: ' . $exception->getMessage() );
				}
				
				$actualMessage = $exception->getMessage();
				
				if ( $message != $actualMessage ) {
					
					Assert::assertNotSame( $message, $actualMessage );
					
					return;
				}
				
				throw new AssertionFailedError(
					
					sprintf( "Exception '%s' with message '%s' was not expected to be thrown", $throws, $message )
					
				);
			}
			
			Assert::assertTrue( TRUE, 'No Listeners executed.' );
			
			
			
		}

		public function assertNoListener( $callable, $event ) {
			
			$message = implode( ', ', Arr::wrap( classNameIfClassExists( $callable ) ) );
			
			Assert::assertFalse( $this->dispatcher->hasListenerFor( $callable, $event ),
				'Unexpected callable: ' . $message . ' for the event: ' . $event . '.' );
			
		}
		
		public function assertHasListener( $callable, $event ) {
			
			$message = implode( ', ', Arr::wrap( classNameIfClassExists( $callable ) ) );
			
			Assert::assertTrue( $this->dispatcher->hasListenerFor( $callable, $event ),
				
				'Excepted callable: ' . $message . ' for the event: ' . $event . 'but it was not found.'
			
			);
			
		}
		
		public function dispatchAndAssertAction( $event, $payload, array $expected ) {
			
			
			$this->assertDidCallbackWithException(
				
				DidAction::class, implode( ' => ', $expected ),
				
				function () use ( $event, $payload ) {
					
					$this->dispatcher->dispatch( $event, $payload );
					
					
				}
			);
			
		}
		
		public function dispatchAndAssertNoActionDone( $event, $payload, array $expected ) {
			
			$this->assertDidNoCallbackWithException(
				
				DidAction::class, implode( ' => ', $expected ),
				
				function () use ( $event, $payload ) {
					
					$this->dispatcher->dispatch( $event, $payload );
					
					
				}
			);
			
		}
		
		public function dispatchAndAssertClosure( $event, $payload, array $expected ) {
			
			$this->assertDidCallbackWithException(
				
				DidClosureAction::class, spl_object_hash( $expected[0] ) . ' => ' . $expected[1],
				
				function () use ( $event, $payload ) {
					
					$this->dispatcher->dispatch( $event, $payload );
					
					
				}
			);
			
		}
		
		public function dispatchObjectAndAssertClosure ( object $event, array $expected) {
			
			$this->assertDidCallbackWithException(
				
				DidClosureAction::class, spl_object_hash( $expected[0] ) . ' => ' . $expected[1],
				
				function () use ( $event ) {
					
					$this->dispatcher->dispatch( $event );
					
					
				}
			);
			
		}
		
		public function dispatchObjectAndAssertAction ( object $event, array $expected) {
			
			$this->assertDidCallbackWithException(
				
				DidAction::class,  $expected[0] . ' => ' . $expected[1],
				
				function () use ( $event ) {
					
					$this->dispatcher->dispatch( $event );
					
					
				}
			);
			
		}
		
		public function dispatchObjectAndAssertNoAction ( object $event, array $expected) {
			
			$this->assertDidNoCallbackWithException(
				
				DidAction::class,  $expected[0] . ' => ' . $expected[1],
				
				function () use ( $event ) {
					
					$this->dispatcher->dispatch( $event );
					
					
				}
			);
			
		}
		
		public function doClosureAction( $result ) {
			
			
			$trace = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, 9 );
			
			$closure_listener = $trace[8]['object'];
			
			$closure_reflection = new \ReflectionClass( $closure_listener );
			
			$closure_property = $closure_reflection->getProperty( 'closure' );
			
			$closure_property->setAccessible( TRUE );
			
			$closure = $closure_property->getValue( $closure_listener );
			
			throw new DidClosureAction( $closure, $result );
			
		}
		
	}
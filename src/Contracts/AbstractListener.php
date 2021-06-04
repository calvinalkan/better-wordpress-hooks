<?php


    declare(strict_types = 1);


    namespace BetterWpHooks\Contracts;
	
	use BetterWpHooks\Traits\ListensConditionally;
	use function BetterWpHooks\Functions\hasTrait;
	
	
	abstract class AbstractListener {
		
		/**
		 * @codeCoverageIgnore
		 *
		 * @return array
		 */
		abstract public function toArray(): array;
		
		
		/**
		 *
		 * @codeCoverageIgnore
		 *
		 * @param $payload
		 *
		 * @return mixed
		 */
		abstract public function execute( $payload );
		
		/**
		 *
		 *@codeCoverageIgnore
		 *
		 * @return array
		 */
		abstract public function aliases(): array;
		
		/**
		 *@codeCoverageIgnore
		 *
		 * @param $payload
		 *
		 * @return bool
		 */
		abstract public function shouldHandle( $payload ): bool;
		
		public function hasConditionalTrait ( $class ): bool {
			
			return hasTrait( ListensConditionally::class, $class );
			
		}
		
		
	}
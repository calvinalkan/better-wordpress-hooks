<?php
	
	namespace BetterWpHooks\Contracts;
	
	/**
	 * Interface ErrorHandler
	 *
	 *
	 * @codeCoverageIgnore
	 *
	 * @package BetterWpHooks\Contracts
	 */
	interface ErrorHandler {

        /**
         *
         * @codeCoverageIgnore
         *
         * @param \Throwable $e
         * @return mixed
         */
		public function handle(\Throwable $e );
		
	}
<?php


    declare(strict_types = 1);


    namespace BetterWpHooks\Contracts;
	
	/**
	 * Interface EventMapper
	 *
	 * @codeCoverageIgnore
	 *
	 * @package BetterWpHooks\Contracts
	 */
	interface EventMapper {
		
		/**
		 *
		 * Bind External Events that we dont control to Custom Events
		 * that trigger their respective custom EventListeners in our Application
		 *
		 *
		 * @param  string  $hook_name
		 * @param  array   $event
		 * @param  int     $priority
		 *
         * @codeCoverageIgnore
         *
		 * @return mixed
		 */
		public function listen( string $hook_name, array $event, int $priority = 10 );
		
		
		
		
	}
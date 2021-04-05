<?php
	
	namespace BetterWpHooks\Mappers;
	
	use BetterWpHooks\Contracts\EventMapper;
	use BetterWpHooks\WordpressApi;
	
	class WordpressEventMapper implements EventMapper {
		
		
		/**
		 * @var \BetterWpHooks\WordpressApi
		 */
		private  $wp_api;
		
		/**
		 * @var array
		 */
		private  $listeners = [];
		
		public function __construct( WordpressApi $wp_api = null ) {
			
			$this->wp_api = $wp_api ?? new WordpressApi();
		}
		
		
		/**
		 *
		 * @param  string  $hook_name
		 * @param  array  $event
		 * @param  int     $priority
		 */
		public function listen( string $hook_name, array $event, int $priority = 10 ) {
			
			$priority = $event[1] ?? $priority;
			
			$hook = $this->makeHook( $hook_name, $event[0] , $priority );
			
			$this->listeners[ $hook_name ] = $hook;
			
			$this->wp_api->addFilter(...$hook);
			
			
		}
		
		
		private function makeHook( $hook_name, $event, $priority ): array {
			
			return [  $hook_name, [ $event, 'mapEvent'], $priority, 99   ];
			
		}
		
		
		
		
		
	}
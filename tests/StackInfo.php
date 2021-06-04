<?php


    declare(strict_types = 1);


    namespace Tests;
	
	trait StackInfo {
		
		public function getStackInfo(): string {
			
			$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 )[1];
			
			return $trace['class'] . '@' . $trace['function'];
			
		}
		
	}
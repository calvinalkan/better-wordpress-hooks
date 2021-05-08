<?php
	
	namespace BetterWpHooks\Contracts;
	
    use BetterWpHooks\Traits\ListensConditionally;
    use BetterWpHooks\Traits\ReflectsCallable;
    use Illuminate\Support\Str;


    use function BetterWpHooks\Functions\hasTrait;

    use function BetterWpHooks\Functions\normalizeClassMethod;
	
	abstract class AbstractListener {

        use ReflectsCallable;


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

        protected function toArrayCallable(array $listener) : array
        {

            $listener = array_values($listener);

            if (Str::contains($listener[0],'@')) {

                $listener = Str::parseCallback($listener[0]);

            }

            return $listener;

        }

        protected function callClassMethod(array $class_callable, $payload)
        {

            $parameters = $this->buildParameterNames($class_callable, $payload);

            return $this->container->call(normalizeClassMethod($class_callable, 'handleEvent'), $parameters);


        }


    }
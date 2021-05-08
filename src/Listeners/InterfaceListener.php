<?php


    namespace BetterWpHooks\Listeners;

    use BetterWpHooks\Contracts\AbstractListener;
    use BetterWpHooks\Traits\ReflectsCallable;
    use Contracts\ContainerAdapter;

    use function BetterWpHooks\Functions\normalizeClassMethod;

    class InterfaceListener extends AbstractListener
    {

        use ReflectsCallable;


        /** @var InstanceListener */
        private $implementation;

        /**
         * @var array
         */
        private $interface_callable;

        /**
         * @var ContainerAdapter
         */
        protected $container;

        public function __construct(array $listener, ContainerAdapter $container)
        {

            $this->interface_callable = $this->toArrayCallable($listener);
            $this->container = $container;

        }

        public function toArray() : array
        {

            return $this->interface_callable;
        }

        public function execute($payload)
        {

            return $this->callClassMethod( [$this->implementation, $this->interface_callable[1]], $payload );
        }

        public function aliases() : array
        {

            return [

                normalizeClassMethod( $this->interface_callable, 'handleEvent' ),
                $this->interface_callable[0],

            ];
        }

        public function shouldHandle($payload) : bool
        {

            $this->implementation = $this->container->make($this->interface_callable[0]);

            if ( ! $hasTrait = $this->hasConditionalTrait( $this->implementation ) ) {
                return TRUE;
            }

            return $hasTrait && $this->callClassMethod( [ $this->implementation, 'shouldHandle' ], $payload );


        }


    }
<?php


    declare(strict_types = 1);


    namespace Tests\TestEvents;

    use BetterWpHooks\Traits\IsAction;
    use Tests\TestDependencies\Dependency;
    use Tests\TestStubs\Plugin1;

    class ActionEvent2 extends Plugin1
    {

        use IsAction;

        /**
         * @var Dependency
         */
        private $dependency;

        public function __construct(Dependency $dependency)
        {

            $this->dependency = $dependency;
        }

    }
<?php


    declare(strict_types = 1);


    namespace Tests\TestEvents;

    use BetterWpHooks\Traits\IsAction;
    use Tests\TestStubs\Plugin1;

    class ActionEvent extends Plugin1
    {
        use IsAction;

    }
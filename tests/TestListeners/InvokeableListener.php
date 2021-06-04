<?php


    declare(strict_types = 1);


    namespace Tests\TestListeners;

    use Tests\Exceptions\DidAction;
    use Tests\StackInfo;
    use Tests\TestEvents\EventFakeStub;

    class InvokeableListener
    {

        use StackInfo;

        public function __invoke($foo)
        {

            $response = $foo instanceOf EventFakeStub
                ? $foo->creator . '_handled'
                : $foo . '_handled';

            throw new DidAction( $this->getStackInfo(), $response );

        }

    }
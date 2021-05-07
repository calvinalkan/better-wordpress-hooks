<?php


    namespace Tests\TestEvents;

    use http\Exception;

    class EventWithDefaultLogic
    {

        public function default ( $payload , $filtered ) :string {

            if ( $filtered === 1 ) {

                return 'toString:' . $filtered;

            }

            throw new \Exception('Return value is not valid for '. __CLASS__);


        }

    }
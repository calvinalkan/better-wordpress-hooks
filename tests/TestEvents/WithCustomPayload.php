<?php


    declare(strict_types = 1);


    namespace Tests\TestEvents;

    class WithCustomPayload
    {

        private $payload = 'PAYLOAD';

        public function payload () {

            return $this->payload;

        }

    }
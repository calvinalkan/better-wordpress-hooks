<?php


    declare(strict_types = 1);


    namespace Tests\Unit;

   use BetterWpHooks\Dispatchers\WordpressDispatcher;
   use BetterWpHooks\ListenerFactory;
   use BetterWpHooks\Testing\BetterWpHooksTestCase;
   use BetterWpHooks\WordpressApi;
   use SniccoAdapter\BaseContainerAdapter;

   class ReceivedPayloadTest extends BetterWpHooksTestCase
   {

       /**
        * @var WordpressApi
        */
       private $wp;

       /**
        * @var WordpressDispatcher
        */
       private $dispatcher;

       protected function setUp() : void
       {

           parent::setUp();

           $this->setUpWp(VENDOR_DIR);

           $this->wp         = new WordpressApi();
           $this->dispatcher = new WordpressDispatcher(

               new ListenerFactory( new BaseContainerAdapter() ),
               $this->wp

           );

       }

       protected function tearDown() : void
       {

           parent::tearDown();

           $this->tearDownWp();

       }



   }
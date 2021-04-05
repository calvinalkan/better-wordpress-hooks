# BetterWpHooks - A modern, OOP Wrapper around the Wordpress Plugin Api.

![CircleCI](https://img.shields.io/circleci/build/github/calvinalkan/better-wordpress-hooks/master?token=888f31e8ca77ad9621a420ab09ce799f3382d52e)
![code coverage](https://img.shields.io/badge/coverage-99%25-brightgreen)
![last commit](https://img.shields.io/github/last-commit/calvinalkan/better-wordpress-hooks)
![open issues](https://img.shields.io/github/issues-raw/calvinalkan/better-wordpress-hooks)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
![php version](https://img.shields.io/packagist/php-v/calvinalkan/better-wordpress-hooks)
![lines of code](https://img.shields.io/badge/total%20lines-2268-blue)

BetterWpHooks is a small library that wraps the Wordpress Plugin/Hook API allowing for modern, OOP PHP with features like:

🚀 Lazy instantiation of classes registered in actions and filters

🔥 Zero configuration dependency-resolution of class and method dependencies using an IoC-Container

❤ Conditional dispatching and handling of hooks based on parameters only available at runtime.

📦 Inbuilt testing module, so you don't have to boostrap the wordpress core in your unit tests.

⭐ 100 % compatibility with the Wordpress Core and the way users interact with custom hooks and filters.

## Table of Contents

* [Why bother?](#why-bother--long-)
* [Requirements](#requirements)
* [Installation](#installation)
* [Key Concepts](#key-concepts)
    * [Terminology](#terminology)
    * [Entry point](#entry-point)
    * [IoC-Container](#1-ioc-container)
    * [Events](#2-events)
    * [Event Dispatcher](#3-event-dispatcher)
    * [Event-Mapper](#4-event-mapper)
* [Bootstrapping](#bootstrapping)
* [Using BetterWpHooks](#using-betterwp-hooks)
    * [Complete Example with 3rd-party Hooks](#complete-example-using-third-party-hooks)
    * [Dispatching your own events](#dispatching-your-own-events)
    * [Wordpress Filters](#dispatching-your-own-events)
    * [Valid Listeners](#valid-listeners)
    * [Dependency Resolution](#dependency-resolution)
    * [Conditional Event Dispatching](#conditional-event-dispatching)
    * [Conditional Listeners](#conditional-event-listening)
    * [Stopping a Listener Chain ](#stopping-a-listener-chain)
* [API](#api)
* [Inbuilt Testing Module](#inbuilt-testing-module)
* [How it works](#how-it-works)  
    * [How Events are dispatcher](#how-events-are-dispatched)
    * [How Listeners are called](#how-listeners-are-called)
* [TO-DO](#to-do)
* [Credits](#credits)

***

## Why bother?

Being released in Wordpress 2.1, in a time when OOP wasn't a thing yet in
PHP, [The Wordpress Plugin-API](https://codex.wordpress.org/Plugin_API/Hooks) has several shortcomings.

**The main issues of the Wordpress Plugin/Hook API are:**

- No usage
  of [Dependency Injection or an IoC-Container](https://www.martinfowler.com/articles/injection.html#ServiceLocatorVsDependencyInjection)
  . If you care about code quality and maintainability of your code you use an IoC-Container.
- There is **no proper place to define actions and filters**. Many Wordpress devs default to using a class constructor
  which is not a great option for several reasons. Another approach I see a lot of the times is a custom factory which
  often leads to your IDE not being able to detect where you added hooks.
- When using class-based callbacks, the only option besides using static methods ( *don't do that ) is to **instantiate
  the class on every request** before creating the Wordpress Hook. I can't think of any modern PHP-framework that forces
  you to instantiate classes to **MAYBE** be used later as an event observer. Let's review an example that can be found
  in 95% of Wordpress plugin code. Classes that do stuff and also control when they do it. ( Violation of
  the [SRP](https://blog.cleancoder.com/uncle-bob/2014/05/08/SingleReponsibilityPrinciple.html)  . )

```php
// Approach #1, creating the class and passing the object to the hook.
class MyClass {

    public function __construct() {
        add_action('init', [ $this, 'doStuff']);
    }
    public function doStuff () {
        //
    }
    
    public static function doStuffStatic () {
    //
    }
}

// Approach #2, even worse. Using static functions.
add_action('init', [ MyClass::class, 'doStuffStatic' ]);
```

For a simple class this might work just fine, but now imagine that `MyClass`
has nested dependencies and handles several Wordpress hooks. Constructing this object on every request just doesn't feel
right and changing the constructor arguments at some point in the future might be very painful.

- Wordpress is event-driven. Hooks have to be added on every request. Yet there is **no clearly defined way to
  conditionally fire hooks** based on variables you only have available at runtime. Some code may only ever be required
  under certain circumstances, yet the classes get created on every request. An Example might be a class that handles
  sending a gift card if a customer did a purchase with an order value greater than 500$.

```php
// Lets assume we want to send an a email and log to an external service
// ( maybe a google sheet ) every time an order takes place with a total > 500. 
class GiftCardHandler {

    private $mailer; 
    private $logger;

    // We need a mailer and a logger service
    public function __construct( Mailer $mailer, Logger $logger) {
    
        $this->mailer = $mailer; 
        $this->logger = $logger; 
        add_action('checkout_completed', [ $this, 'handle'], 10, 1);
        
    }
    
    // Lets assume we get an order object from the hook.
    public function handle (Order $order) {
    
        if ( $order->total() >= 500 ) {
        
            $mailer->sendGiftcard($order->user());
            $logger->logBigBurchase($order);
        
        }
    }
}
```

We only ever use this class under very special circumstances, yet we have to create it on every request to pass it to
the Wordpress Hook.

- Lastly, unit testing ( I hope you unit test your plugin code ) this code is complicated since it's tightly coupled to
  Wordpress Core functions which means you either have to bootstrap the entire Wordpress installation during your test
  set up or use a Wordpress mocking framework like [Brain Monkey](https://github.com/Brain-WP/BrainMonkey)
  or [WP_Mock](https://github.com/10up/wp_mock). I have used them both, they are great. But it should not be necessary
  to go through such hoops to test a basic event pattern.

#### BetterWpHooks solves all of these problems and provides many more convenience features for a better Wordpress developer experience.

***

## Requirements

- BetterWpHooks is a composer package, not a plugin. To be able to use this package you need to
  have [composer set up](https://getcomposer.org/doc/00-intro.md) in your plugin's root directory.
- PHP Version >= 7.3

In theory, you should be able to use this package with some minor modifications with every PHP Version >= 7.0 but I did
not activly test neither do I recommend
using [PHP Versions that are not actively supported anymore](https://www.php.net/supported-versions.php) by the PHP
maintainers.

***

## Installation

From the root directory of your plugin or theme, execute the following command from the terminal.

``` composer require calvinalkan/better-wordpress-hooks ```

***

## Key Concepts

### Terminology

In the following Wordpress actions and filters will be referred to as **events**. Hook callbacks, be it a class or an
anonymous closure are referred to as **event listeners**.

### Entry Point

BetterWpHooks was built in mind with how the Wordpress plugin ecosystem works. Unlike many other packages that try to
modernize Wordpress BetterWpHooks **can be used by an unlimited amount of plugins at the same time without conflicts**.

The main entry point to the library is the
trait `BetterWpHooksFacade`([src](https://github.com/calvinalkan/better-wordpress-hooks/blob/master/src/Traits/BetterWpHooksFacade.php))
.

By creating a custom class which uses this Trait you gain access to your own instance of the core of the library.

In the following we are going to assume that we are using this library inside a plugin called **AcmePlugin**.

```php
use BetterWpHooks\Traits\BetterWpHooksFacade;

class AcmeEvents {

use BetterWpHooksFacade;

}
```

The class `AcmeEvents`will be your entry point to an instance of the main class of the
library`BetterWpHooks`([src](https://github.com/calvinalkan/better-wordpress-hooks/blob/master/src/BetterWpHooks.php))
which provides you access to the 3 main collaborators of the library:

### 1. IoC-Container

To auto-resolve dependencies of event listeners and automaticcly create objects, BetterWpHooks makes use of a Inversion
of Control (IoC) Container. Since many Wordpress plugins already use a IoC-Container it would make no sense to force the
usage of any specific container implementation.

The entire library is dependent on an ``ContainerAdapterInterface`` . By default, an adapter for
the `illuminate/container` is used. Every feature of the [Illumiante/Container](https://laravel.com/docs/8.x/container)
is fully supported.

The acutal container implementation can be swapped out by confirming to a simple interface, so you can use any other
container like:

- [Symfony Container](https://symfony.com/doc/current/service_container.html)
- [Aura](http://auraphp.com/packages/2.x/Di.html)
- [Dice](https://r.je/dice)

The only constraint is that **auto-resolving ( auto-wiring ) neeeds to be supported by your container!**

### 2. Events

Events represent Wordpress Actions and Filters. There is however no need to distinguish between the two. That is taken
care of under the hood.

There are two ways to use Events with BetterWpHooks:

1. Events as event objects (**recommended**, as it provides way more features)
2. Similar to Wordpress Actions and Filters but still using the IoC-Container to resolve classes (not-recommended)

### 3. Event Dispatcher

This is the main class that servers as a layer between your plugin code and the Wordpress Plugin API. Instead of
directly creating hooks via `add_action` and `add_filter` your create them using the Event Dispatcher.

### 4. Event Mapper

The Event Mapper is a small class that can be used to automatically transform core ( or 3rd-party ) actions and filters
into custom event objects, thus allowing you to use all the provided features even with events ( hooks ) you have no
control over.

***

## Bootstrapping

In order to use BetterWpHooks you need to bootstrap it in 3 simple steps. This should be done in your main plugin file
or any other file that gets executed **before** Wordpress fires its first Hook. You should
also [require the composer autoloader](https://getcomposer.org/doc/01-basic-usage.md#autoloading) in your main plugin
file.

**1. Create your entry-point class**

```php
use BetterWpHooks\Traits\BetterWpHooksFacade;

class AcmeEvents {

use BetterWpHooksFacade;

}
```

**2. Create an instance of the the ``BetterWpHooks`` class that will be mapped to ``AcmeEvents``:**

```php
AcmeEvents::make();

// Alternatively if you want to use a custom container:
$custom_container_adapter = new SymfonyContainerAdapter();
AcmeEvents::make($custom_container_adapter);
```

**3. Map core and 3rd party hooks to custom event objects ( optional but recommended )**.

All that this does is dispatching your custom event object when the WP Hooks is fired. Will see why we do this in a
minute.

```php
$mapped = [

'init' => [

    RegisterJobListingPostType::class,
    
    // more events
], 

'save_post_job_listing' => [

    JobListingCreated::class
    
]

];

AcmeEvents::mapEvents($mapped);
```

**4. Create Event Listeners that should handle your custom events and boot the class.**

```php
$listeners = [

RegisterJobListingPostType::class => [

    PostTypeRegistry::class,
    
    // More Event Listeners if needed
], 

JobListingCreated::class => [

   NotifyAdmin::class,
   SendConfirmationEmail::class, 
   TagCreatorInMailchimp::class
   
    // More Event Listeners if needed
],

// Custom Events that you fire in your code 
JobMatchFound::class => [

    NotifyListingOwner::class,
    NotifyApplicatant::class,

]

];

AcmeEvents::listeners($listeners);
AcmeEvents::boot();
```

**The entire process can also be created as a fluent api**.

Ideally you would create to plain php files that just return an array of your mapped events and events listeners. This
way you have all your events nicely in one file instead of being sepereated over your entire codebase.

**Complete example:**

```php
require __DIR__ . '/vendor/autoload.php';

$mapped = require_once __DIR__ . '/mapped-events';
$listeners = require_once __DIR__ . '/event-listeners';

AcmeEvents::make()->mapEvents($mapped)->listeners($listeners)->boot();
```

***

## Using BetterWpHooks

Alright, **why would I do all this?** Time for some examples:

Lets assume we are developing a Woocommerce Extension that gives users the possibility to extend their woocommerce store
with a lot of marketing related features.

One of them being the gift card functionality described in the intro.

### Complete Example using third-party hooks.

We create an event that represents the
action ```woocommerce_checkout_order_processed``` ([src](https://github.com/dipolukarov/wordpress/blob/master/wp-content/plugins/woocommerce/classes/class-wc-checkout.php#L673))
. This Event needs to extend ``AcmeEvents``.

This action hook provides the order_id of the created order and the form submission data. We will create a working
example of this functionality in incremental steps improving it slowly.

1. Create an event object. Event objects are plain PHP object that do not store any logic.

```php
class HighValueOrderCreated extends AcmeEvents {

    public $order;
    public $submission_data;
    
   public function __construct( int $order_id, array $submission_data ) {
   
            $this->order = wc_get_order($order_id);
            $this->submission_data = $submission_data;
   }

}
```

2. Create a listener that handles the logic when an order with a total >= 500$ is created.

```php
class ProcessGitCards {

    private $mailer;
    private $logger;
    
   public function __construct( $mailer, $logger ) {
   
            $this->mailer = $mailer;
            $this->logger = $logger;
   }
   
   public function handleEvent ( HighValueOrderCreated $event) {
   
        $order = $event->order; 
        
        // Example properties. 
        $this->mailer->sendGitfgard( $order->user_id, $order->items );
        $this->logger->logGiftcardRecepient( $order->user_id, $order->items, $order->purchased_at );
        
   }

}
```

3. Wire the Event and Listener. ( The wiring should be done in a seperate, plain php file )

```php
require __DIR__ . '/vendor/autoload.php';

$mapped = [

    'woocommerce_checkout_order_processed' => [
    
        HighValueOrderCreated::class 
        // Map more if needed.
]];

$listeners = [
    HighValueOrderCreated::class => [
    
        ProcessGitCards::class . '@handleEvent'
        // More Listeners if needed
] ];

AcmeEvents::make()->mapEvents($mapped)->listeners($listeners)->boot();
```

**So what happens now when woocommerce creates an order ?**

1. Wordpress will fire the ``woocommerce_checkout_order_processed`` action.
2. Since under the hood a custom event was mapped to this action Wordpress will now call a closure that will create a
   new instance of the ```HighValueOrderCreated``` Event using the **passed arguments from the filter** to construct the
   event object.
3. The called closure will first create the instance and then dispatch an event ```HighOrderValueCreated::class``` which
   passes the created object as an argument to any registered Listener.
4. Since we registered a listener for the ``HighOrderValueCreated`` our the ``handleEvent``method on
   the ``ProcessGiftcards``class is now called. ( See [How it works](#) for a detailed explanation ).
5. The constructor dependencies ``$mailer, $logger`` are **automatically** injected into the class.
6. If the ``handleEvent()`` would have any method dependencies besides the event object, those **method dependencies
   would have also been injected automatically.**

### Improving

***

#### Conditional Dispatching

You might have noticed that we have not handled the logic regarding the conditional execution based on the order value
yet.

1. We apply a ```DispatchesConditionally``` Trait to our ```HighValueOrderCreated``` class. We also give users the
   choice to define a custom amount for when a giftcard should be sent.

```php
class HighValueOrderCreated extends AcmeEvents {

    use \BetterWpHooks\Traits\DispatchesConditionally;

    public $order;
    public $submission_data;
    
    public function __construct( int $order_id, array $submission_data ) {
   
            $this->order = wc_get_order($order_id);
            $this->submission_data = $submission_data;
   }

    public function shouldDispatch() : bool{
 
    return $this->order->total >= (int) get_option('acme_gift_card_amount');
 
}}
```

Before the Dispatcher dispatches an event that uses this trait the shouldDispatch Method is called and evaluated. If it
returns false
**the event will not be fired by Wordpress at all**. It will never hit the Plugin/Hook Api, and **no instance**
of ``ProcessGiftCards, Mailer and Logger`` **will be ever be created**.

#### Interfaces instead of concrete implementations.

We now want to give users the option to use a different mailer. We support Sendgrid, AWS and MailGun. How do we do this?
In order to not break
the [Dependency Inversion Principle](https://stackify.com/dependency-inversion-principle/#:~:text=Martin's%20definition%20of%20the%20Dependency,should%20not%20depend%20on%20details.)
we now use a ``MailerInterface``

Assuming to you are using the default Container Adapter you can now do the following:

```php

use Illuminate\Container\Container;
use SniccoAdapter\BaseContainerAdapter;
use MailerInterface;

$container = new Container();

$container->when(ProcessGiftCards::class)
           ->needs(MailerInterface::class)
           ->give(function () {
           
              $mailer = get_option('acme_mailer');
              
              if($mailer === 'sendgrid') {
                
                return new SendGridMailer();
              
              }  
              
              if($mailer === 'mailgun') {
                
                return new MailGunMailer();
              
              }  
                
              return new AwsMailer();
              
          });

AcmeEvents::make(new BaseContainerAdapter($container)); // -> 
```

The ```ProcessGiftCards``` class will now automatically receive the correct mailer implementation based on which option
the site admin currently has selected.

The configuration of the container should be handled by a separate class. For all options and documentation check out
the`Illuminate/Container` docs over at [laravel.com](https://laravel.com/docs/8.x/container).

Again: **if the minimum threshold for the total order value is not met, none of this will ever be executed. Everything
is lazy-loaded at runtime.**

I hope both agree that this implementation is cleaner and most importantly, a lot more extendable and maintainable than
anything we can currently implement in with the Wordpress Plugin/Hook Api.

### Dispatching your own Events:

There are two ways you can dispatch events (hooks) in your own code with BetterWpHooks.

1. **Dispatching events as objects.**

***

Instead of doing

```php
// Code that processes an apointment booking. 

do_action('booking_created', $book_id, $booking_data );
```

You can do the following:

```php
// Code that processes an appointment booking. 

BookingCreated::dispatch( [$book_id, $booking_data] );
```

This will first create a new instance of the ```BookingCreated```event passing the arguments into the constructor and
then run the event through the ``Dispatcher``instance provided by your custom class ``AcmeEvents`` ( Remember all events
would extend the ``AcmeEvents`` class ).

```php
class AcmeEvents {

use BetterWpHooksFacade;

}
```

When creating object events all arguments that should be passed to the constructor **have to be wrapped in an array**.

This will not work:

```php 
BookingCreated::dispatch( $book_id, $booking_data );
```

Only the ``$book_id`` would be passed to the constructor.

***

2. **Dispatching events via the** ```AcmeEvents``` **class**

This approach can be uses if you don't want to create a dedicated event object for an event.

```php
// Code that processes an appointment booking. 

AcmeEvents::dispatch( 'booking_created', $book_id, $booking_data );
```

This is similar to the way ``do_action()`` works but you still get acces to most features of BetterWpHooks like
auto-resolution of dependencies and the testing-module. However you wont be able to use conditional dispatching of
events quite the same as you would when using approach #1.

In addition your listeners would need to accept the same number of parameters that you passed when dispatching the
event. With approach #1 your listeners **always receive one argument only**, the event object instance.

Using the Facade class you have to options to define arguments which both have the same outcome.

```php
// These are identical.
AcmeEvents::dispatch( 'booking_created', [ $book_id, $booking_data ] );
AcmeEvents::dispatch( 'booking_created',  $book_id, $booking_data  );
```

However only **the first passed paramenter must be the identifier of the event you want to dispatch.**
***

#### Dispatching Helpers.

Sometimes you might only ever want to dispatch one of your own events under specific conditions.

```php
// If we have a big-group appointment we want to send an
// email to the responsible staff to notify them in advance

// This can be replaced
if ( $appointment->participantCount() >= 5 ) {

    do_action('acme_big_group_booking_created', $appointment);

}

// With 
BigGroupBookingCreated::dispatchIf( $appointment->participantCount() >= 5, [$appointment]);
```

There is also the opposite available:

```php
BookingCreated::dispatchUnless( $appointment->participantCount() >= 5, [$appointment]);
```

***

### Wordpress Filters

Using the default Plugin/Hook API you need to distinguish between unsing ``add_action`` and ```add_filter``` .
BetterWpHooks takes care of this under the hood. There is no different syntax for defining actions and filters. Lets
review a simple example where we might want to allow other developers to modify an appointment data created by our
fictive appointment plugin.

````php
// Code to create an appointment object

$appointment = AppointmentCreated::dispatch([ $appointment_object ]);

// Save appointment to the Database.
````

A third-party developer ( or maybe a paid extension of your plugin ) might now filter the appointment object just as he
normally would. Example:

````php
// When dispatching object events the hook id is always the full classname. 

add_filter('ApointmentCreated::class', function( AppointmentCreated $event) {
    
    $appointment = $event->appointment;
    
    if ( $some_conndition === TRUE ) {
    
     // increase cost.
     $appointment->cost = 50;
    
    }
    
   return $appointment;

} );
````

Alternativly you could dispatch the filter like this:

````php
$appointment = AcmeEvents::dispatch( 'acme_appointment_created', $appointment_object );
````

***

#### Default return values.

BetterWpHooks recognized that you are trying to dispatch a filter and will automatically handle the case where no
listeners might be created to pick up on the event. In that case no object will ever be built, in fact not event the
Plugin API will be called under the hood.

Default return values are evaluated in the following order:

1. If you are dispatching an event object you can define a ````default()```` method on the event object class. This
   method will be called if it exists and the returned value will be passed as a default value.

2. If there is no ````default()```` method on the event class but you are dispatching an event object, the object itself
   will be returned. For the example above the instance of ``AppointmentCreated`` would be returned.

3. If #1 and #2 are not possible the first parameter passed into the ````dispatch()```` method will be returned.

***

### Valid Listeners

A listener, just like with the default Wordpress Plugin/Hook API can be either an anonymous closure a class callable.
Any of the following options are valid for creating a listener with the Dispatcher. By default, if no method is
specified BetterWpHooks will try to call the ````handleEvent()```` method on your listener.

````php

$listeners = [

    Event1::class => [
    
    // All of these class callables will work.
    Listener::class, 
    Listener::class . '@foo',
    [ Listner::class ],
    [ Listener::class, 'handleEvent' ],
    [ Listener::class, 'handleEvent' ],
    [ Listener::class, 'foobar' ],
    [ Listener::class . '@foobarbiz' ],
    [ 'custom_identifier' => Listener::class . '@foo' ],
    [ 'custom_identifier' => Listener::class],
    [ 'custom_identifier' => [ Listener::class, 'foobar' ] ],
 
    // Closures 
    function (Event1 $event ) {
    
        // Do Stuff
        
    },
    
    'custom_closure_key' => function (Event1 $event ) {
    
        // Do Stuff
        
    }
    
]

];
````

See the section on [custom identifier](#custom-identifiers) for its use cases.


***

### Dependency Resolution

Let's look at a complex example of how dependency resolution works with BetterWpHooks. This is assuming that you are
using the inbuilt ``Illuminate/Container Adapater``. With other containers there might be slight differences with how
you need to define method and constructor dependencies.

The ```BookingEventsListener``` shall handle various events regarding Bookings. ( creation, deletion, rescheduling etc.)

When a Booking is canceled we want to notify the hotel owner and the guest and also make an API-call to booking.com to
update our availability. However we only need the ``BookingcomClient`` in one method of this EventListener lets not make
it a constructor dependency.

Our class will look like this:

````php

class BookingEventsListener {

    private $complex_mailer; 
    
    public function __construct( ComplexMailerDependency $mailer ) {

           $this->complex_mailer = $mailer;
    }

   
    public function bookingCanceled (BookingCanceled $event, BookingcomClient $bookingcom_client ) {
    
        $owner = $event->booking->owner();
        $guest = $event->booking->guest();
       
        $this->complex_mailer->confirmCancelation([ $owner, $guest ]);
        
        $bookingcom_client->updateAvailablity($event->booking_id);
        
    
    }

}

class ComplexMailerDependency {
    
    private $simple_dependency; 
    
    public function __construct( SimpleConstructorDependency $simple_dependency) {
        
       $this->simple_dependency = $simple_dependency;
       
    }

}
````

When the ````BookingCanceled```` event is dispatched the following things happen:

1. The ```SimpleConstructorDependency``` class is instantiated.
2. Using the ```SimpleConstructorDependency``` the ```ComplexMailerDependency``` is instantiated.
3. The ```BookingEventsListener``` class is instantiated using the new ```ComplexMailerDependency```.
4. A new ```BookingcomClient``` is instantiated.
5. The ```bookingCanceled()```method is called passing in the dispatched event object and the ```BookingcomClient```.

**Method arguments that are created from the dispatched event should always be declared first in the method signature
before any other dependencies**.

***

### Conditional Event Dispatching

This is meant to be used for actions and filters whose triggering are not under your control. (ie. core or third-party
plugin hooks).

For events that you control it's easier to just use the ````dispatchIf```` and ````dispatchUnless```` helpers. However,
if the logic for when your event should be dispatched is complex, it might be preferable to put this logic inside the
event object.

After mapping a third-party hook to a custom event, you should use the ````DispatchesConditionally```` Trait on your
Event class.

````php
use BetterWpHooks\Traits\DispatchesConditionally;

class HighOrderValueCreated {

    use DispatchesConditionally;
 
    private $order; 
    
    public function __construct(Order $order ) {
    
        $this->order = $order;
        
    }
 
    public function shouldDispatch() : bool{
        
        return $this->order->total >= 500;
            
    }}
````

The return value of the ````shouldDispatch```` method is evaluated every time **before** the anything is executed.
If ```FALSE``` is returned, the Wordpress Plugin/API will never be hit, neither will any class get instantiated.

### Conditional Event Listening

In some cases it might be useful to determine at runtime if a listener should handle an Event.

Let's consider the following fictive Use case:

**Every time an appointment is booked you want to:**

1. Notify the responsible staff member via Slack.
2. Send a confirmation email to the customer
3. Add the customer to an external SaaS like Mailchimp.
4. **If** the customer booked an appointment with a total value greater than $300 you also want to notify the business
   owner via SMS, so he can personally attend the client.

So how could be do this?

Our code dispatches an event every time an appointment is created:

````php
// Process appointment
AppointmentCreated::dispatch([$order]);
````

It's clear that conditionally dispatching the event is not an option here, because we want Listeners 1-3 to always
handle the event.

All listeners are correctly registered for the ``AppointmentCreated`` event.

````php
$listeners = [

    AppointmentCreated::class => [
    
        NotifyStaffViaSlack::class, 
        SendConfirmationEmail::class,
        AddToMailchimp::class, 
        NotifyBusinessOwner::class
    ]

];
````

This is where we can use **conditional event listeners**

We define the ```NotifyBusinessOwner``` listener like this with the trait ``ListensConditionally``

When a listener has this trait, **before** calling the defined method ( here its ``handleEvent()`` ) the return value of ``shouldHandle``is evaluated.
If false is returned the method will not be executed and the ```$event``` object will get passed unchanged to the next listeners ( if there are any ).

````php
class NotifyBusinessOwner {

    use \BetterWpHooks\Traits\ListensConditionally;

    private $sms_client;
    private $config;
 
    public function __construct( SmsClient $sms_client, Config $config ) {
    
    $this->sms_client = $sms_client;
    $this->config = $config;
    
    }
    
    public function handleEvent ( AppointmentCreated $event ) {
    
        $business_owner_phone_number = $this->config->get('primary_phone_numbe');
     
        $this->sms_client->notify($business_owner_phone_number, $event->appointment);
        
    }
    
    // Abstract method defined in the trait
    public function shouldHandle( AppointmentCreated $event) : bool{
    
        return $event->appointment->totalValue() >= 300;
    
    } 
}
````

### Stopping a listener chain

If for some reason you want to stop every following listener from executing after a specific listener you can 
have the listener use the ```StopsPropagation``` Trait. 

**Caveats:**
This will remove every listener registered by **your instance of `BetterWpHooks`** for the current request.
The order in which listeners were registered does not matter. The first listener that is registered with the ``StopsPropagation`` Trait will be called, while every other listener will be removed for the current request.

````php
$listeners = [

    Event1::class => [
    
        Listener1::class, 
        Listener2::class,
        Listener3::class, 
        Listener4::class
    ]

];
````
If ``Listener4`` were to use the ```StopsPropagation``` Trait, every other Listener would be removed for ``Event1`` during the current request.

## API

In theory, you should not have the need to use the underlying service classes provided to you by your ``AcmeEvents``
class outside the [bootstrapping](#bootstrapping) process.

If that need arises, BetterWpHooks makes it easy to do so.

Your class ``AcmeEvents`` ( see [Entry-Point](#entry-point) ) serves as Facade to the underlying services, pretty
similar to how [Laravel Facades](https://laravel.com/docs/8.x/facades) work.

Every static method call is not actually static but resolves to the underlying ```BetterWpHooks``` instance of your
class using PHP's
```_callStatic()``` magic-method.

There is a dedicated
class ````Mixin```` ( [src](https://github.com/calvinalkan/better-wordpress-hooks/blob/master/src/Mixin.php)) that
provides IDE-autocompletion and also serves as documentation of the methods that are available.

#### Container

`````php
// getting the underlying container instance.
$container = AcmeEvents::container();
`````

#### Event Dispatcher

`````php
// getting the underlying dispatcher instance.
$dispatcher = AcmeEvents::dispatcher();
`````

You can directly call methods on the dispatcher using your  ``AcmeEvents`` class. Method calls to dispatcher methods
take precedence over calls to the main ````BetterWpHooks```` class.

`````php
// These two are equivalent

#1
$dispatcher = AcmeEvents::dispatcher();
$dispatcher->hasListeners('event_id_to_look_for');

#2
AcmeEvents::hasListener('event_id_to_look_for');
`````

#### Directly creating a listener outside the bootstrapping process.

`````php
AcmeEvents::listen(Event1::class, Listener::class . '@foobar');
`````

#### Marking a listener as unremovable

Sometimes third-party developers tinker too much with a plugin's codebase and remove complete callbacks using the core
function ````remove_filter()````, which is fine in most cases. However, if the implications of removing a given filter
might not be completely obvious and will most likely cause your plugin to be broken you can mark a listener as
unremovable using the ``unremovable()`` method instead of ```listen()```.

The listener will now be unremovable through your ``AcmeEvents`` class and the only other possiblity would be to guess
the exact  ``spl_object_hash()`` since that
is [how Wordpress creates hook-ids](https://github.com/WordPress/WordPress/blob/b70c00d6acd441af54342f147ab3db1b840632e5/wp-includes/plugin.php#L916)
.

`````php
AcmeEvents::unremovable(Event1::class, Listener::class . '@foobar');
`````

#### Checking existence of listeners

`````php
AcmeEvents::hasListeners(Event1::class);

AcmeEvents::hasListenersFor( Listener1::class . '@foobar', Event1::class);
`````

The following combinations are valid ways to search for a registered listener.

`````php
[ Listener1::class, '*' ]
[ Listener1::class . '@*']
[ Listener1::class . '*' ]
[ Listener1::class, 'foobar' ]
[ Listener1::class . '@handleEvent']
[ Listener1::class, 'handleEvent' ]
`````

#### Deleting a listener for an event.

`````php
AcmeEvents::forgetOne( Event1::class, Listener1::class . '@foobar');
`````

**The combination of class and method has to be a match**. Only the class is not enough. However you can forget a
listner by only passing the classname if you registered the listener with the default ``handleEvent()`` method.

#### Deleting a listener for an event.

`````php
// This will work.
AcmeEvents::listen( Event1::class, Listener1::class );
AcmeEvents::forgetOne( Event1::class, Listener1::class );

// This won't
AcmeEvents::listen( Event1::class, Listener1::class . '@foobar' );
AcmeEvents::forgetOne( Event1::class, Listener1::class );
`````

#### Custom identifiers.

If you have created a listener using a custom identifier ( always a good idea when using closures ) you can also find
and remove a listener by its custom key.

`````php
// This will work.
AcmeEvents::listen( Event1::class, [ 'custom_id' => Listener1::class . '@foobar' ] );
AcmeEvents::forgetOne( Event1::class, 'custom_id' );


// This will also work with closures which is impossible with the default Wordpress Plugin API
AcmeEvents::listen( Event1::class, [ 'closure_key' => function ( Event1 $event ) {

       // do stuff 

} ] );

AcmeEvents::forgetOne( Event1::class, 'closure_key' );


`````
## Inbuilt Testing Module

In order to unit test code in the context of Wordpress, one should not have to bootstrap the entire Wordpress Core.
There are two great Wordpress mocking libraries out there:
- [Brain Monkey](https://github.com/Brain-WP/BrainMonkey) and
- [WP_Mock](https://github.com/10up/wp_mock).

Both are great and work. I have used them both before. But it never felt right to have to use a dedicated mocking framework just to be able not have code blow up because all Wordpress core function are undefined if you don't bootstrap core. ( and have ever unit test last 1 sec+ ).

Inspired by the way Laravel handles [event testing](https://laravel.com/docs/8.x/mocking#event-fake), BetterWpHooks was built with testing in mind before the first line of code was written.

This is how it works with BetterWpHooks: (example taken from the laravel docs)
````php
class OrderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test order shipping.
     */
    public function test_orders_can_be_shipped()
    {
        // This replaces the dispatcher instance with a FakeDispatcher
        AcmeEvents::fake();

        // Perform order shipping...

        // Assert that an event was dispatched...
        AcmeEvents::assertDispatched(OrderShipped::class);

        // Assert an event was dispatched twice...
        AcmeEvents::assertDispatchedTimes(OrderShipped::class, 2);

        // Assert an event was not dispatched...
        AcmeEvents::assertNotDispatched(OrderFailedToShip::class);

        // Assert that no events were dispatched...
        AcmeEvents::assertNothingDispatched();
    }
}
````

### Additional Validation

You can pass a closure to the `assertDispatched` or `assertNotDispatched` methods
in order to verify that an event was dispatched that passes the given "truth test".

````php
AcmeEvents::assertDispatched( function (OrderShipped $event) use ( $order ) {
    return $event->order->id === $order->id;
});
````

### Only faking some events

If you only want to fake some events and have the rest of them be handled by the real ```WordpressDispatcher```
you can do this by passing in an array of events into the ``AcmeEvents::fake()`` method.

````php
AcmeEvents::fake([
        OrderCreated::class,
    ]);

// OrderCreated will be faked, while other events will be run as usual.
````

**Important:** If you only wish to fake some events and have others be executed you will need access to all of the Wordpress core functions.

In order to not have to load the entire Wordpress Core, which will make your unit tests run well over a second, BetterWpHooks shipps with a custom test class `BetterWpHooksTestCase` that extends `\PHPUnit\Framework\TestCase`.

Extending `BetterWpHooksTestCase` will take care of loading **only one file**: `wp-includes/plugin.php`.

This will give you access to all the functionality provided by the Plugin/Hook API, but your tests will still run blazing fast.


## How it works

To understand how BetterWpHooks its necessary to explain how the Core Plugin/Hook API works.

At a basic level, everything you add via ``add_action()`` and ``add_filter()`` is stored in a global variable ``$wp_filter``. ( ...yikes )

Many WP-devs don't know this, but ``add_action()`` and ``add_filter()`` are exactly the same. The ``add_action`` function [only delegates](https://github.com/WordPress/WordPress/blob/master/wp-includes/plugin.php#L409) to ``add_filter()``.

When either the ``do_action('tag')`` or ``apply_filters('tag')`` is called, Wordpress iterates over every registered array item inside the 
global `$wp_filter['tag']` associative array and calls the registered callback functions.

A callback can either be:
- an anonymous function 
- a `[ CallbackClass::class, 'method' ]` combination, where ``method`` needs to be static in order to not cause deprecation errors.
- a `[ new CallbackClass(), 'method' ]` combination, where the handling class is already instantiated. This is the most common way in combination with adding hooks in the constructor like this:
```php
 class CallbackCLass {
 
    public function __construct() {
        
        add_action('init', [ $this , 'doStuff']);
    
    }
 
 }
```
### How events are dispatched 

***

The ```WordpressDispatcher``` class is responsible for dispatching events. You have access to an instance of this class via your ``AcmeEvents``Facade.

This is a simplyfied version of the responsible `dispatch` method.

````php
public function dispatch( $event, ...$payload ) {
			
	// Here we handle mapped events conditionally.		
	if ( ! $this->shouldDispatch( $event ) ) {
	
		return;
		
	}
	
	// Here we convert an event object so that the hook tag is the class name
	// and the payload is the actual event object
	// In a sense we just swap $event and $payload.	
	[ $event, $payload ] = $this->parseEventAndPayload( $event, $payload );
					
	// Here we handle temporal removal if a listener wants to stop
	// a listener chain.					
	$this->maybeStopPropagation( $event );
	
	// If no listeners are registered we just return the default value.	
	if ( ! $this->hasListeners( $event ) ) {
				
				
            if ( is_callable( [ $payload, 'default' ] ) ) {
            
                    return $payload->default();
            }
                        
            return is_object( $payload ) ? $payload : $payload[0];
                        
        }
		
	// If we make it this far, only here do we hit the Wordpress Plugin API.
        return $this->hook_api->applyFilter( $event, $payload );
			
			
}
````

As the code example demonstrates the Wordpress Plugin API is used, but through the layer of abstraction BetterWpHooks is able to introduce most of its before we actually hit the Plugin API. We also might never hit it [if conditions are not met](#conditional-event-dispatching) 

If all conditions were to pass, the following method call:

````php
BookingCreated::dispatch([$booking_data]);
````

would be similar to:
````php
// Traditional Wordpress implementation.
$booking = new Booking($booking_data);

add_action('acme_booking_created', $booking );
````

Now, Wordpress would call all the registered Hook Callbacks which brings us to:

### How Listeners are called.

***

BetterWpHooks serves as a layer between your plugin code and the Plugin API. It still uses the Plugin API but in a different way.

There are 3 types of Listeners BetterWpHooks creates under the hood depending of what you registered:

- Closure Listeners
- Instance Listeners
- Class Listeners

The difference between Instance Listeners and Class Listeners is, that an Instance Listener already contains an instantiated class ( because you passed it in ).

No matter which type of Listener is created, **they are all wrapped inside an anonymous closure** which is then passed to the Wordpress Plugin API.

This happens inside the ``ListenerFactory`` class.

````php

/**
 * Wraps the created abstract listener in a closure.
 * The Wordpress Hook Api will save this closure as
 * the Hook Callback and execute it at runtime.
 *
 * @param  \BetterWpHooks\Contracts\AbstractListener  $listener
 *
 * @return \Closure
 */
private function wrap( AbstractListener $listener ): Closure {
			
	// This anonymous function will be executed by Wordpress
	// Not the Listener directly.		
	return function ( $payload ) use ( $listener ) {
				
		try {
					
		     return $listener->shouldHandle( $payload ) ? $listener->execute( $payload ) : $payload;
					
		} 
		
		catch ( \Throwable $e ) {
					
		    $this->error_handler->handle($e);
					
		}
				
				
	};
}
````

Wordpress **does not directly call the class callable**. It only knows about the anonymous closure which when executed will execute the listener [if conditions are met](#conditional-event-listening).

Like this we can achieve lazy instantiation of objects and put an IoC-Container in between Wordpress and the Listener.
The actual building of the ```$listener``` happens inside the ``execute()`` method which is defined in the ``AbstractListener`` class and differs a bit for every listener type.

## Compatibility

TO-DO: Explain why its 100% compatible with Wordpress.

## TO-DO

- Set up CI
- Move the documentation to a dedicated site.
- Hire proffreader to correct my english mistakes ( I'm German ).

## Credits

- ``Laravel Framework``: While not depending on
  the [Illuminate/Events](https://packagist.org/packages/illuminate/events) package, BetterWpHooks was heavyly inspired
  by the way Laravel handles event dispatching.

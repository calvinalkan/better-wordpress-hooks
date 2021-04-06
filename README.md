# BetterWpHooks - A modern, OOP Wrapper around the WordPress Plugin API.

![CircleCI](https://img.shields.io/circleci/build/github/calvinalkan/better-wordpress-hooks/master?label=circleci&token=888f31e8ca77ad9621a420ab09ce799f3382d52e)
![code coverage](https://img.shields.io/badge/coverage-99%25-brightgreen)
![last commit](https://img.shields.io/github/last-commit/calvinalkan/better-wordpress-hooks)
![open issues](https://img.shields.io/github/issues-raw/calvinalkan/better-wordpress-hooks)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
![php version](https://img.shields.io/packagist/php-v/calvinalkan/better-wordpress-hooks)
![lines of code](https://img.shields.io/badge/total%20lines-2268-blue)

BetterWpHooks is a small library that wraps the WordPress Plugin/Hook API, allowing for modern, object-oriented PHP.

Some included features are:

🚀 Lazy instantiation of classes registered in actions and filters.

🔥 Zero configuration dependency-resolution of class and method dependencies using an IoC-Container.

❤ Conditional dispatching and handling of hooks based on parameters only available at runtime.

📦 Inbuilt testing module, no more third-party mocking libraries or bootstrapping core to test hooks.

⭐ 100 % compatibility with WordPress Core and the way users can interact with custom hooks and filters.

## Table of Contents

* [Why bother?](#why-bother)
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
* [Using BetterWpHooks](#using-betterwphooks)
  * [Complete Example with 3rd-party Hooks](#complete-example-using-third-party-hooks)
  * [Dispatching your own events](#dispatching-your-events)
  * [Wordpress Filters](#wordpress-filters)
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
* [Compatbility](#compatibility)    
* [TO-DO](#to-do)
* [Contributing](#contributing)
* [Credits](#credits)

***

## Why bother?

Being released in WordPress 2.1, in a time when OOP wasn't a thing yet in
PHP, [The WordPress Plugin-API](https://codex.wordpress.org/Plugin_API/Hooks) has several shortcomings.

**The main issues of the WordPress Plugin/Hook API are:**

1. No usage
   of [dependency injection or an IoC-Container](https://www.martinfowler.com/articles/injection.html#ServiceLocatorVsDependencyInjection)
   . If you care about the quality and maintainability of your code you use an IoC-Container.

2. Defining the number of parameters your callback should expect is annoying and often leads to seemingly random bugs if the order or amount of the received parameters should ever change. WordPress should be able to solve this on its own behind the scenes but due to the insane backwards compatibility commitments the native [PHP Reflection API](https://www.php.net/manual/en/book.reflection.php) can't be used.
3. There is **no proper place to define actions and filters**. Many WordPress developers default to using the class constructor which is not a great option for several reasons. Another common approach is using a custom factory which often leads to your IDE not being able to detect where you added hooks. Not ideal.
4. When using class-based callbacks, the only option besides using static methods ( *don't do that ) is to **instantiate the class on every request** before creating the WordPress Hook. There isn't any modern PHP framework that forces you to instantiate classes to **MAYBE** be used later as an event observer.
   Additionally, using OOP practices when defining hooks always leads to [hooks being unremovable for third-party developers](https://inpsyde.com/en/remove-wordpress-hooks/) because WordPress will use `spl_object_hash` to store the hook id.

Let's review an example that can be found similarly in 95% of popular WordPress plugins.

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

// Approach #2, Using static functions, worse.
add_action('init', [ MyClass::class, 'doStuffStatic' ]);
```

For a simple class this might work just fine, but now imagine that `MyClass`
has nested dependencies and handles several WordPress hooks. Constructing this object on every request just doesn't feel
right and changing the constructor arguments in the future might be very painful.

5. WordPress is event-driven. Hooks have to be added on every request. Yet there is **no clearly defined way to conditionally fire hooks** based on variables you only have available at runtime. Some code may only ever be required under very specific circumstances, yet the class callbacks get instantiated on every request. An example might be a class that handles sending a gift card if a customer placed an order with a total value greater than 500$.

```php
//Let's assume we want to send an email and log to an external service
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
    
    //Let's assume we get an order object from the hook.
    public function handle (Order $order) {
    
        if ( $order->total() >= 500 ) {
        
            $mailer->sendGiftcard($order->user());
            $logger->logBigBurchase($order);
        
        }
    }
}
```

We only ever use this class under very special circumstances, yet we have to create it on every request to pass it to
the WordPress hook.

- Lastly, unit testing ( **yes, WordPress plugins should be unit-tested** ) this code is complicated since it's tightly
  coupled to WordPress Core functions which means you either have to bootstrap the entire WordPress installation during
  your test set-up or use a WordPress mocking framework like [Brain Monkey](https://github.com/Brain-WP/BrainMonkey)
  or [WP_Mock](https://github.com/10up/wp_mock). I have used them both, they are great. But it should not be necessary
  to go through such hoops to test a basic event pattern.

### BetterWpHooks solves all of these problems and provides many more convenient features for a better WordPress developer experience.

***

## Requirements

- BetterWpHooks is a composer package, not a plugin. To be able to use this package you need to have [composer set up](https://getcomposer.org/doc/00-intro.md) in your plugin's root directory.
- PHP Version >= 7.3

In theory, you should be able to use this package with some minor modifications with every PHP Version >= 7.0 , but I did
not actively test it and neither should you be
using [PHP Versions that are not actively supported anymore](https://www.php.net/supported-versions.php) by the PHP
maintainers.

***

## Installation

From the root directory of your plugin or theme, execute the following command from the terminal.

``` composer require calvinalkan/better-wordpress-hooks ```

***

## Key Concepts

### Terminology

In the following WordPress actions and filters will be referred to as **events**. Hook callbacks, be it a class or an
anonymous closure will be referred to as **event listeners**.

### Entry Point

BetterWpHooks was built in mind with how the WordPress plugin ecosystem works. Unlike many other packages that try to
modernize WordPress BetterWpHooks **can be used by an unlimited amount of plugins at the same time without conflicts**.

The main entry point to the library is the
trait `BetterWpHooksFacade`([src](https://github.com/calvinalkan/better-wordpress-hooks/blob/master/src/Traits/BetterWpHooksFacade.php))
.

By creating a custom class that uses this Trait you gain access to your instance of the core of the library.

In the following, we are going to assume that we are using this library inside a plugin called **AcmePlugin**.

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

To auto-resolve dependencies of event listeners and automatically create objects, BetterWpHooks makes use of an Inversion
of Control (IoC) Container. Since many WordPress plugins already use an IoC-Container, it would make no sense to force the
usage of any specific container implementation.

The entire library is dependent on an ``ContainerAdapterInterface`` . By default, an adapter for
the `illuminate/container` is used. Every feature of the [Illumiante/Container](https://laravel.com/docs/8.x/container)
is fully supported.

The actual container implementation can be swapped out by confirming to a simple interface, so you can use any other
container like:

- [Symfony Container](https://symfony.com/doc/current/service_container.html)
- [Aura](http://auraphp.com/packages/2.x/Di.html)
- [Dice](https://r.je/dice)

The only constraint is that **auto-resolving ( auto-wiring ) needs to be supported by your container!**

### 2. Events

Events represent WordPress Actions and Filters. There is however no need to distinguish between the two. That is taken
care of under the hood.

There are two ways to use Events with BetterWpHooks:

1. Events as event objects (**recommended**, as it provides way more features)
2. Similar to WordPress Actions and Filters but still using the IoC-Container to resolve classes (not-recommended)

### 3. Event Dispatcher

This is the main class that servers as a layer between your plugin code and the WordPress Plugin API. Instead of
directly creating hooks via `add_action` and `add_filter` you create them using the Event Dispatcher.

### 4. Event Mapper

The Event Mapper is a small class that can be used to automatically transform core ( or 3rd-party ) actions and filters
into custom event objects, thus allowing you to use all the provided features even with events ( hooks ) you have no
control over.

***

## Bootstrapping

To use BetterWpHooks you need to bootstrap it in 3 simple steps. This should be done in your main plugin file
or any other file that gets executed **before** WordPress fires its first Hook. You should
also [require the composer autoloader](https://getcomposer.org/doc/01-basic-usage.md#autoloading) in your main plugin
file.

**1. Create your entry-point class**

```php
use BetterWpHooks\Traits\BetterWpHooksFacade;

class AcmeEvents {

use BetterWpHooksFacade;

}
```

**2. Create an instance of the ``BetterWpHooks`` class that will be mapped to ``AcmeEvents``:**

```php
AcmeEvents::make();

// Alternatively if you want to use a custom container:
$custom_container_adapter = new SymfonyContainerAdapter();
AcmeEvents::make($custom_container_adapter);
```

**3. Map core and 3rd party hooks to custom event objects ( optional but recommended )**.

All that this does is dispatching your custom event object when the WP Hooks is fired. We'll see why we do this in a
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

**The entire process can also be created as a fluent API**.

Ideally, you would create a plain PHP files that just return an array of your mapped events and events listeners. This
way you have all your events nicely in one file instead of being separated over your entire codebase.

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

Let's assume we are developing a Woocommerce Extension that gives users the possibility to extend their WooCommerce store
with several marketing-related features.

One of them being the gift card functionality described in the intro.

### Complete Example using third-party hooks.

We create an event that represents the
action ```woocommerce_checkout_order_processed``` ([src](https://github.com/dipolukarov/wordpress/blob/master/wp-content/plugins/woocommerce/classes/class-wc-checkout.php#L673))
. This Event needs to extend ``AcmeEvents``.

This action hook provides the order_id of the created order and the form submission data. We will create a working
example of this functionality in incremental steps improving it slowly.

1. Create an event object. Event objects are plain PHP objects that do not store any logic.

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
class ProcessGiftCards {

    private $mailer;
    private $logger;
    
   public function __construct( $mailer, $logger ) {
   
            $this->mailer = $mailer;
            $this->logger = $logger;
   }
   
   public function handleEvent ( HighValueOrderCreated $event) {
   
        $order = $event->order; 
        
        // Example properties. 
        $this->mailer->sendGiftCard( $order->user_id, $order->items );
        $this->logger->logGiftcardRecepient( $order->user_id, $order->items, $order->purchased_at );
        
   }

}
```

3. Wire the Event and Listener. ( The wiring should be done in a separate, plain PHP file )

```php
require __DIR__ . '/vendor/autoload.php';

$mapped = [

    'woocommerce_checkout_order_processed' => [
    
        HighValueOrderCreated::class 
        // Map more if needed.
]];

$listeners = [
    HighValueOrderCreated::class => [
    
        ProcessGiftCards::class . '@handleEvent'
        // More Listeners if needed
] ];

AcmeEvents::make()->mapEvents($mapped)->listeners($listeners)->boot();
```

**So what happens now when WooCommerce creates an order ?**

1. WordPress will fire the ``woocommerce_checkout_order_processed`` action.
2. Since under the hood a custom event was mapped to this action WordPress will now call a closure that will create a
   new instance of the ```HighValueOrderCreated``` Event using the **passed arguments from the filter** to construct the
   event object.
3. The called closure will first create the instance and then dispatch an event ```HighOrderValueCreated::class``` which
   passes the created object as an argument to any registered Listener.
4. Since we registered a listener for the ``HighOrderValueCreated`` event, the ``handleEvent``method on the ``ProcessGiftcards``class is now called. ( See [How it works](#) for a detailed explanation ).
5. The constructor dependencies ``$mailer, $logger`` are **automatically** injected into the class.
6. If the ``handleEvent()`` would have any method dependencies besides the event object, those **method dependencies
   would have also been injected automatically.**

### Improving

***

#### Conditional Dispatching

You might have noticed that we have not handled the logic regarding the conditional execution based on the order value
yet.

1. We apply a ```DispatchesConditionally``` Trait to our ```HighValueOrderCreated``` class. We also give users the choice to define a custom amount for when a gift card should be sent.

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

Before the Dispatcher dispatches an event that uses this trait the `shouldDispatch` Method is called and evaluated. If it
returns false,
**the event will not be fired by WordPress at all**. It will never hit the Plugin/Hook API and **no instance**
of ``ProcessGiftCards, Mailer and Logger`` **will be ever be created**.

#### Interfaces instead of concrete implementations.

We now want to give users the option to use a different mailer. We support Sendgrid, AWS and MailGun. How do we do this?
In order to not break
the [Dependency Inversion Principle](https://stackify.com/dependency-inversion-principle/#:~:text=Martin's%20definition%20of%20the%20Dependency,should%20not%20depend%20on%20details.)
we now use a ``MailerInterface``

Assuming you are using the default Container Adapter you can now do the following:

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

I hope both agree that this implementation is cleaner and most importantly, a lot more extensible and maintainable than
anything we can currently implement with the WordPress Plugin/Hook API.

### Dispatching your Events:

There are two ways you can dispatch events (hooks) in your code with BetterWpHooks.

1. **Dispatching events as objects.**

***

Instead of doing

```php
// Code that processes an appointment booking. 

do_action('booking_created', $book_id, $booking_data );
```

You can do the following:

```php
// Code that processes an appointment booking. 

BookingCreated::dispatch( [$book_id, $booking_data] );
```

This will first create a new instance of the ```BookingCreated``` event passing the arguments into the constructor and
then run the event through the ``Dispatcher`` instance provided by your custom class ``AcmeEvents`` ( Remember all events
would extend the ``AcmeEvents`` class ).

```php
class AcmeEvents {

use BetterWpHooksFacade;

}
```

When creating object events, all arguments that should be passed to the constructor **have to be wrapped in an array**.

This will not work:

```php 
BookingCreated::dispatch( $book_id, $booking_data );
```

Only the ``$book_id`` would be passed to the constructor.

***

2. **Dispatching events via the** ```AcmeEvents``` **class**

This approach can be used if you don't want to create a dedicated event object for an event.

```php
// Code that processes an appointment booking. 

AcmeEvents::dispatch( 'booking_created', $book_id, $booking_data );
```

This is similar to the way ``do_action()`` works, but you still get access to most features of BetterWpHooks like
auto-resolution of dependencies and the testing module. However, you won't be able to use conditional dispatching of
events quite the same as you would when using approach #1.

Besides, your listeners would need to accept the same number of parameters that you passed when dispatching the
event. With approach #1 your listeners **always receive one argument only**, the event object instance.

Using the Facade class you have two options to define arguments which both have the same outcome.

```php
// These are identical.
AcmeEvents::dispatch( 'booking_created', [ $book_id, $booking_data ] );
AcmeEvents::dispatch( 'booking_created',  $book_id, $booking_data  );
```

However, only **the first passed parameter must be the identifier of the event you want to dispatch.**
***

#### Dispatching Helpers.

Sometimes you might only ever want to dispatch one of your events under specific conditions.

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

Using the default Plugin/Hook API you need to distinguish between using ``add_action`` and ```add_filter``` .
BetterWpHooks takes care of this under the hood. The syntax is the same for defining actions and filters. Let's
review a simple example where we might want to allow other developers to modify appointment data created by our
fictional appointment plugin.

````php
// Code to create an appointment object

$appointment = AppointmentCreated::dispatch([ $appointment_object ]);

// Save appointment to the Database.
````

A third-party developer ( or maybe a paid extension of your plugin ) might now filter the appointment object just as he
normally would. Example:

````php
// When dispatching object events the hook id is always the full class name. 

add_filter('AppointmentCreated::class', function( AppointmentCreated $event) {
    
    $appointment = $event->appointment;
    
    if ( $some_conndition === TRUE ) {
    
     // increase cost.
     $appointment->cost = 50;
    
    }
    
   return $appointment;

} );
````

Alternatively you could dispatch the filter like this:

````php
$appointment = AcmeEvents::dispatch( 'acme_appointment_created', $appointment_object );
````

***

#### Default return values.

BetterWpHooks recognized that you are trying to dispatch a filter and will automatically handle the case where no
listeners might be created to pick up on the event. In that case no object will ever be built, in fact not event the
Plugin API will be called under the hood.

Default return values are evaluated in the following order:

1. If you are dispatching an event object you can define a ````default()```` method on the event object class. This method will be called if it exists, and the returned value will be passed as a default value.

2. If there is no ````default()```` method on the event class, but you are dispatching an event object, the object itself will be returned. For the example above the instance of ``AppointmentCreated`` would be returned.

3. If #1 and #2 are not possible the first parameter passed into the ````dispatch()```` method will be returned.

***

### Valid Listeners

A listener, just like with the default WordPress Plugin/Hook API can either be an anonymous closure, or a class callable.
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
using the inbuilt ``Illuminate/Container Adapter``. With other containers, there might be slight differences with how
you need to define method and constructor dependencies.

The ```BookingEventsListener``` shall handle various events regarding Bookings. ( creation, deletion, rescheduling etc.)

When a Booking is cancelled we want to notify the hotel owner and the guest,  and also make an API call to booking.com to
update our availability. However, we only need the ``BookingcomClient`` in one method of this EventListener let's not make
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
4. A new ``` BookingcomClient``` is instantiated.
5. The ```bookingCanceled()```method is called passing in the dispatched event object and the ```BookingcomClient```.

**Method arguments that are created from the dispatched event should always be declared first in the method signature
before any other dependencies**.

***

### Conditional Event Dispatching

This is meant to be used **for actions and filters whose triggering are not under your control.** ( i.e. core or third-party
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

The return value of the ````shouldDispatch```` method is evaluated every time **before** anything is executed.
If ```FALSE``` is returned, the WordPress Plugin/API will never be hit, neither will any class get instantiated.

### Conditional Event Listening

In some cases, it might be useful to determine at runtime if a listener should handle an Event.

Let's consider the following fictional Use case:

**Every time an appointment is booked you want to:**

1. Notify the responsible staff member via Slack.
2. Send a confirmation email to the customer
3. Add the customer to an external SaaS like Mailchimp.
4. **If** the customer booked an appointment with a total value greater than $300 you also want to notify the business owner via SMS, so he can personally serve the client.

So how could we do this?

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

When a listener has this trait, **before** calling the defined method ( here it's ``handleEvent()`` ) the return value
of ``shouldHandle``is evaluated. If false is returned the method will not be executed and the ```$event``` object will
get passed to the next listeners unchanged ( if there are any ).

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
    
        $business_owner_phone_number = $this->config->get('primary_phone_number');
     
        $this->sms_client->notify($business_owner_phone_number, $event->appointment);
        
    }
    
    // Abstract method defined in the trait
    public function shouldHandle( AppointmentCreated $event) : bool{
    
        return $event->appointment->totalValue() >= 300;
    
    } 
}
````

### Stopping a listener chain

If for some reason you want to stop every following listener from executing after a specific listener you can have the
listener use the ```StopsPropagation``` Trait.

**Caveats:**
This will remove every listener registered by **your instance of `BetterWpHooks`** for the current request. The order in
which listeners were registered does not matter. The first registered listener that is using the ``StopsPropagation`` Trait will be called, while every other listener will be removed for the current request.

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

If ``Listener4`` were to use the ```StopsPropagation``` Trait, every other Listener would be removed for ``Event1``
during the current request.

## API

In theory, you should not need to use the underlying service classes provided to you by your ``AcmeEvents``
class outside the [bootstrapping](#bootstrapping) process.

If that need arises, BetterWpHooks makes it easy to do so.

Your class ``AcmeEvents`` ( see [Entry-Point](#entry-point) ) serves as Facade to the underlying services, pretty
similar to how [Laravel Facades](https://laravel.com/docs/8.x/facades) work.

Every static method call is not static but resolves to the underlying ```BetterWpHooks``` instance of your
class using PHP's
```_callStatic()``` magic-method.

There is a dedicated
class ````Mixin```` ( [src](https://github.com/calvinalkan/better-wordpress-hooks/blob/master/src/Mixin.php)) that
provides IDE-autocompletion and also serves as documentation of the available methods.

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

The listener will now be unremovable through your ``AcmeEvents`` class, and the only other possibility would be to guess
the exact  ``spl_object_hash()`` since that
is [how WordPress creates hook-ids](https://github.com/WordPress/WordPress/blob/b70c00d6acd441af54342f147ab3db1b840632e5/wp-includes/plugin.php#L916)
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
[ Listener1::class, '*' ]               // will search for Listener1::class + any method
[ Listener1::class . '@*']              // will search for Listener1::class + any method
[ Listener1::class . '*' ]              // will search for Listener1::class + any method
[ Listener1::class, 'foobar' ]          // will search for Listener1::class + foobar method
[ Listener1::class . '@handleEvent']    // will search for Listener1::class + handleEvent method
[ Listener1::class, 'handleEvent' ]     // will search for Listener1::class + handleEvent method
[ Listener1::class]                     // will search for Listener1::class + handleEvent method
`````

#### Deleting a listener for an event.

`````php
AcmeEvents::forgetOne( Event1::class, Listener1::class . '@foobar');
`````

**The combination of class and method has to be a match**. Only the class is not enough. However, you can forget a
listener by only passing the class name if you registered the listener with the default ``handleEvent()`` method.

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


// This will also work with closures which is impossible with the default WordPress Plugin API
AcmeEvents::listen( Event1::class, [ 'closure_key' => function ( Event1 $event ) {

       // do stuff 

} ] );

AcmeEvents::forgetOne( Event1::class, 'closure_key' );


`````

## Inbuilt Testing Module

To unit test code in the context of WordPress, one should not have to bootstrap the entire WordPress Core.
There are two great WordPress mocking libraries out there:

- [Brain Monkey](https://github.com/Brain-WP/BrainMonkey)
- [WP_Mock](https://github.com/10up/wp_mock).

Both are great and work, I have used them both before. However, it never felt right to have to use a dedicated mocking
framework just so that all the code does not blow up because WordPress Core functions are undefined.

Inspired by the way Laravel handles [event testing](https://laravel.com/docs/8.x/mocking#event-fake), BetterWpHooks was
built with testing in mind before the first line of code was written.

There are two ways you can use the testing module with BetterWpHooks:

**1. Completely swapping out the underlying dispatcher with a fake dispatcher:** Using this option none of your
registered listeners will be executed.

````php
class OrderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test order shipping.
     */
    public function test_orders_can_be_shipped()
    {
        // This replaces the underlying dispatcher instance with a FakeDispatcher
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

You can also pass a closure to the `assertDispatched` or assertNotDispatched` methods to assert that an event
was dispatched that passes a given "truth test".

````php
// create $order

AcmeEvents::assertDispatched(function (OrderShipped $event) use ($order) {
    return $event->order->id === $order->id;
});
````

**2. Only faking a subset of events:** If you are doing Integration Testing, but you still want to fake some events, (
maybe because they are talking to a slow or unstable external API ) you might do the following:

1. Create a test that extends `BetterWpHooksTestCase`
2. In the `setUp` method of your test, call `$this->setUpWp`
3. In the `tearDown` method of you test, `$this->tearDownWp`

`BetterWpHooksTestCase` extends `\PHPUnit\Framework\TestCase` and takes care of loading all **only**
the WordPress Hook API. Loading single pieces of WordPress is a dangerous and brittle endeavour, which is why we created
a
stand-alone [repo that exactly mirrors the WordPress Hook API](https://github.com/calvinalkan/wordpress-hook-api-clone).
This repo is manually synced for every WordPress release.

`BetterWpHooksTestCase` also takes care of clearing the global state before and after every test.

You get the full features of the WordPress Hook API, but your tests will still run blazing fast.

Let's assume we want to test the following method.

````php
// SUT
class OrderProcess {

    public function processNewOrder ($form_data) {
    
            // Do stuff with $form_data
            
            OrderCreated::dispatch([$order]);
            StockStatusUpdated::dispatch([$order]);
    
    }

}

// Registered Listeners 
$listeners = [
    
                OrderCreated::class => [
                
                    
                    // Listeners you want for integration tests
                
            ], 

                StockStatusUpdated::class => [
            
                    UpdateSlowThirdPartyApi::class,
            ]
 
        ];



// Test
class OrderProcessTest extends \BetterWpHooks\Testing\BetterWpHooksTestCase {

    protected function setUp() : void{
    
        parent::setUp(); 
        $this->setUpWp();
        
        // You need to set up your events and listeners
        $this->bootstrapAcmeEvents();
        
      
    }   

    protected function tearDown() : void{
    
        parent::tearDown();
        $this->tearDownWp();
        
    }

    public function test_orders_can_be_processed()
    {
    
        $subject = new OrderProcess();
    
        AcmeEvents::fake([
        
            StockStatusUpdated::class,
            
        ]);
        
        // All listeners for OrderCreated::class will be called. 
        // UpdateSlowThirdPartyApi::class will NOT be called. 
        $subject->processNewOrder([ // $test_data ]);
       
        // Assertions work for both events. 
        AcmeEvents::assertDispatched(OrderCreated::class);
        AcmeEvents::assertDispatched(StockStatusUpdated::class);
    
    }

}
````

The `$this->bootstrapAcmeEvents();` can be anything you want, but if you want your listeners to execute you need to properly [bootstrap your `AcmeEvents`instance](#bootstrapping).
It's recommended that you create a custom factory class and **don't bootstrap your instance in your main plugin file.**, so you maintain greater testing flexibility.

## How it works

To understand how BetterWpHooks works, it's necessary to explain how the Core Plugin/Hook API works.

At a basic level, everything you add via ``add_action()`` and ``add_filter()`` is stored in a global
variable ``$wp_filter``. ( ...yikes )

Many WP-devs don't know this, but ``add_action()`` and ``add_filter()`` are exactly the same. The ``add_action``
function [only delegates](https://github.com/WordPress/WordPress/blob/master/wp-includes/plugin.php#L409)
to ``add_filter()``.

When either ``do_action('tag')`` or ``apply_filters('tag')`` is called, Wordpress iterates over every registered
array key inside the global `$wp_filter['tag']` associative array and calls the registered callback functions.

A callback can either be:

- an anonymous function
- a `[ CallbackClass::class, 'method' ]` combination, where ``method`` needs to be static to not cause
  deprecation errors.
- a `[ new CallbackClass(), 'method' ]` combination, where the handling class is already instantiated. This is the most
  commonly used way in combination with adding hooks in the constructor:

```php
 class CallbackCLass {
 
    public function __construct() {
        
        add_action('init', [ $this , 'doStuff']);
    
    }
 
 }
```

### How events are dispatched

***

The ```WordpressDispatcher``` class is responsible for dispatching events. You have access to an instance of this class
via your ``AcmeEvents``Facade.

This is a simplified version of the responsible `dispatch` method.

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
					
	// Here we handle temporary removal if a listener wants to stop
	// a listener chain.					
	$this->maybeStopPropagation( $event );
	
	// If no listeners are registered we just return the default value.	
	if ( ! $this->hasListeners( $event ) ) {
				
				
            if ( is_callable( [ $payload, 'default' ] ) ) {
            
                    return $payload->default();
            }
                        
            return is_object( $payload ) ? $payload : $payload[0];
                        
    }
		
	// If we make it this far, only here do we hit the WordPress Plugin API.
    return $this->hook_api->applyFilter( $event, $payload );
			
			
}
````

Like the example demonstrates, the WordPress Plugin API is used but through a layer of abstraction, BetterWpHooks can introduce most of its before we hit the Plugin API. We also might never hit
it [if conditions are not met](#conditional-event-dispatching)

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

Now, WordPress would call all the registered Hook Callbacks which brings us to:

### How Listeners are called.

***

BetterWpHooks serves as a layer between your plugin code and the Plugin API. It still uses the Plugin API but in a
different way.

There are 3 types of Listeners BetterWpHooks creates under the hood depending on you defined them during the bootstrapping process.

- Closure Listeners
- Instance Listeners
- Class Listeners

The difference between Instance Listeners and Class Listeners is, that an Instance Listener already contains an
instantiated class ( because you passed it in ).

No matter which type of Listener is created, **they are all wrapped inside an anonymous closure** which is then passed
to the WordPress Plugin API.

This happens inside the ``ListenerFactory`` class.

````php

/**
 * Wraps the created abstract listener in a closure.
 * The WordPress Hook Api will save this closure as
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

WordPress **does not directly call the class callable**. It only knows about the anonymous closure which when executed
will execute the listener [if conditions are met](#conditional-event-listening).

Like this, we can achieve lazy instantiation of objects and put an IoC-Container in between WordPress and the Listener.
The actual building of the ```$listener``` happens inside the ``execute()`` method which is defined in
the ``AbstractListener`` class and differs a bit for every listener type.

## Compatibility

BetterWpHooks is 100% compatible with how the WordPress Plugin/Hook API works.

- No core files get modified.
- No custom Event/Observer pattern is introduced. Actions and Filters are executed the same way as they normally would.
- **Can be used by any amount of plugins on the same site.** Since every plugin creates its Facade via the `BetterWpHooksFacade` Trait, there will never be a case where two plugins try to do conflicting stuff with the Dispatcher or the IoC-Container.
- **Third-Party developers can create custom hooks for your events the same way they normally would** using `add_action`, `add_filter` . Arguably it's even easier since callbacks will receive just one parameter so that they don't have to search the docs for the number of parameters they need to use.
- Additional features: It's very easy to allow the removal/customization of hooks for advanced users. Normally with WordPress, it would be very hard, to remove a hook, that uses an instantiated object as the hook callback, because WordPress uses the `spl_object_hash()` function to store the hook_id. The same goes for closures. There are even [dedicated packages trying to solve this exact problem](https://github.com/inpsyde/objects-hooks-remover), removing plugin hooks when objects or closures are used.
  With BetterWpHooks this becomes quite easy for users that want to customize your plugin.
  If you like, you could provide your own custom functions to interact with your `BetterWpHooksFacade` instance. For example:
  
```php 
if ( ! function_exists('acme_remove_filter') {
    function acme_remove_filter($tag, $callback) {
    
        AcmeEvents::forgetOne($tag, $callback);

    }
}

// Third-party dev that wants to customise AcmeEvents
acme_remove_filter(Event1::class, Listener1::class);

// This works. 
add_filter(Event1::class, ThridPartyListener::class)
```
No more accessing the global `$wp_filter` or editing source files because hooks are unremovable. You also don't have to remember the hook priority like you would when trying to remove a hook with `remove_filter()` .

**One caveat:**

If you are using this library, or any other third-party dependency for that matter in a plugin that you plan on distributing on WordPress.org there is the risk of running into conflicts, when two 
plugins require the same dependency but bundle it in different versions. 

The composer autoloader will only load the version that is first required. Since WordPress loads plugins alphabetically there might be issues if your plugin relies on features, that are only implemented in newer versions of a dependency, while the plugin that was first loaded required an older version.

**This is not an issue of composer, nor of this library**, but from WordPress still not having a dedicated solution for dependency management in 2021. 

Until WordPress finds are way to solve this, the only way to be 100% is to wrap every dependency that your plugin has in your own namespace (...yikes again).

However, there are projects that facilitate this process: 

- [imposter-plugin](https://github.com/Typisttech/imposter-plugin)
- [mozart](https://github.com/coenjacobs/mozart)

For further info on this matter check out this article and **especially the comment section.** 

[https://wppusher.com/blog/a-warning-about-using-composer-with-wordpress/](https://wppusher.com/blog/a-warning-about-using-composer-with-wordpress/)

## TO-DO

- Move the documentation to a dedicated site.
- Improve grammar and spelling of README.md ( I'm German ) - **pull requests are very welcome.**

## Contributing

BetterWpHooks is completely open-source and everybody is encouraged to participate by:

- Reviewing `CONTRIBUTING.md`
- ⭐ the project on GitHub ([https://github.com/calvinalkan/better-wordpress-hooks](https://github.com/calvinalkan/better-wordpress-hooks))
- Posting bug reports ([https://github.com/calvinalkan/better-wordpress-hooks/issues](https://github.com/calvinalkan/better-wordpress-hooks/issues))
- (Emailing security issues to [calvin@snicco.de](mailto:calvin@snicco.de) instead)
- Posting feature suggestions ([https://github.com/calvinalkan/better-wordpress-hooks/issues](https://github.com/calvinalkan/better-wordpress-hooks/issues))
- Posting and/or answering questions ([https://github.com/calvinalkan/better-wordpress-hooks/issues](https://github.com/calvinalkan/better-wordpress-hooks/issues))
- Submitting pull requests ([https://github.com/calvinalkan/better-wordpress-hooks/pulls](https://github.com/calvinalkan/better-wordpress-hooks/pulls))
- Sharing your excitement about BetterWpHooks with your community




## Credits

- ``Laravel Framework`` While not depending on
  the [Illuminate/Events](https://packagist.org/packages/illuminate/events) package, BetterWpHooks was heavily inspired by the way Laravel handles event dispatching. Especially the testing features are very close to the [laravel testing features](https://github.com/illuminate/support/blob/master/Testing/Fakes/EventFake.php).

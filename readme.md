# Aws Ses Bounce Complaint Helper


[![GitHub build status][ico-github]][link-github]
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]
[![StyleCI][ico-styleci]][link-styleci]
[![Scrutinizer Code Quality][ico-scrutinizer]][link-scrutinizer]
[![Code Coverage][ico-scrutinizer-coverage]](https://scrutinizer-ci.com/g/ag84ark/aws-ses-bounce-complaint-handler/?branch=master)

Helper for handling AWS SES with SNS 
This works with HTTP(S) calls or SQS, HTTP(S) recommended! 
Take a look at [contributing.md](contributing.md) to see a to do list.

## Installation

Via Composer

``` bash
$ composer require ag84ark/aws-ses-bounce-complaint-handler
```

---
Publish the migration and run them with:

```bash
php artisan vendor:publish --provider="ag84ark\AwsSesBounceComplaintHandler\AwsSesBounceComplaintHandlerServiceProvider" --tag="migrations"
php artisan migrate
```

---
You can publish the config file with:
```bash
php artisan vendor:publish --provider="ag84ark\AwsSesBounceComplaintHandler\AwsSesBounceComplaintHandlerServiceProvider" --tag="config"
```
---
Add the route to the except list in  App\Http\Middleware\VerifyCsrfToken.php 
```php
class VerifyCsrfToken extends Middleware
{
    protected $except = [
        // ... 
        'amazon-sns/notifications'
    ];
}
```

Add link in AWS SNS for AWS SES email bounce and complains to: /amazon-sns/notifications  
Also be sure to check the __Enable raw message delivery__ option  

---

## Usage

#### Check to see if it is safe to send email
```php
$email = "me@example.com";
AwsSesBounceComplaint::canSendToEmail($email);
```

#### To stop emails from being sent to unsafe email addresses automatically
Add in App\Providers\EventServiceProvider.php

```php
class EventServiceProvider {

    protected $listen = [
        // ...
        Illuminate\Mail\Events\MessageSending::class => [
            App\Listeners\CheckEmailAddressBeforeSending::class,
        ],
    ];
}
```

In  App\Listeners\CheckEmailAddressBeforeSending.php
```php
<?php
 
 namespace App\Listeners;
 
 use ag84ark\AwsSesBounceComplaintHandler\AwsSesBounceComplaintHandler;
 use Illuminate\Mail\Events\MessageSending;
 
 class CheckEmailAddressBeforeSending
 {
     public function __construct()
     {
         //
     }
 
     public function handle(MessageSending $event): bool
     {
         $email = $event->data['email'];
         if (!AwsSesBounceComplaintHandler::canSendToEmail($email)) {
             \Log::info(json_encode($event->data));
             // log the information in some way   
             return false;
         }
 
 
         return true;
 
     }
 }

```


#### To be able to resend to a "banned" email address use
```php
$email = "me@example.com";
AwsSesBounceComplaint::ignoreEmail($email);
```
This will mark the entries to be ignored when running __canSendToEmail__ but will only work if you run this after the email address was marked as bad. Useful in development.

#### To remove all entries of an email
```php
$email = "me@example.com";
AwsSesBounceComplaint::clearEmail($email);
```
## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email author email instead of using the issue tracker.

## Credits

- [ag84ark][link-author]


## License

Please see the [license file](license.md) for more information.

[ico-github]: https://github.com/ag84ark/aws-ses-bounce-complaint-handler/workflows/Tests/badge.svg
[ico-version]: https://img.shields.io/packagist/v/ag84ark/aws-ses-bounce-complaint-handler.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/ag84ark/aws-ses-bounce-complaint-handler.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/ag84ark/aws-ses-bounce-complaint-handler/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/280539001/shield
[ico-scrutinizer]: https://scrutinizer-ci.com/g/ag84ark/aws-ses-bounce-complaint-handler/badges/quality-score.png?b=master
[ico-scrutinizer-coverage]: https://scrutinizer-ci.com/g/ag84ark/aws-ses-bounce-complaint-handler/badges/coverage.png?b=master

[link-github]: https://github.com/ag84ark/aws-ses-bounce-complaint-handler/actions
[link-packagist]: https://packagist.org/packages/ag84ark/aws-ses-bounce-complaint-handler
[link-downloads]: https://packagist.org/packages/ag84ark/aws-ses-bounce-complaint-handler
[link-travis]: https://travis-ci.org/ag84ark/aws-ses-bounce-complaint-handler
[link-styleci]: https://styleci.io/repos/280539001
[link-author]: https://github.com/ag84ark
[link-scrutinizer]: https://scrutinizer-ci.com/g/ag84ark/aws-ses-bounce-complaint-handler/?branch=master
[link-scrutinizer-coverage]: https://scrutinizer-ci.com/g/ag84ark/aws-ses-bounce-complaint-handler/?branch=master


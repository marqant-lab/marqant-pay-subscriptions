# Marqant Pay Subscriptions

This package is an extension of the [marqant-lab/marqant-pay](https://github.com/marqant-lab/marqant-pay) package and
 provides subscription functionality for it.

## Installation

To install this package you just need to run the good old composer command that you all know and love.

```shell script
composer require marqant-lab/marqant-pay-subscriptions 
```

Next you will need to create the migrations to hook this package up to your database. Make sure to replace the `User` 
 model with whatever you use as billable. The rest of the values will be taken from the configuration of this package.
 You can overwrite them if you want to.

```shell script
php artisan marqant-pay:migrations:subscriptions App\\User
# or
php artisan marqant-pay:migrations:subscriptions "App\\User"
```

Now you can run your migrations as usual to finish up the installation.

```shell script
php artisan migrate
```

And that's it, you have extended your project with subscriptions 🤯
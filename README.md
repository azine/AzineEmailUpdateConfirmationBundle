AzineEmailUpdateConfirmationBundle
==================

Symfony bundle which allows to use functionality of email change confirmation based on FOSUserBundle. 



## Installation
To install AzineEmailUpdateConfirmationBundle with Composer just add the following to your `composer.json` file:

```
// composer.json
{
    // ...
    require: {
        // ...
        "azine/emailupdateconfirmation-bundle": "dev-master"
    }
}
```
Then, you can install the new dependencies by running Composer’s update command from 
the directory where your `composer.json` file is located:

```
php composer.phar update
```
Now, Composer will automatically download all required files, and install them for you. 
All that is left to do is to update your AppKernel.php file, and register the new bundle:

```
<?php

// in AppKernel::registerBundles()
$bundles = array(
    // ...
    new Azine\EmailUpdateConfirmationBundle\AzineEmailUpdateConfirmationBundle(),
    // ...
);
```

Register the routes of the AzineEmailUpdateConfirmationBundle:

```
// in app/config/routing.yml

azine_email_update_confirmation_bundle:
    resource: "@AzineEmailUpdateConfirmationBundle/Resources/config/routing.yml"
   
    
```
Setup from_email parameter:

```
// in app/config/config.yml

azine_email_update_confirmation:
    from_email: test@example.com
```
## Configuration options
This is the complete list of configuration options with their defaults.

```
//app/config/config.yml

// Default configuration for "AzineEmailUpdateConfirmationBundle"
azine_email_update_confirmation:
    enabled:        false # enables email update confirmation functionality
    cypher_method:  null # determines the encryption mode for encryption of email value
    mailer:         azine.email_update.mailer # mailer service
    email_template: @AzineEmailUpdateConfirmation/Email/email_update_confirmation.txt.twig # email template file
    redirect_route: fos_user_profile_show # route to redirect after email confirmation
    from_email:      # from email
    

```

# Contribute
Contributions are very welcome. Please fork the repository and issue your pull-request against the master branch.

The PR should:
- contain a description what the PR solves or adds to the bundle (reference existing issues if applicable)
- contain clean code with some iniline documentation and phpdocs, no "pure whitespace" changes.
- respect the [Symfony best practices](http://symfony.com/doc/current/bundles/best_practices.html) and coding style
- have phpunit tests covering the new feature or fix
- result in a 'green' build for your branch on [travis-ci.org](https://travis-ci.org/azine/AzineEmailUpdateConfirmationBundle/branches) before you issue the PR

## Code style
You can check the code style with the `php-cs-fixer`. Optionally you can set up a pre-commit hook which contains the `php-cs-fixer` check. Also see https://github.com/FriendsOfPHP/PHP-CS-Fixer

All you have to do is to move `pre-commit.sample` file from `commit-hooks/` to `.git/hooks/` folder and rename it to `pre-commit`.

`php-cs-fixer` will check the style of your new added code each time you commit and apply fixes to the commit.

To run `php-cs-fixer` manually, install dependencies (`composer install`) and execute `php vendor/friendsofphp/php-cs-fixer/php-cs-fixer --diff --dry-run -v fix --config=.php_cs.dist .`


## Build-Status ec.

[![Build Status](https://travis-ci.org/azine/AzineEmailUpdateConfirmationBundle.svg)](https://travis-ci.org/azine/AzineEmailUpdateConfirmationBundle)
[![Total Downloads](https://poser.pugx.org/azine/emailupdateconfirmation-bundle/downloads)](https://packagist.org/packages/azine/emailupdateconfirmation-bundle)
[![Latest Stable Version](https://poser.pugx.org/azine/emailupdateconfirmation-bundle/v/stable)](https://packagist.org/packages/azine/emailupdateconfirmation-bundle)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/azine/AzineEmailUpdateConfirmationBundle/badges/quality-score.png)](https://scrutinizer-ci.com/g/azine/AzineEmailUpdateConfirmationBundle/)
[![Code Coverage](https://scrutinizer-ci.com/g/azine/AzineEmailUpdateConfirmationBundle/badges/coverage.png)](https://scrutinizer-ci.com/g/azine/AzineEmailUpdateConfirmationBundle/)

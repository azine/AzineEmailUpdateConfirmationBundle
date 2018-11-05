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
php composer.phar require azine/emailupdateconfirmation-bundle
```
Now, Composer will automatically download all required files, and install them for you. 
All that is left to do is to update your AppKernel.php file, and register the new bundle:

```php
<?php

// in AppKernel::registerBundles()
$bundles = array(
    // ...
    new Azine\EmailUpdateConfirmationBundle\AzineEmailUpdateConfirmationBundle(),
    // ...
);
```

Register the routes of the AzineEmailUpdateConfirmationBundle:
```yml
// in app/config/routing.yml

azine_email_update_confirmation_bundle:
    resource: "@AzineEmailUpdateConfirmationBundle/Resources/config/routing.yml"
```

Setup from_email parameter:
```yml
// in app/config/config.yml

azine_email_update_confirmation:
    from_email: test@example.com
```
## Configuration options
This is the complete list of configuration options with their defaults.
```yml
//app/config/config.yml

# Default configuration for "AzineEmailUpdateConfirmationBundle"
azine_email_update_confirmation:

    # enable/disable email update confirmation functionality. default = true
    enabled:              true

    # determines the encryption mode for encryption of email value. openssl_get_cipher_methods(false) is default value
    cypher_method:        null

    # mailer service to be used
    mailer:               azine.email_update.mailer

    # email template
    email_template:       '@AzineEmailUpdateConfirmation/Email/email_update_confirmation.txt.twig'

    # `from`-address for the email. If not set, `fos_user.resetting.email.from_email` will be used
    from_email:           ~

    # `from`-name for the email. If not set, `fos_user.resetting.email.from_email` will be used
    from_name:            ~

    # route to redirect to, after the update confirmation
    redirect_route:       fos_user_profile_show

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

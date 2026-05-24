AzineEmailUpdateConfirmationBundle
==================

Symfony bundle which allows email change confirmation workflows based on FOSUserBundle.

## Requirements

- PHP **8.5+**
- Symfony components **7.4+**
- Composer 2

## Installation

Install with Composer:

```bash
composer require azine/emailupdateconfirmation-bundle
```

Register the bundle:

```php
// config/bundles.php
return [
    // ...
    Azine\EmailUpdateConfirmationBundle\AzineEmailUpdateConfirmationBundle::class => ['all' => true],
];
```

Register routes:

```yaml
# config/routes.yaml
azine_email_update_confirmation_bundle:
    resource: "@AzineEmailUpdateConfirmationBundle/Resources/config/routing.yml"
```

## Configuration options

```yaml
azine_email_update_confirmation:
    enabled: true
    cypher_method: null
    mailer: azine.email_update.mailer
    email_template: '@AzineEmailUpdateConfirmation/Email/email_update_confirmation.txt.twig'
    redirect_route: fos_user_profile_show
    from_email: '%fos_user.resetting.email.from_email%'
```

## Development

Install dependencies and run tests:

```bash
composer update
vendor/bin/phpunit -c phpunit.xml.dist
```

## CI

This repository now uses **GitHub Actions** for automated test execution on every `push` and `pull_request`.
Legacy Travis CI configuration has been removed as part of the modernization to supported CI infrastructure.

## Upgrade notes

- Minimum PHP version is now `^8.5`.
- Symfony component constraints are now `^7.4`.
- Dev tooling upgraded to modern versions (PHPUnit 11, PHP-CS-Fixer 3).
- Event dispatch usage and translator/session interfaces were modernized for Symfony 7 compatibility.

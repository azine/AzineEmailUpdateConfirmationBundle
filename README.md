# Azine Email Update Confirmation Bundle

Requires confirmation before a FOSUserBundle user's changed email address is persisted.

## Requirements

- PHP 8.5
- Symfony 7.4
- Doctrine ORM 3.6
- FOSUserBundle 4.1
- Symfony Mailer, Twig 3 and OpenSSL

## Installation

```bash
composer require azine/emailupdateconfirmation-bundle:^2.0
```

Register the bundle in `config/bundles.php` and import its routes:

```php
<?php

return [
    // ...
    Azine\EmailUpdateConfirmationBundle\AzineEmailUpdateConfirmationBundle::class => ['all' => true],
];
```

```yaml
# config/routes/azine_email_update_confirmation.yaml
azine_email_update_confirmation:
    resource: '@AzineEmailUpdateConfirmationBundle/Resources/config/routing.yml'
```

## Configuration

```yaml
# config/packages/azine_email_update_confirmation.yaml
azine_email_update_confirmation:
    redirect_route: fos_user_profile_show
    confirmation_ttl: 86400
    allow_legacy_payloads: true
    cipher_method: AES-128-CBC
    from_email:
        address: no-reply@example.test
        sender_name: Example
```

New links contain an authenticated, expiring payload. `allow_legacy_payloads` defaults to `true` so pending 1.x links remain usable during rollout; disable it after the longest possible old-link lifetime has elapsed.

The legacy misspelling `cypher_method` remains accepted for migration but new configuration must use `cipher_method`.

## Mail delivery

The bundle includes a Symfony Mailer implementation by default.

To reuse templates and delivery from `azine/email-bundle` 5.0.1 or later:

```yaml
azine_email_update_confirmation:
    mailer: azine_email.default.template_twig_mailer
```

The bundle automatically wraps that service with its adapter and explicitly addresses the confirmation message to the pending new email address.

Custom mailer services must implement:

```php
Azine\EmailUpdateConfirmationBundle\Mailer\EmailUpdateConfirmationMailerInterface
```

## Persistence flow

The Doctrine listener runs in `onFlush` so it can:

1. detect the attempted email change;
2. restore the currently persisted email address;
3. generate and persist a new confirmation token in the same flush;
4. send the confirmation message in `postFlush`.

When the authenticated user opens a valid link, the controller applies the new email, clears the confirmation token through the same listener, and redirects only to the configured `redirect_route`. User-controlled redirect route names are ignored. The old route shape remains available only so already-issued links do not break.

## Security behavior

- payloads are encrypted and authenticated with independent token-derived keys;
- modified payloads are rejected;
- new payloads expire according to `confirmation_ttl`;
- the confirmation token provides replay protection and is cleared after successful confirmation;
- the authenticated user must match the token owner;
- invalid, expired or malformed links return the generic configured error instead of exposing details.

## Development

```bash
composer validate --strict --no-check-publish
composer update
composer lint
composer test
```

CI runs stable and lowest dependency sets on PHP 8.5 and fails on PHP syntax errors, PHPUnit notices, deprecations, risky tests or warnings.

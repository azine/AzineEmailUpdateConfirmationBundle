# Changelog

## 2.0.0

- Upgrade to PHP 8.5, Symfony 7.4, Doctrine ORM 3.6, FOSUserBundle 4.1, Twig 3 and PHPUnit 12.
- Migrate the default mailer to Symfony Mailer.
- Add a tested adapter for `azine/email-bundle:^5.0.1`.
- Persist generated confirmation tokens reliably by moving Doctrine handling to `onFlush`.
- Send notifications after persistence in `postFlush`.
- Remove user-controlled redirects; use only the configured route while retaining a legacy route shape for already-issued links.
- Add authenticated versioned email payloads with configurable expiry and optional legacy-payload migration support.
- Add tampering, expiry, routing, adapter and Doctrine lifecycle regression coverage.

# Inertia Bundle for Symfony

[![Latest Stable Version](https://poser.pugx.org/creativspeed/inertia-bundle/v/stable)](https://packagist.org/packages/creativspeed/inertia-bundle)
[![Total Downloads](https://poser.pugx.org/creativspeed/inertia-bundle/downloads)](https://packagist.org/packages/creativspeed/inertia-bundle)
[![License](https://poser.pugx.org/creativspeed/inertia-bundle/license)](https://packagist.org/packages/creativspeed/inertia-bundle)

[Inertia.js](https://inertiajs.com/) for Symfony — build a Vue or React single-page app against your existing Symfony controllers, without hand-rolling a JSON API.

```php
#[Route('/', name: 'home')]
public function index(InertiaInterface $inertia): Response
{
    return $inertia->render('Home', [
        'title' => 'Hello from Symfony!',
    ]);
}
```

## Features

- Full **Inertia.js v3 protocol** — lazy, always, deferred (with groups), and merge/deep-merge props
- Automatic **302→303 redirect conversion** and **asset-version conflict** handling (the two things every from-scratch integration forgets)
- **Server-side rendering** with a real body+head hand-off to your Twig shell
- **Vite integration** with automatic asset-version detection from the manifest
- Automatic **auth data** and **flash message** sharing on every response
- `back()` / `redirect()` / `location()` / `redirectWithErrors()` helpers
- History encryption (`encryptHistory()` / `clearHistory()`)
- A testing trait (`InertiaAssertionsTrait`) for asserting on Inertia responses in PHPUnit

## Requirements

- PHP 8.2+
- Symfony 7.0+ (including Symfony 8)
- `symfony/security-bundle` (used for automatic auth-data sharing)

## Install

```bash
composer require creativspeed/inertia-bundle
```

Not using Symfony Flex? Register it manually:

```php
// config/bundles.php
return [
    // ...
    CreativSpeed\InertiaBundle\InertiaBundle::class => ['all' => true],
];
```

Then read **[docs/installation.md](docs/installation.md)** for the root template, config, and frontend setup — there's a couple of details there (specifically around the `protocol_version` setting) worth getting right the first time.

## Documentation

| | |
|---|---|
| **[Installation](docs/installation.md)** | Root template, frontend setup, verifying it works |
| **[Configuration](docs/configuration.md)** | Every `inertia.yaml` option, explained |
| **[Props](docs/props.md)** | `lazy()`, `always()`, `defer()`, `merge()`, `deepMerge()` |
| **[Shared data](docs/shared-data.md)** | `share()`, automatic auth data, flash messages |
| **[Redirects](docs/redirects.md)** | `back()`, `redirect()`, `location()`, form validation errors |
| **[Server-side rendering](docs/ssr.md)** | Enabling SSR and what your SSR server needs to return |
| **[Testing](docs/testing.md)** | `InertiaAssertionsTrait` for PHPUnit |
| **[Upgrading from 1.x](docs/upgrading.md)** | The breaking changes in 2.0, and why |

## Contributing

Issues and PRs welcome at [github.com/CreativSpeed/inertia-bundle](https://github.com/CreativSpeed/inertia-bundle).

## License

[MIT](LICENSE).

---

Made by [CreativSpeed](https://github.com/CreativSpeed) · bachir@creativspeed.com

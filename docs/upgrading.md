# Upgrading from 1.x

2.0 is a breaking release. Short version: update your namespace imports, everything else is additive.

## Breaking: namespace corrected

1.x shipped with an inconsistent namespace — `composer.json` and most classes used `Creativspeed\InertiaBundle` (lowercase `s`), while the README's own examples showed `CreativSpeed\InertiaBundle\Service` (uppercase `S`, singular `Service`). Neither matched consistently, so anyone following the README's controller example verbatim got a class-not-found error.

2.0 standardizes on the form the docs always showed:

```diff
- use Creativspeed\InertiaBundle\Services\InertiaInterface;
+ use CreativSpeed\InertiaBundle\Service\InertiaInterface;
```

Update every `use` statement and any `config/bundles.php` entry referencing the old casing. A find-and-replace of `Creativspeed\InertiaBundle\Services` → `CreativSpeed\InertiaBundle\Service` across your codebase covers it.

## New in 2.0 (non-breaking, opt in whenever)

- `merge()` / `deepMerge()` props — previously advertised, not actually implemented.
- `defer()` now actually defers — 1.x resolved deferred props eagerly, identical to a plain prop.
- `back()`, `redirect()`, `location()`, `redirectWithErrors()` on the `Inertia` service — previously shown in the README but not present on the interface or class.
- `encryptHistory()` / `clearHistory()`.
- `root_id` config option, and a per-render `rootView` override on `render()`.
- `InertiaAssertionsTrait` for testing.
- The service now implements `ResetInterface`, for correctness under long-running workers (RoadRunner, FrankenPHP worker mode) — irrelevant under classic PHP-FPM.

## Fixed in 2.0 (behavior changes you shouldn't need to touch, but worth knowing about)

- The version-conflict check (409 + `X-Inertia-Location`) now only applies to `GET` requests. In 1.x it could theoretically fire on a `POST`/`PUT`/`DELETE` with a stale client-side version header, discarding submitted data — this never actually ran in 1.x because of the bug below, but it's worth knowing the behavior changed.
- The 302→303 redirect conversion and the version-conflict check are now both live. In 1.x, the listener method implementing them existed but was never registered — so neither ever ran.
- SSR now actually renders through your root Twig template (doctype, stylesheets, everything) with the SSR body/head spliced in, instead of returning the SSR server's raw body as the entire HTTP response.

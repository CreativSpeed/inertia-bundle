# Configuration

All of it lives in `config/packages/inertia.yaml`. Every key is optional — shown here with its default.

```yaml
inertia:
    version: null
    protocol_version: 3
    root_view: 'app'
    root_id: 'app'
    ssr:
        enabled: false
        url: 'http://127.0.0.1:13714'
```

## `version`

The asset version Inertia uses to detect a new deploy and force a full page reload instead of a stale SPA navigation. Leave it `null` (the default) and it's auto-detected by hashing your Vite manifest:

```
public/build/.vite/manifest.json   (Vite 5+)
public/build/manifest.json         (older layout)
```

If neither file exists (e.g. you haven't run `npm run build` yet, or you're running the Vite dev server), it falls back to the fixed string `'dev'` — deliberately stable, so it doesn't force a reload on every HMR update while you're developing.

Set it explicitly if you'd rather control it yourself:

```yaml
inertia:
    version: '%env(APP_VERSION)%'
```

You can also override it at runtime — `$inertia->version('v2')` — useful if the version needs to depend on something request-specific.

## `protocol_version`

`3` (default) or `2`. Controls which HTML the `inertia()` Twig function renders — see [installation.md](installation.md#3-create-the-root-template) for exactly what each one produces and why it matters. Match this to whatever your installed `@inertiajs/*` frontend package version expects.

## `root_view`

The Twig template Inertia renders into, resolved as `@Inertia/{root_view}.html.twig` — i.e. `templates/inertia/{root_view}.html.twig`. Pass a full path ending in `.html.twig` if you don't want the `@Inertia/` namespace applied.

Override it for a single render without touching the config:

```php
return $inertia->render('Admin/Dashboard', $props, viewData: [], rootView: 'admin');
```

which renders `templates/inertia/admin.html.twig` for that one response — handy if an admin section needs different asset tags or a different `<head>`.

## `root_id`

The `id` of the DOM element Inertia mounts into — must match whatever your frontend's `createInertiaApp({ id: ... })` expects (default `'app'` on both sides, so you only need to touch this if you've changed one of them).

## `ssr`

```yaml
inertia:
    ssr:
        enabled: true
        url: 'http://127.0.0.1:13714'
```

`url` is the address of your running SSR server (typically `@inertiajs/vue3/server` behind a small Node process — the bundle doesn't run this for you). See [ssr.md](ssr.md) for the full contract and failure behavior.

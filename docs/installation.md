# Installation

## 1. Install the bundle

```bash
composer require creativspeed/inertia-bundle
```

Symfony Flex registers it automatically. Without Flex, add it to `config/bundles.php`:

```php
return [
    // ...
    CreativSpeed\InertiaBundle\InertiaBundle::class => ['all' => true],
];
```

## 2. Configure it

Create `config/packages/inertia.yaml`:

```yaml
inertia:
    protocol_version: 3   # see the note below before changing this
    root_view: 'app'
    root_id: 'app'
```

Every option is documented in [configuration.md](configuration.md). You can skip this file entirely and rely on the defaults shown above.

## 3. Create the root template

Create `templates/inertia/app.html.twig`:

```twig
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title inertia>{{ page.props.title ?? 'My App' }}</title>

    {{ vite_entry_link_tags('app') }}
</head>
<body>
    {{ inertia(page, ssrBody ?? null) }}

    {{ vite_entry_script_tags('app') }}
</body>
</html>
```

Swap the two `vite_entry_*` calls for whatever your Vite integration provides — `reprise_entry_link_tags`/`reprise_entry_script_tags` for `@symfony/reprise`, the equivalent from `vite-plugin-symfony`, etc. That part is unrelated to Inertia.

**Use `{{ inertia(page, ssrBody ?? null) }}`, not a hand-written `<div data-page="...">`.** This is the one detail worth being deliberate about:

- `protocol_version: 3` (the default) renders `<script data-page="app" type="application/json">{...}</script><div id="app"></div>` — the current Inertia protocol.
- `protocol_version: 2` renders the older `<div id="app" data-page="...">` with the JSON escaped into the attribute.

If you hand-write the div-attribute form yourself instead of calling `inertia()`, and your frontend's Inertia client version expects the script-tag form (or vice versa), you'll get exactly the kind of "works everywhere except this one thing" bug that's easy to lose an afternoon to — the client silently fails to find the page data instead of erroring clearly. Let the twig function handle it, and change `protocol_version` in one place if you ever need to.

## 4. Set up the frontend

```bash
npm install @inertiajs/vue3 vue
npm install --save-dev @vitejs/plugin-vue vite
```

`assets/app.js`:

```javascript
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.vue', { eager: true });
        return pages[`./pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
});
```

Don't pass a `page` option to `createInertiaApp()` — it reads the page data from the DOM itself (via whichever of the two conventions above `protocol_version` produced), and does so more robustly than re-implementing that yourself.

`assets/pages/Home.vue`:

```vue
<template>
  <div>
    <h1>{{ title }}</h1>
  </div>
</template>

<script setup>
defineProps({ title: String });
</script>
```

## 5. Write a controller

```php
namespace App\Controller;

use CreativSpeed\InertiaBundle\Service\InertiaInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(InertiaInterface $inertia): Response
    {
        return $inertia->render('Home', [
            'title' => 'Hello from Symfony!',
        ]);
    }
}
```

## 6. Verify it

- A normal browser visit to `/` should render the full HTML page with your Vue app mounted.
- A request with `X-Inertia: true` (e.g. `curl -H "X-Inertia: true" http://localhost/`) should get back a JSON body with `component`, `props`, `url`, and `version` keys, and an `X-Inertia: true` response header.

If the JSON response is missing or the page never mounts, it's almost always the root template — double check step 3.

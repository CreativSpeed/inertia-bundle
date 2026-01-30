# Inertia Bundle for Symfony 8

[![Latest Stable Version](https://poser.pugx.org/creativspeed/inertia-bundle/v/stable)](https://packagist.org/packages/creativspeed/inertia-bundle)
[![Total Downloads](https://poser.pugx.org/creativspeed/inertia-bundle/downloads)](https://packagist.org/packages/creativspeed/inertia-bundle)
[![License](https://poser.pugx.org/creativspeed/inertia-bundle/license)](https://packagist.org/packages/creativspeed/inertia-bundle)

Modern [Inertia.js](https://inertiajs.com/) integration for **Symfony 8** and **PHP 8.2+**. Build powerful single-page applications using Vue.js or React without the complexity of building an API.

## ✨ Features

- 🚀 **Modern Symfony 8** - Built with `AbstractBundle` pattern
- 🎯 **Zero Configuration** - Works out of the box
- 🔄 **Automatic Auth Sharing** - User data automatically available in frontend
- ⚡ **Partial Reloads** - Only fetch data you need
- 🎨 **Vue 3 & React Support** - Use your favorite framework
- 🔒 **Security First** - Integrates seamlessly with Symfony Security
- 📦 **PSR-4 Autoloading** - Modern PHP standards
- 🧪 **Type Safe** - Full PHP 8.2+ type hints

## 📋 Requirements

- PHP **8.2**, **8.3**, or **8.4**
- Symfony **8.0+** (also compatible with Symfony 7.0+)
- Composer 2.0+

## 📥 Installation

Install the bundle via Composer:
```bash
composer require creativspeed/inertia-bundle
```

If you're using Symfony Flex, the bundle will be automatically registered. Otherwise, add it manually to `config/bundles.php`:
```php
<?php

return [
    // ...
    CreativSpeed\InertiaBundle\InertiaBundle::class => ['all' => true],
];
```

## ⚙️ Configuration

Create `config/packages/inertia.yaml`:
```yaml
inertia:
    # Asset versioning for cache busting (optional)
    version: '%env(default::APP_VERSION)%'
    
    # Root Twig template (optional, default: 'app')
    root_view: 'app'
    
    # Server-side rendering (optional)
    ssr:
        enabled: false
        url: 'http://127.0.0.1:13714'
```

## 🚀 Quick Start

### 1. Create Root Template

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
    <div id="app" data-page="{{ page|json_encode|e('html_attr') }}"></div>
    
    {{ vite_entry_script_tags('app') }}
</body>
</html>
```

### 2. Setup Frontend (Vue 3 Example)

Install frontend dependencies:
```bash
npm install @inertiajs/vue3 vue
npm install --save-dev @vitejs/plugin-vue vite
```

Create `assets/app.js`:
```javascript
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';

createInertiaApp({
    resolve: name => {
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

### 3. Create Your First Page Component

Create `assets/pages/Home.vue`:
```vue
<template>
    <div>
        <h1>{{ title }}</h1>
        <p>Welcome to Inertia.js with Symfony 8!</p>
    </div>
</template>

<script setup>
defineProps({
    title: String
});
</script>
```

### 4. Create a Controller
```php
<?php

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
            'title' => 'Hello from Symfony!'
        ]);
    }
}
```

## 📖 Usage Examples

### Basic Rendering
```php
return $inertia->render('Dashboard/Index', [
    'user' => $this->getUser(),
    'stats' => ['visits' => 100]
]);
```

### Sharing Data Globally
```php
// In a controller or event listener
$inertia->share('auth', [
    'user' => $this->getUser()
]);
```

### Lazy Props (Partial Reloads)
```php
return $inertia->render('Users/Index', [
    'users' => $userRepository->findAll(),
    // Only loaded when specifically requested
    'stats' => fn() => $this->calculateExpensiveStats()
]);
```

### Redirects
```php
// Redirect back
return $inertia->back();

// Redirect to URL
return $inertia->redirect('/dashboard');
```

## 🏗️ Architecture

This bundle uses Symfony's modern `AbstractBundle` pattern (Symfony 6.1+) for cleaner, more maintainable code:

- ✅ No separate Extension class needed
- ✅ No separate Configuration class needed
- ✅ Everything configured in the bundle class
- ✅ Auto-wiring and auto-configuration enabled

## 🔧 Advanced Configuration

### Multiple Root Views
```yaml
inertia:
    root_view: 'app'  # Default for most pages
```

Then override per-request:
```php
return $inertia->render('Admin/Dashboard', $props, [
    'root_view' => 'admin'  // Use templates/inertia/admin.html.twig
]);
```

### Asset Versioning
```yaml
inertia:
    version: '1.0.0'  # Static version
    # OR
    version: '%env(APP_VERSION)%'  # From environment
```

### Server-Side Rendering
```yaml
inertia:
    ssr:
        enabled: true
        url: 'http://127.0.0.1:13714'
```

## 🧪 Testing
```bash
composer test
```

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This bundle is open-sourced software licensed under the [MIT license](LICENSE).

## 🔗 Links

- [Inertia.js Documentation](https://inertiajs.com/)
- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [GitHub Repository](https://github.com/CreativSpeed/inertia-bundle)
- [Report Issues](https://github.com/CreativSpeed/inertia-bundle/issues)

## 💬 Support

- 🐛 **Bug Reports**: [GitHub Issues](https://github.com/CreativSpeed/inertia-bundle/issues)
- 💡 **Feature Requests**: [GitHub Issues](https://github.com/CreativSpeed/inertia-bundle/issues)
- 📧 **Email**: bachir@creativspeed.com

## ⭐ Show Your Support

If you find this bundle helpful, please consider giving it a ⭐ on [GitHub](https://github.com/CreativSpeed/inertia-bundle)!

---

**Made with ❤️ by [CreativSpeed](https://github.com/CreativSpeed)**

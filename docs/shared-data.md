# Shared Data

Data shared once is included on every Inertia response for the rest of the request — no need to repeat it in every `render()` call.

```php
$inertia->share('appName', 'My App');
$inertia->share([
    'appName' => 'My App',
    'locale' => $request->getLocale(),
]);
```

Call it from a controller, or from your own event listener on `kernel.controller` if it needs to apply everywhere.

Anything sharable as a prop can be shared, including `lazy()`/`always()`/`defer()`/`merge()` wrapped values.

## Auth data (automatic)

Every response already includes an `auth` prop — you don't need to share it yourself:

```json
{ "auth": { "user": { "id": 1, "identifier": "ada@example.com" } } }
```

With no user logged in, it's `{ "auth": { "user": null } }`.

By default this is a minimal shape (`id` + the Symfony user identifier). To control exactly what gets sent, implement `InertiaAuthUserInterface` on your User entity:

```php
use CreativSpeed\InertiaBundle\Contracts\InertiaAuthUserInterface;

class User implements UserInterface, InertiaAuthUserInterface
{
    public function getInertiaAuthData(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $this->getRoles(),
        ];
    }
}
```

If you need to override it for one specific response — impersonation, a stripped-down public view, whatever — call `$inertia->share('auth', [...])` yourself in that controller; a manual share always wins over the automatic one.

## Flash messages & validation errors

Every flash message type set on the session is shared automatically as a `flash` prop, and anything flashed under the `errors` key is promoted to its own top-level `errors` prop (the convention Inertia's client-side form helpers expect):

```php
$this->addFlash('success', 'Saved!');
$this->addFlash('warning', 'Your session expires soon.');
// -> shared as: "flash": { "success": [...], "warning": [...] }
```

See [redirects.md](redirects.md) for `redirectWithErrors()`, which is how `errors` normally gets populated.

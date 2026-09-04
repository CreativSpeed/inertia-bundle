# Redirects

## The automatic part

Any redirect your controller returns — `$this->redirectToRoute(...)`, a plain `RedirectResponse`, whatever — gets its status code corrected automatically on an Inertia request: a `302` following a `PUT`, `PATCH`, or `DELETE` is rewritten to `303`. You don't need to think about this; it's handled by the bundle's response listener. It matters because a `302` after those methods gets re-sent by the browser with the *original* method instead of downgrading to `GET`, which is essentially never what you want after e.g. a delete action.

## `back()`

```php
return $inertia->back();               // redirects to the referer
return $inertia->back('/dashboard');   // ...or this, if there's no referer to fall back to
```

## `redirect()`

```php
return $inertia->redirect('/dashboard');
```

A thin wrapper around `RedirectResponse` — exists mainly so it reads consistently alongside `back()`/`location()`. A plain `RedirectResponse` works exactly as well; the 302→303 conversion above applies either way.

## `location()` — leaving the SPA entirely

```php
return $inertia->location('https://checkout.example.com/session/abc');
```

Use this instead of a normal redirect when you need the browser to do a *real*, full navigation rather than an Inertia-managed one — an external URL, a different app on the same domain, anything outside the SPA. On an Inertia (XHR) request this sends a `409` with an `X-Inertia-Location` header, which the client interprets as "do a full `window.location` assignment"; on a plain request it's just a normal redirect.

## Form validation errors

```php
$form = $this->createForm(ContactType::class);
$form->handleRequest($request);

if ($form->isSubmitted() && !$form->isValid()) {
    return $inertia->redirectWithErrors($form);
}
```

Flattens the form's errors into `field => message` pairs, flashes them, and redirects back (303) to wherever the form was submitted from. The next response then carries them as the `errors` prop — see [shared-data.md](shared-data.md#flash-messages--validation-errors).

You can pass a plain array instead of a form if the errors didn't come from a Symfony Form:

```php
return $inertia->redirectWithErrors(['email' => 'That email is already taken.']);
```

Both accept an optional second argument to redirect somewhere other than the referer:

```php
return $inertia->redirectWithErrors($form, '/contact');
```

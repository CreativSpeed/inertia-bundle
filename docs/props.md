# Props

Plain values in `render()` are always included. Wrap a value in one of the helpers below to change when it's included or how the client handles it.

```php
return $inertia->render('Dashboard', [
    'user' => $this->getUser(),                                  // always sent
    'stats' => $inertia->lazy(fn () => $this->expensiveStats()),  // only on request
]);
```

## `lazy()` — only on an explicit partial reload

```php
'stats' => $inertia->lazy(fn () => $this->calculateExpensiveStats()),
```

Excluded from the initial page load. Included only when the client explicitly asks for it by name via a partial reload (`router.reload({ only: ['stats'] })` on the frontend). The callback isn't even invoked unless it's actually requested — so this is also how you avoid running expensive queries on every navigation.

## `always()` — never excluded

```php
'notificationCount' => $inertia->always(fn () => $this->notifications->unreadCount()),
```

The opposite of lazy: included on the initial load *and* on every partial reload, even one whose `only` list doesn't mention it or whose `except` list does. Use it for things that should stay current regardless of what else the client is (or isn't) asking for.

## `defer()` — loaded after the initial render

```php
'analytics' => $inertia->defer(fn () => $this->buildAnalyticsReport()),

// Grouped: props sharing a group name are fetched together, in one follow-up request
'sidebar' => $inertia->defer(fn () => $this->sidebarData(), group: 'secondary'),
'widgets' => $inertia->defer(fn () => $this->widgetData(), group: 'secondary'),
```

Excluded from the initial payload entirely (not even sent as `null`); the client fetches it in a follow-up request right after the page mounts, so the initial paint isn't blocked on it. Pair with the frontend's `<Deferred>` component. Props with the same `group` are fetched together in a single request instead of one each.

## `merge()` / `deepMerge()` — client merges instead of replacing

```php
'results' => $inertia->merge($searchResults),        // shallow merge (e.g. appending a flat list)
'filters' => $inertia->deepMerge($nestedFilterTree),  // recursive merge
```

Tells the client to merge this value into what it already has instead of replacing it outright — the standard use case is an infinite-scroll list where each page of results should append rather than replace. The server still just returns the current value normally; whether the *client* actually merges or resets is driven by the client's own state (it can send an `X-Inertia-Reset` header to force a fresh replace, e.g. when filters change).

Combine with `defer()` fluently:

```php
$inertia->defer(fn () => $this->nextPage(), group: 'feed')->merged(),
$inertia->defer(fn () => $this->filterTree(), group: 'feed')->deepMerged(),
```

Both accept an optional array of keys to match array items on when merging lists of objects, to avoid duplicate entries:

```php
$inertia->merge($items, matchOn: ['id']),
```

## History state

Not props, but set alongside them on the `Inertia` service — relevant if a page carries data you don't want recoverable from the browser's back/forward cache:

```php
$inertia->encryptHistory();  // encrypt this page's history state client-side
$inertia->clearHistory();    // instruct the client to clear all encrypted history (e.g. on logout)

return $inertia->render('Account/Billing', $props);
```

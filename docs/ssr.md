# Server-Side Rendering

```yaml
inertia:
    ssr:
        enabled: true
        url: 'http://127.0.0.1:13714'
```

This makes `render()` attempt SSR before falling back to client-side rendering — it doesn't run an SSR server for you. You need a separate Node process running your frontend's SSR entry point (for Vue: `@inertiajs/vue3/server`, built via your framework's `ssr.js`/`ssr.ts` entry).

## The contract

On every `render()` call, if `ssr.enabled` is true, the bundle sends:

```
POST {ssr.url}/render
Content-Type: application/json

{ "component": "...", "props": {...}, "url": "...", "version": "..." }
```

and expects back:

```json
{
    "body": "<div id=\"app\" data-server-rendered=\"true\">...</div>",
    "head": ["<title>Page Title</title>", "<meta name=\"description\" content=\"...\">"]
}
```

`body` is spliced directly into your root template in place of the usual empty mount element — make sure your template calls `{{ inertia(page, ssrBody ?? null) }}`, not just `{{ inertia(page) }}` (see [installation.md](installation.md)). `head` is available separately via `{{ inertiaHead(ssrHead ?? []) }}`, typically placed in your `<head>`.

## Failure behavior

SSR is a progressive enhancement, never a hard dependency. If the SSR server is down, times out (1 second), or returns anything other than a `200`, `render()` silently falls back to normal client-side rendering — the page still works, just without pre-rendered HTML. This is deliberate: an SSR outage should degrade your app, not take it down.

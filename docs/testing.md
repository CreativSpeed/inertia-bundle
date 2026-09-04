# Testing

`InertiaAssertionsTrait` adds a handful of assertions for Inertia responses to any PHPUnit test case (typically a `WebTestCase`).

```php
use CreativSpeed\InertiaBundle\Testing\InertiaAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardControllerTest extends WebTestCase
{
    use InertiaAssertionsTrait;

    public function testDashboardRenders(): void
    {
        $client = static::createClient();
        $client->request('GET', '/dashboard', server: ['HTTP_X-Inertia' => 'true']);

        $response = $client->getResponse();

        $this->assertInertiaComponent($response, 'Dashboard');
        $this->assertInertiaProp($response, 'user.name', 'Ada Lovelace');
    }

    public function testInvalidFormShowsErrors(): void
    {
        $client = static::createClient();
        $client->request('POST', '/contact', ['email' => 'not-an-email'], server: ['HTTP_X-Inertia' => 'true']);

        $this->assertInertiaHasErrors($client->getResponse(), ['email']);
    }
}
```

## Available assertions

| Method | Checks |
|---|---|
| `assertInertiaComponent($response, string $component)` | The response's `component` matches. |
| `assertInertiaProp($response, string $path, mixed $expected)` | A prop equals a value. `$path` supports dot notation (`'user.name'`) for nested props. |
| `assertInertiaPropMissing($response, string $path)` | A prop is absent — useful for confirming a `lazy()`/`defer()`-wrapped prop was correctly excluded. |
| `assertInertiaHasErrors($response, array $keys = [])` | An `errors` prop is present, optionally checking specific field keys exist within it. |

All of them require the response to actually be an Inertia response (the `X-Inertia` header present) — send your test request with `server: ['HTTP_X-Inertia' => 'true']` as shown above, or they'll fail with a clear message telling you that's missing rather than a confusing JSON-decode error.

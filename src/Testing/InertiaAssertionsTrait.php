<?php

namespace CreativSpeed\InertiaBundle\Testing;

use Symfony\Component\HttpFoundation\Response;

/**
 * Add to a WebTestCase for Inertia-aware assertions:
 *
 *   $client->request('GET', '/dashboard', server: ['HTTP_X-Inertia' => 'true']);
 *   $this->assertInertiaComponent($client->getResponse(), 'Dashboard');
 *   $this->assertInertiaProp($client->getResponse(), 'user.name', 'Ada');
 *   $this->assertInertiaHasErrors($client->getResponse(), ['email']);
 */
trait InertiaAssertionsTrait
{
    private function decodeInertiaResponse(Response $response): array
    {
        self::assertTrue(
            $response->headers->has('X-Inertia'),
            'Expected an Inertia response (missing the X-Inertia header) — did the request send the X-Inertia header?',
        );

        $data = json_decode($response->getContent() ?: '', true, flags: JSON_THROW_ON_ERROR);

        self::assertIsArray($data);
        self::assertArrayHasKey('component', $data);
        self::assertArrayHasKey('props', $data);

        return $data;
    }

    protected function assertInertiaComponent(Response $response, string $component): void
    {
        $page = $this->decodeInertiaResponse($response);

        self::assertSame($component, $page['component']);
    }

    protected function assertInertiaProp(Response $response, string $path, mixed $expected): void
    {
        $page = $this->decodeInertiaResponse($response);

        self::assertSame($expected, $this->getInertiaPropByPath($page['props'], $path));
    }

    protected function assertInertiaPropMissing(Response $response, string $path): void
    {
        $page = $this->decodeInertiaResponse($response);

        try {
            $this->getInertiaPropByPath($page['props'], $path);
        } catch (\OutOfBoundsException) {
            return;
        }

        self::fail(sprintf('Expected prop "%s" to be missing, but it was present.', $path));
    }

    /** @param string[] $keys */
    protected function assertInertiaHasErrors(Response $response, array $keys = []): void
    {
        $page = $this->decodeInertiaResponse($response);

        self::assertArrayHasKey('errors', $page['props'], 'Expected an "errors" prop, none was present.');

        foreach ($keys as $key) {
            self::assertArrayHasKey($key, $page['props']['errors'], sprintf('Expected an error for "%s".', $key));
        }
    }

    private function getInertiaPropByPath(array $props, string $path): mixed
    {
        $value = $props;

        foreach (explode('.', $path) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                throw new \OutOfBoundsException(sprintf('Prop path "%s" does not exist.', $path));
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

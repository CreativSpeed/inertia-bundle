<?php

namespace Creativspeed\InertiaBundle\Test;

use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait InertiaTestTrait
{
    public function assertInertia(Response $response, string $component = null, array $props = []): void
    {
        Assert::assertInstanceOf(JsonResponse::class, $response, 'Response is not an Inertia JSON response (Check if X-Inertia header was sent in request).');

        $content = json_decode($response->getContent(), true);

        Assert::assertArrayHasKey('component', $content);
        Assert::assertArrayHasKey('props', $content);
        Assert::assertArrayHasKey('url', $content);

        if ($component) {
            Assert::assertEquals($component, $content['component']);
        }

        foreach ($props as $key => $value) {
            Assert::assertArrayHasKey($key, $content['props']);
            Assert::assertEquals($value, $content['props'][$key]);
        }
    }
}

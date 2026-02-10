<?php

namespace Creativspeed\InertiaBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class InertiaExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('inertia', [$this, 'inertia'], ['is_safe' => ['html']]),
            new TwigFunction('inertiaHead', [$this, 'inertiaHead'], ['is_safe' => ['html']]),
        ];
    }

    public function inertia(array $page): string
    {
        $id = $page['props']['id'] ?? 'app'; // Allow custom ID
        $dataPage = htmlspecialchars(json_encode($page), ENT_QUOTES, 'UTF-8');

        return sprintf('<div id="%s" data-page="%s"></div>', $id, $dataPage);
    }

    public function inertiaHead(array $page): string
    {
        // Inertia V2 often requires no specific head output in the body,
        // but this is useful if you implement the standard <title> tag strategy here later.
        return '';
    }
}

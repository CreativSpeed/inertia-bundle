<?php

namespace CreativSpeed\InertiaBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class InertiaExtension extends AbstractExtension
{
    public function __construct(
        private readonly int $inertiaVersion = 3,
        private readonly string $rootId = 'app',
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('inertia', [$this, 'inertia'], ['is_safe' => ['html']]),
            new TwigFunction('inertiaHead', [$this, 'inertiaHead'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param array<string, mixed> $page
     * @param string|null $ssrBody Pre-rendered mount element + content from
     *   the SSR server. When present, it's output verbatim instead of the
     *   empty client-hydration placeholder.
     */
    public function inertia(array $page, ?string $ssrBody = null): string
    {
        if ($ssrBody !== null) {
            return $ssrBody;
        }

        $id = htmlspecialchars($this->rootId, ENT_QUOTES, 'UTF-8');

        if ($this->inertiaVersion >= 3) {
            // Inertia v3: raw JSON in its own <script> tag, separate from
            // the (empty, client-hydrated) mount element.
            //
            // Slashes MUST stay escaped — that's PHP's default json_encode
            // behavior, so no JSON_UNESCAPED_SLASHES here. That's what
            // turns a literal "</script>" inside a prop value into
            // "<\/script>", which the HTML parser does NOT recognize as a
            // closing tag, preventing it from prematurely ending the
            // script body. The JSON_HEX_* flags below are defense-in-depth
            // on top of that, escaping <, >, &, and quotes as \uXXXX too.
            $encodedPage = json_encode(
                $page,
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
            );

            return sprintf(
                '<script data-page="%s" type="application/json">%s</script><div id="%s"></div>',
                $id,
                $encodedPage,
                $id,
            );
        }

        // Legacy v2 format: escaped JSON in a data attribute.
        $encodedPage = json_encode($page);
        $escapedPage = htmlspecialchars($encodedPage, ENT_QUOTES, 'UTF-8');

        return sprintf('<div id="%s" data-page="%s"></div>', $id, $escapedPage);
    }

    /**
     * @param array<int, string> $ssrHead Raw <title>/<meta> tag strings
     *   returned by the SSR server. Empty (and this renders nothing) until
     *   SSR is actually enabled and running.
     */
    public function inertiaHead(array $ssrHead = []): string
    {
        return implode("\n", $ssrHead);
    }
}

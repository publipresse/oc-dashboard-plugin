<?php

namespace Publipresse\Dashboard\ReportWidgets;

use DOMDocument;
use DOMElement;
use Publipresse\Dashboard\Classes\WidgetBase;

/**
 * Embeds an arbitrary external page, such as a Matomo statistics widget, from the raw
 * <iframe> embed code supplied by the third-party service.
 */
class Iframe extends WidgetBase
{
    protected $defaultAlias = 'publipresseIframe';

    /**
     * Iframe attributes copied through as-is once sanitized. "src" is handled separately
     * since it needs scheme validation rather than plain string escaping, "height" is driven by
     * the dedicated block-height setting below, and "loading", "referrerpolicy" and "sandbox"
     * get a safe default when the pasted code omits them.
     */
    protected const PASSTHROUGH_ATTRIBUTES = ['width', 'title'];

    public function defineProperties()
    {
        return $this->titleProperty('Statistiques') + $this->iconProperty('ph ph-browser') + [
            'iframeCode' => [
                'title' => 'Code iframe',
                'description' => 'Collez ici le code d\'intégration complet fourni par le prestataire (la balise <iframe>...</iframe>)',
                'type' => 'text',
                'size' => 'large',
                'default' => '',
            ],
            'height' => [
                'title' => 'Hauteur du bloc (px)',
                'type' => 'string',
                'default' => '700',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'Indiquez une hauteur en pixels',
            ],
        ];
    }

    protected function prepareVars(): void
    {
        $iframe = $this->extractIframe((string) $this->property('iframeCode'));

        $this->vars['iframe'] = $iframe;
        $this->vars['host'] = $iframe ? parse_url($iframe['src'], PHP_URL_HOST) : null;
        $this->vars['height'] = max(80, (int) $this->property('height', 700));
    }

    /**
     * extractIframe parses the pasted embed code and rebuilds a safe <iframe> definition from
     * it, keeping only a whitelist of attributes so the pasted markup can't smuggle in a
     * <script> tag, event handlers, or a javascript:/data: URL.
     *
     * @return array{src: string, attributes: array<string, string>, allowfullscreen: bool}|null
     */
    protected function extractIframe(string $code): ?array
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        $previous = libxml_use_internal_errors(true);

        try {
            $dom = new DOMDocument();
            // LIBXML_NONET keeps external entities from being resolved while parsing the fragment.
            $dom->loadHTML(
                '<?xml encoding="utf-8" ?><body>' . $code . '</body>',
                LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
            );
        }
        finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $node = $dom->getElementsByTagName('iframe')->item(0);
        if (!$node instanceof DOMElement) {
            return null;
        }

        $src = $this->safeUrl($node->getAttribute('src'));
        if (!$src) {
            return null;
        }

        $attributes = [];
        foreach (static::PASSTHROUGH_ATTRIBUTES as $name) {
            $value = trim($node->getAttribute($name));
            if ($value !== '') {
                $attributes[$name] = $value;
            }
        }

        if (in_array($node->getAttribute('scrolling'), ['yes', 'no'], true)) {
            $attributes['scrolling'] = $node->getAttribute('scrolling');
        }

        $allow = preg_replace('/[^a-z0-9\-\*\s;\.:\/]/i', '', $node->getAttribute('allow'));
        if ($allow !== '') {
            $attributes['allow'] = $allow;
        }

        $sandbox = preg_replace('/[^a-z\-\s]/i', '', $node->getAttribute('sandbox'));
        // Fall back to safe defaults when the pasted code doesn't define these, since widgets
        // are configured by trusted backend users but the embedded page is third-party.
        $attributes['sandbox'] = $sandbox !== '' ? $sandbox : 'allow-scripts allow-same-origin allow-popups allow-forms';
        $attributes['loading'] = in_array($node->getAttribute('loading'), ['lazy', 'eager'], true)
            ? $node->getAttribute('loading')
            : 'lazy';
        $referrerPolicy = trim($node->getAttribute('referrerpolicy'));
        $attributes['referrerpolicy'] = $referrerPolicy !== '' ? $referrerPolicy : 'no-referrer';

        return [
            'src' => $src,
            'attributes' => $attributes,
            'allowfullscreen' => $node->hasAttribute('allowfullscreen'),
        ];
    }
}

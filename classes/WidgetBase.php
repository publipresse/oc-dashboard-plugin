<?php

namespace Publipresse\Dashboard\Classes;

use Dashboard\Classes\ReportWidgetBase;
use Exception;
use Media\Classes\MediaLibrary;

/**
 * Base class shared by all Publipresse dashboard report widgets.
 */
abstract class WidgetBase extends ReportWidgetBase
{
    /**
     * Name of the partial rendered by this widget.
     */
    protected $widgetPartial = 'widget';

    public function render()
    {
        try {
            $this->prepareVars();
        }
        catch (Exception $ex) {
            $this->vars = ['error' => $ex->getMessage()];
        }

        return $this->makePartial($this->widgetPartial);
    }

    protected function prepareVars(): void
    {
    }

    /**
     * Renders the stylesheet shared by every Publipresse dashboard widget. Static report widget
     * markup is injected through AJAX, so regular asset registration is not applied.
     */
    public function styles(): string
    {
        return $this->makePartial('$/publipresse/dashboard/partials/_styles.htm');
    }

    /**
     * Returns the URL only when it uses a browser safe scheme, preventing
     * javascript: or data: payloads from being injected through the inspector.
     */
    protected function safeUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        return filter_var($url, FILTER_VALIDATE_URL) ?: null;
    }

    protected function safeEmail(?string $email): ?string
    {
        $email = trim((string) $email);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ?: null;
    }

    /**
     * Resolves a media library path or an absolute URL to a displayable image URL.
     */
    protected function mediaUrl(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $this->safeUrl($path);
        }

        return MediaLibrary::url($path);
    }

    /**
     * Common title property shared by every widget. The dashboard renders it above the widget body.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function titleProperty(string $default): array
    {
        return [
            'title' => [
                'title' => 'Titre',
                'default' => $default,
                'type' => 'string',
                'validationPattern' => '^.+$',
                'validationMessage' => 'Le titre est obligatoire',
            ],
        ];
    }

    /**
     * widgetTitle returns the configured title, used as the header eyebrow so it lines up
     * with the native dashboard "indicator" widgets (icon + title + value).
     */
    protected function widgetTitle(): string
    {
        return trim((string) $this->property('title'));
    }

    /**
     * iconOptions lists the Phosphor icon classes offered by the picker, curated for the
     * scenarios covered by this plugin's widgets.
     *
     * @return array<string, string>
     */
    protected static function iconOptions(): array
    {
        return [
            'ph ph-user' => 'Utilisateur',
            'ph ph-users' => 'Utilisateurs',
            'ph ph-headset' => 'Casque support',
            'ph ph-chat-circle-text' => 'Discussion',
            'ph ph-phone' => 'Téléphone',
            'ph ph-envelope' => 'Email',
            'ph ph-globe' => 'Globe',
            'ph ph-globe-hemisphere-west' => 'Globe (domaines)',
            'ph ph-calendar' => 'Calendrier',
            'ph ph-calendar-check' => 'Calendrier (validé)',
            'ph ph-chart-line-up' => 'Graphique (hausse)',
            'ph ph-gauge' => 'Jauge',
            'ph ph-rss' => 'Flux RSS',
            'ph ph-browser' => 'Navigateur',
            'ph ph-app-window' => 'Fenêtre',
            'ph ph-chart-bar' => 'Statistiques',
            'ph ph-shield-check' => 'Sécurité',
            'ph ph-power' => 'Statut',
            'ph ph-wrench' => 'Maintenance',
            'ph ph-megaphone' => 'Annonce',
            'ph ph-star' => 'Étoile',
        ];
    }

    /**
     * iconProperty defines the icon picker property, matching the dropdown + "useValuesAsIcons"
     * pattern used by October's own indicator widget so the picker UI is identical.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function iconProperty(string $default): array
    {
        return [
            'icon' => [
                'title' => 'Icône',
                'type' => 'dropdown',
                'default' => $default,
                'options' => static::iconOptions(),
                'useValuesAsIcons' => true,
                'validation' => [
                    'required' => [
                        'message' => 'Veuillez choisir une icône',
                    ],
                ],
            ],
        ];
    }

    /**
     * badgeIcon renders the configured Phosphor icon, sized to match October's own
     * indicator widget icon (40x40 badge, 32px glyph).
     */
    protected function badgeIcon(string $default): string
    {
        $iconClass = trim((string) $this->property('icon', $default)) ?: $default;

        return '<i class="' . e($iconClass) . '"></i>';
    }

    /**
     * icon renders one of the inline pictograms used for inline contact details, all using
     * currentColor so they follow the light/dark backend theme automatically.
     */
    protected function icon(string $name): string
    {
        $icons = [
            'email' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 6 8 7 8-7"/>',
            'phone' => '<path d="M6 3h3l1.5 4.5L8.5 9a10 10 0 0 0 6.5 6.5l1.5-2L21 15v3a2 2 0 0 1-2 2C11.5 20 4 12.5 4 5a2 2 0 0 1 2-2Z"/>',
            'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/>',
            'building' => '<path d="M5 21V5a1 1 0 0 1 1-1h7a1 1 0 0 1 1 1v16"/><path d="M14 9h4a1 1 0 0 1 1 1v11"/>'
                . '<path d="M9 8h.01M9 12h.01M9 16h.01"/><path d="M3 21h18"/>',
        ];

        $body = $icons[$name] ?? $icons['email'];

        return '<svg class="pp-dash-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
            . 'stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            . $body . '</svg>';
    }

    /**
     * scoreLevel resolves the color and qualitative label for a 0-100 score.
     *
     * @return array{color: string, label: string}
     */
    protected function scoreLevel(float $score): array
    {
        if ($score >= 90) {
            return ['color' => '#27ae60', 'label' => 'Excellent'];
        }

        if ($score >= 70) {
            return ['color' => '#7cb518', 'label' => 'Bon'];
        }

        if ($score >= 50) {
            return ['color' => '#e67e22', 'label' => 'Moyen'];
        }

        return ['color' => '#e74c3c', 'label' => 'À améliorer'];
    }

    /**
     * scoreText formats a 0-100 score for display, trimming trailing zeroes
     * (e.g. "94" or "87,5").
     */
    protected function scoreText(float $score): string
    {
        return rtrim(rtrim(number_format($score, 1, ',', ''), '0'), ',');
    }

    /**
     * scoreBar renders a compact horizontal progress bar for a 0-100 score.
     */
    protected function scoreBar(?float $score): string
    {
        if ($score === null) {
            return '';
        }

        $score = max(0, min(100, $score));
        ['color' => $color] = $this->scoreLevel($score);

        return '<div class="pp-dash-bar">'
            . '<div class="pp-dash-bar__track">'
            . '<div class="pp-dash-bar__value" style="width:' . $score . '%;background:' . e($color) . '"></div>'
            . '</div>'
            . '</div>';
    }
}

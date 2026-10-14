<?php

namespace Publipresse\Dashboard\ReportWidgets;

use Publipresse\Dashboard\Classes\WidgetBase;

/**
 * Displays the domain manager contact, the registrar and the purchased domain names.
 */
class SiteInfo extends WidgetBase
{
    protected $defaultAlias = 'publipresseSiteInfo';

    public function defineProperties()
    {
        return $this->titleProperty('Gestionnaire des domaines') + $this->iconProperty('ph ph-globe-hemisphere-west') + [
            'managerName' => [
                'title' => 'Gestionnaire du domaine',
                'type' => 'string',
            ],
            'registrar' => [
                'title' => 'Registrar',
                'description' => 'Bureau d\'enregistrement des domaines (OVHcloud, Gandi, IONOS...)',
                'type' => 'string',
            ],
            'managerEmail' => [
                'title' => 'Email du gestionnaire',
                'type' => 'string',
            ],
            'managerPhone' => [
                'title' => 'Téléphone du gestionnaire',
                'type' => 'string',
            ],
            'domains' => [
                'title' => 'Domaines achetés',
                'description' => 'Un domaine par ligne',
                'type' => 'text',
                'size' => 'medium',
            ],
        ];
    }

    protected function prepareVars(): void
    {
        $this->vars['managerName'] = trim((string) $this->property('managerName'));
        $this->vars['managerEmail'] = $this->safeEmail($this->property('managerEmail'));
        $this->vars['managerPhone'] = $this->formatPhone($this->property('managerPhone'));
        $this->vars['registrar'] = trim((string) $this->property('registrar'));
        $this->vars['domains'] = $this->normalizeDomains($this->property('domains'));
    }

    /**
     * normalizeDomains turns the one-per-line list into chips, linking each one to its site
     * only when it is a bare host name we can safely prefix with https://.
     *
     * @return array<int, array{label: string, url: ?string}>
     */
    protected function normalizeDomains($domains): array
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $domains) ?: [];
        $lines = array_filter(array_map('trim', $lines), fn ($line) => $line !== '');

        return array_values(array_map(fn ($domain) => [
            'label' => $domain,
            'url' => preg_match('/^(?=.{1,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i', $domain)
                ? 'https://' . $domain
                : null,
        ], $lines));
    }
}

<?php

namespace Publipresse\Dashboard\ReportWidgets;

use Publipresse\Dashboard\Classes\WidgetBase;

/**
 * Displays the domain manager contact and the purchased domain names.
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
                'default' => '',
            ],
            'managerCompany' => [
                'title' => 'Société du gestionnaire',
                'type' => 'string',
                'default' => '',
            ],
            'managerEmail' => [
                'title' => 'Email du gestionnaire',
                'type' => 'string',
                'default' => '',
            ],
            'managerPhone' => [
                'title' => 'Téléphone du gestionnaire',
                'type' => 'string',
                'default' => '',
            ],
            'domains' => [
                'title' => 'Domaines achetés',
                'description' => 'Un domaine par ligne',
                'type' => 'text',
                'size' => 'medium',
                'default' => '',
            ],
        ];
    }

    protected function prepareVars(): void
    {
        $this->vars['managerName'] = trim((string) $this->property('managerName'));
        $this->vars['managerCompany'] = trim((string) $this->property('managerCompany'));
        $this->vars['managerEmail'] = $this->safeEmail($this->property('managerEmail'));
        $this->vars['managerPhone'] = trim((string) $this->property('managerPhone'));
        $this->vars['domains'] = $this->normalizeDomains($this->property('domains'));
    }

    /**
     * @return array<int, string>
     */
    protected function normalizeDomains($domains): array
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $domains) ?: [];

        return array_values(array_filter(array_map('trim', $lines), fn ($line) => $line !== ''));
    }
}

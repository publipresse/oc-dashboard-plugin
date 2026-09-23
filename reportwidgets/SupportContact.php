<?php

namespace Publipresse\Dashboard\ReportWidgets;

use Publipresse\Dashboard\Classes\WidgetBase;

/**
 * Displays the technical support contact.
 */
class SupportContact extends WidgetBase
{
    protected $defaultAlias = 'publipresseSupportContact';

    public function defineProperties()
    {
        return $this->titleProperty('Support technique') + $this->iconProperty('ph ph-headset') + [
            'supportEmail' => [
                'title' => 'Email',
                'type' => 'string',
                'default' => 'support@publipresse.fr',
            ],
            'supportPhone' => [
                'title' => 'Téléphone',
                'type' => 'string',
                'default' => '03 81 67 90 80',
            ],
            'driveUrl' => [
                'title' => 'Lien Google Drive',
                'description' => 'Lien vers le drive photos du client (http/https)',
                'type' => 'string',
                'default' => '',
            ],
        ];
    }

    protected function prepareVars(): void
    {
        $this->vars['supportEmail'] = $this->safeEmail($this->property('supportEmail'));
        $this->vars['supportPhone'] = trim((string) $this->property('supportPhone'));
        $this->vars['driveUrl'] = $this->safeUrl($this->property('driveUrl'));
    }
}

<?php

namespace Publipresse\Dashboard\ReportWidgets;

use Publipresse\Dashboard\Classes\SalesManagerRepository;
use Publipresse\Dashboard\Classes\WidgetBase;

/**
 * Displays the client's Publipresse contacts: the account manager, picked from the corporate
 * sales managers directory, the technical support line and the link to the client's photo Drive.
 */
class AccountManager extends WidgetBase
{
    protected $defaultAlias = 'publipresseAccountManager';

    public function defineProperties()
    {
        return $this->titleProperty('Votre interlocuteur') + [
            'managerId' => [
                'title' => 'Commercial',
                'description' => 'Choisissez votre interlocuteur dans l\'annuaire Publipresse',
                'type' => 'dropdown',
                'options' => SalesManagerRepository::options(),
            ],
            'supportEmail' => [
                'title' => 'Email du support technique',
                'type' => 'string',
                'default' => 'support@publipresse.fr',
            ],
            'supportPhone' => [
                'title' => 'Téléphone du support technique',
                'type' => 'string',
                'default' => '03 81 67 90 80',
            ],
            'driveUrl' => [
                'title' => 'Lien Google Drive (optionnel)',
                'description' => 'Lien vers le drive photos du client (http/https). Laissez vide pour masquer le Drive photos',
                'type' => 'string',
            ],
        ];
    }

    protected function prepareVars(): void
    {
        $managerId = trim((string) $this->property('managerId'));
        $manager = $managerId !== '' ? SalesManagerRepository::find($managerId) : null;

        $this->vars['managerName'] = $manager['name'] ?? '';
        $this->vars['managerPhoto'] = $manager ? $this->mediaUrl($manager['photo']) : null;
        $this->vars['managerEmail'] = $manager ? $this->safeEmail($manager['email']) : null;
        $this->vars['managerPhone'] = $this->formatPhone($manager['phone'] ?? '');
        $this->vars['supportEmail'] = $this->safeEmail($this->property('supportEmail'));
        $this->vars['supportPhone'] = $this->formatPhone($this->property('supportPhone'));
        $this->vars['driveUrl'] = $this->safeUrl($this->property('driveUrl'));
    }
}

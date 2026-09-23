<?php

namespace Publipresse\Dashboard\ReportWidgets;

use Publipresse\Dashboard\Classes\SalesManagerRepository;
use Publipresse\Dashboard\Classes\WidgetBase;

/**
 * Displays the account manager (commercial contact) details, picked from the
 * corporate sales managers directory.
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
                'default' => '',
                'options' => SalesManagerRepository::options(),
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
        $this->vars['managerPhone'] = $manager['phone'] ?? '';
    }
}

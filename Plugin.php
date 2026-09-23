<?php

namespace Publipresse\Dashboard;

use System\Classes\PluginBase;

use Publipresse\Dashboard\ReportWidgets\AccountManager;
use Publipresse\Dashboard\ReportWidgets\Breves;
use Publipresse\Dashboard\ReportWidgets\Iframe;
use Publipresse\Dashboard\ReportWidgets\LaunchDate;
use Publipresse\Dashboard\ReportWidgets\SiteInfo;
use Publipresse\Dashboard\ReportWidgets\SupportContact;

class Plugin extends PluginBase
{
    public function pluginDetails(): array
    {
        return [
            'name' => 'Publipresse Dashboard',
            'description' => 'Modules personnalisables pour le tableau de bord du back-office',
            'author' => 'Publipresse',
            'icon' => 'icon-dashboard',
        ];
    }

    public function registerReportWidgets(): array
    {
        return [
            AccountManager::class => [
                'label' => 'Contact commercial',
                'context' => 'dashboard',
                'group' => 'Publipresse',
            ],
            SupportContact::class => [
                'label' => 'Support technique',
                'context' => 'dashboard',
                'group' => 'Publipresse',
            ],
            Breves::class => [
                'label' => 'Brèves',
                'context' => 'dashboard',
                'group' => 'Publipresse',
            ],
            SiteInfo::class => [
                'label' => 'Gestionnaire des domaines',
                'context' => 'dashboard',
                'group' => 'Publipresse',
            ],
            LaunchDate::class => [
                'label' => 'Statut du site',
                'context' => 'dashboard',
                'group' => 'Publipresse',
            ],
            Iframe::class => [
                'label' => 'Iframe',
                'context' => 'dashboard',
                'group' => 'Publipresse',
            ],
        ];
    }

    public function registerPermissions(): array
    {
        return [
            'publipresse.dashboard.manage_widgets' => [
                'tab' => 'Publipresse Dashboard',
                'label' => 'Gérer les modules du tableau de bord',
            ],
        ];
    }
}

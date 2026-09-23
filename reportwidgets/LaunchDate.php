<?php

namespace Publipresse\Dashboard\ReportWidgets;

use Backend;
use Carbon\Carbon;
use Cms\Models\MaintenanceSetting;
use Publipresse\Dashboard\Classes\WidgetBase;
use Throwable;

/**
 * Displays the website's go live date, entered once when configuring the widget, along with
 * the current maintenance status, a link to the maintenance mode settings page, and the SE
 * Ranking audit score captured for the site.
 */
class LaunchDate extends WidgetBase
{
    protected $defaultAlias = 'publipresseLaunchDate';

    public function defineProperties()
    {
        return $this->titleProperty('Statut') + [
            'launchDate' => [
                'title' => 'Date de mise en ligne',
                'description' => 'Format JJ/MM/AAAA',
                'type' => 'string',
                'default' => '',
                'validationPattern' => '^$|^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/[0-9]{4}$',
                'validationMessage' => 'Utilisez le format JJ/MM/AAAA',
            ],
            'score' => [
                'title' => 'Score SE Ranking / 100',
                'type' => 'string',
                'default' => '',
                'validationPattern' => '^$|^(100|[0-9]{1,2})$',
                'validationMessage' => 'Indiquez un score entre 0 et 100',
            ],
            'reportUrl' => [
                'title' => 'Lien vers le rapport SE Ranking',
                'description' => 'URL du rapport SE Ranking partagé (http/https)',
                'type' => 'string',
                'default' => '',
            ],
        ];
    }

    protected function prepareVars(): void
    {
        $this->vars['launchDate'] = $this->formatDate($this->property('launchDate'));
        // The CMS's own maintenance mode (toggled from the settings page we link to below) is
        // driven by MaintenanceSetting, not by Laravel's artisan up/down file lock. We read the
        // raw setting rather than MaintenanceSetting::isEnabled(), which always reports "false"
        // for the current backend user once they hold the "general.view_offline" permission
        // (true for superusers) — appropriate for the front-end wall, wrong for this status read.
        $this->vars['isDown'] = (bool) MaintenanceSetting::get('is_enabled', false);
        $this->vars['maintenanceSettingsUrl'] = Backend::url('system/settings/update/october/cms/maintenance_settings');

        $score = is_numeric($this->property('score')) ? (float) $this->property('score') : null;
        $this->vars['score'] = $score;
        $this->vars['scoreHeadline'] = $score !== null
            ? $this->scoreText($score) . ' - ' . $this->scoreLevel($score)['label']
            : null;
        $this->vars['bar'] = $this->scoreBar($score);
        $this->vars['reportUrl'] = $this->safeUrl($this->property('reportUrl'));
    }

    protected function formatDate($date): ?string
    {
        $date = trim((string) $date);
        if ($date === '') {
            return null;
        }

        // Accept both the documented dd/mm/yyyy input and an ISO yyyy-mm-dd value, since the
        // latter can come from a date value entered/pasted in that shape.
        foreach (['d/m/Y', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat($format, $date)->format('d/m/Y');
            }
            catch (Throwable $ex) {
                continue;
            }
        }

        return null;
    }
}

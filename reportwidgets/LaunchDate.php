<?php

namespace Publipresse\Dashboard\ReportWidgets;

use Backend;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Cms\Models\MaintenanceSetting;
use Publipresse\Dashboard\Classes\WidgetBase;
use Throwable;
use Url;

/**
 * Displays the website's live status along with the go live date, entered once when
 * configuring the widget, a link to the maintenance mode settings page, and the SE Ranking
 * audit score captured for the site.
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
                'validationPattern' => '^$|^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/[0-9]{4}$',
                'validationMessage' => 'Utilisez le format JJ/MM/AAAA',
            ],
            'score' => [
                'title' => 'Score SE Ranking / 100',
                'type' => 'string',
                'validationPattern' => '^$|^(100|[0-9]{1,2})$',
                'validationMessage' => 'Indiquez un score entre 0 et 100',
            ],
            'reportUrl' => [
                'title' => 'Lien vers le rapport SE Ranking',
                'description' => 'URL du rapport SE Ranking partagé (http/https)',
                'type' => 'string',
            ],
        ];
    }

    protected function prepareVars(): void
    {
        // The CMS's own maintenance mode (toggled from the settings page we link to below) is
        // driven by MaintenanceSetting, not by Laravel's artisan up/down file lock. We read the
        // raw setting rather than MaintenanceSetting::isEnabled(), which always reports "false"
        // for the current backend user once they hold the "general.view_offline" permission
        // (true for superusers) — appropriate for the front-end wall, wrong for this status read.
        $this->vars['isDown'] = (bool) MaintenanceSetting::get('is_enabled', false);
        $this->vars['maintenanceSettingsUrl'] = Backend::url('system/settings/update/october/cms/maintenance_settings');

        $siteUrl = Url::to('/');
        $this->vars['siteUrl'] = $siteUrl;
        $this->vars['siteHost'] = parse_url($siteUrl, PHP_URL_HOST) ?: $siteUrl;

        $launchDate = $this->parseDate($this->property('launchDate'));
        $this->vars['launchDate'] = $launchDate ? $this->longDate($launchDate) : null;
        $this->vars['isLaunched'] = !$launchDate || !$launchDate->isFuture();
        $this->vars['launchAge'] = $launchDate ? $this->relativeAge($launchDate) : null;

        $score = is_numeric($this->property('score')) ? (float) $this->property('score') : null;
        $this->vars['score'] = $score;
        $this->vars['scoreText'] = $score !== null ? $this->scoreText($score) : null;
        $this->vars['scoreLabel'] = $score !== null ? $this->scoreLevel($score)['label'] : null;
        $this->vars['ring'] = $score !== null ? $this->scoreRing($score) : '';
        $this->vars['reportUrl'] = $this->safeUrl($this->property('reportUrl'));
    }

    protected function parseDate($date): ?Carbon
    {
        $date = trim((string) $date);
        if ($date === '') {
            return null;
        }

        // Accept both the documented dd/mm/yyyy input and an ISO yyyy-mm-dd value, since the
        // latter can come from a date value entered/pasted in that shape.
        foreach (['d/m/Y', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat('!' . $format, $date);
            }
            catch (Throwable $ex) {
                continue;
            }
        }

        return null;
    }

    /**
     * longDate spells the date out in French ("1er février 2026"), using the ordinal
     * abbreviation French typography expects for the first day of the month.
     */
    protected function longDate(Carbon $date): string
    {
        $date = $date->copy()->locale('fr');

        return ($date->day === 1 ? '1er' : $date->day) . ' ' . $date->translatedFormat('F Y');
    }

    /**
     * relativeAge describes how long ago the site went live ("Il y a 7 mois", "Il y a 1 an et
     * 2 mois") or how soon it will ("Dans 5 jours"), rounded to the unit that matters at that
     * scale rather than Carbon's default "7 mois et 3 semaines".
     */
    protected function relativeAge(Carbon $date): string
    {
        $today = Carbon::today();
        if ($date->isSameDay($today)) {
            return 'Aujourd\'hui';
        }

        $isOverAYear = $date->diffInMonths($today, true) >= 12;

        $age = $date->copy()->locale('fr')->diffForHumans($today, [
            'syntax' => CarbonInterface::DIFF_RELATIVE_TO_NOW,
            'parts' => $isOverAYear ? 2 : 1,
            'join' => true,
            'skip' => $isOverAYear ? ['week', 'day'] : [],
        ]);

        return mb_strtoupper(mb_substr($age, 0, 1)) . mb_substr($age, 1);
    }
}

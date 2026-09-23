<?php

namespace Publipresse\Dashboard\ReportWidgets;

use ApplicationException;
use Cache;
use Http;
use Publipresse\Dashboard\Classes\WidgetBase;

/**
 * Displays the latest "brèves" (short news items) published by publipresse.fr.
 */
class Breves extends WidgetBase
{
    protected $defaultAlias = 'publipresseBreves';

    /**
     * API endpoint providing the JSON list of brèves.
     */
    protected const API_URL = 'https://www.publipresse.fr/api/breves';

    public function defineProperties()
    {
        return $this->titleProperty('Brèves') + $this->iconProperty('ph ph-megaphone') + [
            'itemCount' => [
                'title' => 'Nombre de brèves',
                'type' => 'dropdown',
                'default' => '5',
                'options' => [
                    '3' => '3',
                    '5' => '5',
                    '10' => '10',
                ],
            ],
            'cacheMinutes' => [
                'title' => 'Durée du cache (minutes)',
                'type' => 'string',
                'default' => '15',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'Indiquez un nombre de minutes',
            ],
        ];
    }

    protected function prepareVars(): void
    {
        $this->vars['items'] = $this->loadBreves((int) $this->property('itemCount', 5));
    }

    /**
     * @return array<int, array{title: string, tag: string, description: string}>
     */
    protected function loadBreves(int $count): array
    {
        $ttl = max(1, (int) $this->property('cacheMinutes', 15)) * 60;
        $cacheKey = 'publipresse.dashboard.breves.' . $count;

        return Cache::remember($cacheKey, $ttl, function () use ($count) {
            $response = Http::timeout(10)->get(self::API_URL);

            if (!$response->successful()) {
                throw new ApplicationException('Le service des brèves est injoignable (code ' . $response->status() . ').');
            }

            $payload = $response->json();
            if (!is_array($payload)) {
                throw new ApplicationException('Réponse inattendue du service des brèves.');
            }

            $items = [];
            foreach ($payload as $entry) {
                if (count($items) >= $count) {
                    break;
                }

                if (!is_array($entry)) {
                    continue;
                }

                $title = trim((string) ($entry['titre'] ?? ''));
                if ($title === '') {
                    continue;
                }

                $items[] = [
                    'title' => $title,
                    'tag' => trim((string) ($entry['tag'] ?? '')),
                    'description' => trim((string) ($entry['description'] ?? '')),
                ];
            }

            return $items;
        });
    }
}

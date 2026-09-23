<?php

namespace Publipresse\Dashboard\Classes;

use Cache;
use Http;
use Throwable;

/**
 * Fetches and caches the list of sales managers exposed by the corporate API,
 * used to populate the "Votre interlocuteur" widget dropdown.
 */
class SalesManagerRepository
{
    protected const API_URL = 'https://www.publipresse.fr/api/commerciaux';

    protected const CACHE_KEY = 'publipresse.dashboard.salesmanagers';

    protected const CACHE_MINUTES = 60;

    /**
     * all returns the normalized list of sales managers, keyed by id.
     *
     * @return array<string, array{id: string, name: string, email: ?string, phone: ?string, photo: ?string}>
     */
    public static function all(): array
    {
        return Cache::remember(static::CACHE_KEY, static::CACHE_MINUTES * 60, function () {
            return static::fetch();
        });
    }

    /**
     * find returns a single sales manager by id, or null when not found.
     *
     * @return array{id: string, name: string, email: ?string, phone: ?string, photo: ?string}|null
     */
    public static function find(string $id): ?array
    {
        return static::all()[$id] ?? null;
    }

    /**
     * options returns an id => name map suitable for a dropdown property.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = ['' => '- Choisissez un commercial -'];

        foreach (static::all() as $manager) {
            $options[$manager['id']] = $manager['name'];
        }

        return $options;
    }

    /**
     * @return array<string, array{id: string, name: string, email: ?string, phone: ?string, photo: ?string}>
     */
    protected static function fetch(): array
    {
        try {
            $response = Http::timeout(5)->get(static::API_URL);
        }
        catch (Throwable $ex) {
            return [];
        }

        if (!$response->successful() || !is_array($response->json())) {
            return [];
        }

        $result = [];

        foreach ($response->json() as $entry) {
            $id = trim((string) ($entry['id'] ?? ''));
            if ($id === '') {
                continue;
            }

            $name = trim(implode(' ', array_filter([$entry['prenom'] ?? null, $entry['nom'] ?? null])));

            $result[$id] = [
                'id' => $id,
                'name' => $name !== '' ? $name : 'Commercial #' . $id,
                'email' => filter_var($entry['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: null,
                'phone' => trim((string) ($entry['telephone'] ?? '')) ?: null,
                'photo' => filter_var($entry['photo'] ?? '', FILTER_VALIDATE_URL) ?: null,
            ];
        }

        return $result;
    }
}

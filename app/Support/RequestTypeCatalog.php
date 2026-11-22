<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Collection;

/** Small, dependency-free catalogue backed by config/request-types.php. */
final class RequestTypeCatalog
{
    /** @return array<string, string> */
    public static function groups(): array
    {
        /** @var array<string, string> $groups */
        $groups = config('request-types.groups', []);

        return $groups;
    }

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        /** @var array<string, array<string, mixed>> $types */
        $types = config('request-types.types', []);

        return $types;
    }

    /** @return array<string, mixed>|null */
    public static function find(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    public static function isValid(string $key): bool
    {
        return self::find($key) !== null;
    }

    public static function label(string $key): string
    {
        return (string) __('portal.types.'.$key.'.label');
    }

    public static function description(string $key): string
    {
        return (string) __('portal.types.'.$key.'.description');
    }

    public static function groupLabel(string $group): string
    {
        $key = self::groups()[$group] ?? '';

        return $key === '' ? $group : (string) __($key);
    }

    public static function iconPath(string $key): string
    {
        return (string) (self::find($key)['icon'] ?? '');
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];
        foreach (self::all() as $key => $type) {
            $options[$key] = self::groupLabel((string) $type['group']).' — '.self::label($key);
        }

        return $options;
    }

    /** @return array<string, array<int, array{key: string, label: string, description: string, group: string, icon: string, url: string}>> */
    public static function grouped(): array
    {
        $result = [];
        foreach (self::groups() as $group => $label) {
            foreach (self::all() as $key => $type) {
                if (($type['group'] ?? null) !== $group) {
                    continue;
                }

                $result[$group][] = [
                    'key' => $key,
                    'label' => self::label($key),
                    'description' => self::description($key),
                    'group' => self::groupLabel($group),
                    'icon' => self::iconPath($key),
                    'url' => route('tickets.create', ['type' => $key]),
                ];
            }
        }

        return $result;
    }

    /**
     * Live-search matches across localized labels, descriptions, keywords and
     * help articles. Everything is config/translation based, so it is fast and
     * needs no database query.
     *
     * @return array{types: array<int, array<string, string>>, articles: array<int, array<string, string>>}
     */
    public static function search(string $term, int $limit = 5): array
    {
        $needle = mb_strtolower(trim($term));
        if ($needle === '') {
            return ['types' => [], 'articles' => []];
        }

        $types = collect(self::all())
            ->filter(static function (array $type, string $key) use ($needle): bool {
                $haystack = mb_strtolower(implode(' ', [
                    $key,
                    self::label($key),
                    self::description($key),
                    self::groupLabel((string) $type['group']),
                    (string) ($type['keywords'] ?? ''),
                ]));

                return str_contains($haystack, $needle);
            })
            ->take($limit)
            ->map(static fn (array $type, string $key): array => [
                'key' => $key,
                'label' => self::label($key),
                'description' => self::description($key),
                'group' => self::groupLabel((string) $type['group']),
                'icon' => self::iconPath($key),
                'url' => route('tickets.create', ['type' => $key]),
            ])
            ->values()
            ->all();

        /** @var array<string, array<string, mixed>> $articleConfig */
        $articleConfig = config('request-types.articles', []);
        $articles = collect($articleConfig)
            ->filter(static function (array $article, string $key) use ($needle): bool {
                $haystack = mb_strtolower(implode(' ', [
                    $key,
                    (string) __('portal.articles.'.$key),
                    (string) ($article['keywords'] ?? ''),
                ]));

                return str_contains($haystack, $needle);
            })
            ->take($limit)
            ->map(static fn (array $article, string $key): array => [
                'key' => $key,
                'label' => (string) __('portal.articles.'.$key),
                'url' => (string) $article['url'],
            ])
            ->values()
            ->all();

        return ['types' => $types, 'articles' => $articles];
    }

    /**
     * @param  Collection<int, string>  $keys
     * @return Collection<string, string>
     */
    public static function labelsFor(Collection $keys): Collection
    {
        return $keys->mapWithKeys(static fn (string $key): array => [$key => self::label($key)]);
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

final class MentionParser
{
    private const PATTERN = '/@([A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}|[A-Za-z][A-Za-z0-9_.\-]{2,39})/u';

    /** @return Collection<int, User> */
    public function parse(string $body): Collection
    {
        if (preg_match_all(self::PATTERN, $body, $matches) === 0) {
            return collect();
        }

        $tokens = array_unique($matches[1]);
        $users = collect();

        foreach ($tokens as $token) {
            $user = str_contains($token, '@')
                ? User::where('email', mb_strtolower($token))->first()
                : User::whereRaw('lower(name) = ?', [mb_strtolower($token)])->first();

            if ($user instanceof User && ! $users->contains('id', $user->id)) {
                $users->push($user);
            }
        }

        return $users->take(10);
    }
}

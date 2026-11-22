<?php

declare(strict_types=1);

namespace App\Support\Scopes;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/** @implements Scope<Ticket> */
final class VisibleToUserScope implements Scope
{
    /**
     * @param  Builder<covariant Ticket>  $builder
     * @param  Ticket  $model
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::guard('web')->user();
        if (! $user instanceof User || $user->isAdmin()) {
            return;
        }

        $builder->where(static function (Builder $query) use ($user): void {
            $query->where('requester_id', $user->id)
                ->orWhere('reporter_id', $user->id);

            if ($user->isAgent()) {
                $query->orWhereIn('project_id', $user->projects()->select('projects.id'));
            }
        });
    }
}

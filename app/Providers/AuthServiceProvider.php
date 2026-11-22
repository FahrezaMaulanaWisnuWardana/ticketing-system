<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\CannedReply;
use App\Models\Category;
use App\Models\Comment;
use App\Models\IntegrationConnection;
use App\Models\Label;
use App\Models\Project;
use App\Models\SavedFilter;
use App\Models\Sprint;
use App\Models\Ticket;
use App\Models\User;
use App\Policies\AttachmentPolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\CannedReplyPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CommentPolicy;
use App\Policies\DevPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\TicketPolicy;
use App\Policies\TrelloPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

final class AuthServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    protected $policies = [
        Ticket::class => TicketPolicy::class,
        Project::class => ProjectPolicy::class,
        Comment::class => CommentPolicy::class,
        User::class => UserPolicy::class,
        Category::class => CategoryPolicy::class,
        Attachment::class => AttachmentPolicy::class,
        AuditLog::class => AuditLogPolicy::class,
        CannedReply::class => CannedReplyPolicy::class,
        Sprint::class => DevPolicy::class,
        Label::class => DevPolicy::class,
        SavedFilter::class => DevPolicy::class,
        IntegrationConnection::class => TrelloPolicy::class,
    ];

    public function boot(): void
    {
        Gate::before(static fn (User $user): ?bool => $user->isAdmin() ? true : null);
    }
}

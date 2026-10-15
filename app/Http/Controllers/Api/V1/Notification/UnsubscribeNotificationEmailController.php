<?php

namespace App\Http\Controllers\Api\V1\Notification;

use App\Http\Controllers\Controller;
use Domain\Notification\Actions\UpdateNotificationEmailPreferencesAction;
use Domain\Notification\Enums\NotificationCategory;
use Domain\Notification\Support\NotificationMailLinks;
use Domain\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UnsubscribeNotificationEmailController extends Controller
{
    public function __construct(
        private readonly UpdateNotificationEmailPreferencesAction $action,
    ) {}

    public function __invoke(Request $request, User $user, NotificationCategory $category): Response
    {
        $this->action->execute($user, [$category->value => false]);

        if ($request->isMethod('post')) {
            return response()->noContent();
        }

        app()->setLocale($user->preferredLocale());

        return response()->view('emails.notifications.unsubscribed', [
            'categoryLabel' => __('notifications.preferences.categories.'.$category->value.'.label'),
            'preferencesUrl' => NotificationMailLinks::preferences(),
        ]);
    }
}

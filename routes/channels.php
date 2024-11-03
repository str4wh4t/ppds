<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
// });

// Broadcast::channel('notifications.{id}', function (User $user, int $id) {
//     return true; // (int) $user->id === (int) $id;
// });

Broadcast::channel('user.notification.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

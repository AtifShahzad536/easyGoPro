<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('available-rides', function ($user) {
    // Authenticated users (drivers) can listen for incoming rides
    return true;
});

Broadcast::channel('rider.{id}', function ($user, $id) {
    // Only the specific rider can listen to their own ride status updates
    return (int) $user->id === (int) $id;
});

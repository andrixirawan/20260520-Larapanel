<?php

return [
    'user_avatars' => [
        'disk' => env('USER_AVATAR_DISK', 'public'),
        'directory' => 'uploads/users/{user}/avatars',
        'max_size' => 2048,
        'mimes' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
    ],
];

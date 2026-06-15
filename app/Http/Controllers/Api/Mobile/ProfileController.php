<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mobile\UpdateProfileRequest;
use App\Http\Resources\Mobile\UserResource;
use App\Support\UserAvatarStorage;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function update(UpdateProfileRequest $request, UserAvatarStorage $avatars): JsonResponse
    {
        $user = $request->user();
        $user->name = $request->validated('name');

        if ($request->boolean('remove_avatar') || $request->hasFile('avatar')) {
            $avatars->delete($user);
            $user->avatar = null;
        }

        if ($avatar = $request->file('avatar')) {
            $user->avatar = $avatars->store($user, $avatar);
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated.',
            'data' => UserResource::make($user->refresh()),
        ]);
    }
}

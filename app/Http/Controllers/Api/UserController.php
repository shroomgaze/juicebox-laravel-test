<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaginationRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Users
 */
class UserController extends Controller
{
    /**
     * List all users.
     *
     * Returns a paginated list of registered users. Default 15 users per page.
     */
    public function index(PaginationRequest $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->input('per_page', 15);

        $users = User::latest()->paginate($perPage);

        return UserResource::collection($users);
    }

    /**
     * Get a user profile.
     *
     * Returns the public profile of a specific user.
     */
    public function show(User $user): UserResource
    {
        return new UserResource($user);
    }
}

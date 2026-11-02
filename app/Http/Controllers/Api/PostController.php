<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaginationRequest;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * @tags Posts
 */
class PostController extends Controller
{
    /**
     * List all posts.
     *
     * Returns a paginated list of posts with their authors. Default 15 posts per page.
     */
    public function index(PaginationRequest $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->input('per_page', 15);

        $posts = Post::with('user')
            ->latest()
            ->paginate($perPage);

        return PostResource::collection($posts);
    }

    /**
     * Create a new post.
     *
     * Creates a post owned by the authenticated user.
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        $post = $request->user()->posts()->create($request->validated());

        $post->load('user');

        return (new PostResource($post))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get a specific post.
     *
     * Returns a single post with its author.
     */
    public function show(Post $post): PostResource
    {
        $post->load('user');

        return new PostResource($post);
    }

    /**
     * Update a post.
     *
     * Only the post owner can update. Send only the fields you want to change.
     */
    public function update(UpdatePostRequest $request, Post $post): PostResource
    {
        $post->update($request->validated());

        $post->load('user');

        return new PostResource($post);
    }

    /**
     * Delete a post.
     *
     * Only the post owner can delete their post.
     */
    public function destroy(Post $post): Response
    {
        Gate::authorize('delete', $post);

        $post->delete();

        return response()->noContent();
    }
}

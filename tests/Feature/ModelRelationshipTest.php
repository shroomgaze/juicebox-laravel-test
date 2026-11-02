<?php

use App\Models\Post;
use App\Models\User;

test('a user has many posts', function () {
    $user = User::factory()->create();
    $posts = Post::factory(3)->for($user)->create();

    expect($user->posts)->toHaveCount(3);
    expect($user->posts->first())->toBeInstanceOf(Post::class);
});

test('a post belongs to a user', function () {
    $user = User::factory()->create(['name' => 'Alice']);
    $post = Post::factory()->for($user)->create();

    expect($post->user)->toBeInstanceOf(User::class);
    expect($post->user->id)->toBe($user->id);
    expect($post->user->name)->toBe('Alice');
});

test('deleting a user cascades and deletes all associated posts', function () {
    $user = User::factory()->create();
    $posts = Post::factory(3)->for($user)->create();

    expect(Post::where('user_id', $user->id)->count())->toBe(3);

    $user->delete();

    expect(Post::where('user_id', $user->id)->count())->toBe(0);
});

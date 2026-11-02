<?php

use App\Models\Post;
use App\Models\User;

test('can list paginated posts', function () {
    $user = User::factory()->has(Post::factory(20))->create();

    $response = $this->getJson('/api/posts');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'title', 'body', 'user', 'created_at', 'updated_at'],
            ],
            'links',
            'meta',
        ])
        ->assertJsonCount(15, 'data')
        ->assertJsonPath('meta.total', 20);
});

test('can list paginated posts with custom per_page', function () {
    User::factory()->has(Post::factory(10))->create();

    $response = $this->getJson('/api/posts?per_page=5');

    $response->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('meta.per_page', 5)
        ->assertJsonPath('meta.last_page', 2);
});

test('posts pagination rejects invalid page parameter', function () {
    $this->getJson('/api/posts?page=invalid')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['page']);
});

test('can show a specific post', function () {
    $post = Post::factory()->create();

    $response = $this->getJson("/api/posts/{$post->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $post->id)
        ->assertJsonPath('data.title', $post->title)
        ->assertJsonStructure([
            'data' => ['id', 'title', 'body', 'user', 'created_at', 'updated_at'],
        ]);
});

test('returns 404 for nonexistent post', function () {
    $this->getJson('/api/posts/999')->assertNotFound();
});

test('authenticated user can create a post', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/posts', [
            'title' => 'My New Post',
            'body' => 'This is the body of my new post.',
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.title', 'My New Post')
        ->assertJsonPath('data.body', 'This is the body of my new post.');

    $this->assertDatabaseHas('posts', [
        'title' => 'My New Post',
        'user_id' => $user->id,
    ]);
});

test('unauthenticated user cannot create a post', function () {
    $response = $this->postJson('/api/posts', [
        'title' => 'My New Post',
        'body' => 'Body content.',
    ]);

    $response->assertUnauthorized();
});

test('creating a post validates required fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/posts', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'body']);
});

test('authenticated user can update their own post', function () {
    $user = User::factory()->create();
    $post = Post::factory()->for($user)->create();

    $response = $this->actingAs($user)
        ->patchJson("/api/posts/{$post->id}", [
            'title' => 'Updated Title',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.title', 'Updated Title');

    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'title' => 'Updated Title',
    ]);
});

test('user cannot update another users post', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $post = Post::factory()->for($owner)->create();

    $response = $this->actingAs($otherUser)
        ->patchJson("/api/posts/{$post->id}", [
            'title' => 'Hacked Title',
        ]);

    $response->assertForbidden();
});

test('authenticated user can delete their own post', function () {
    $user = User::factory()->create();
    $post = Post::factory()->for($user)->create();

    $response = $this->actingAs($user)
        ->deleteJson("/api/posts/{$post->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
});

test('user cannot delete another users post', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $post = Post::factory()->for($owner)->create();

    $response = $this->actingAs($otherUser)
        ->deleteJson("/api/posts/{$post->id}");

    $response->assertForbidden();
});

test('api requests include rate limit headers', function () {
    $this->getJson('/api/posts')
        ->assertOk()
        ->assertHeader('x-ratelimit-limit', 60);
});

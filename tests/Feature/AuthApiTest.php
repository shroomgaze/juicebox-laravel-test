<?php

use App\Jobs\SendWelcomeEmail;
use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('user can register and receives a token', function () {
    Queue::fake();

    $response = $this->postJson('/api/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'message',
            'user' => ['id', 'name', 'email'],
            'token',
        ])
        ->assertJsonPath('user.name', 'John Doe')
        ->assertJsonPath('user.email', 'john@example.com');

    $this->assertDatabaseHas('users', ['email' => 'john@example.com']);

    Queue::assertPushed(SendWelcomeEmail::class, function ($job) {
        return $job->user->email === 'john@example.com';
    });
});

test('registration validates required fields', function () {
    $response = $this->postJson('/api/register', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

test('registration rejects duplicate email', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $response = $this->postJson('/api/register', [
        'name' => 'Jane Doe',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('user can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'jane@example.com',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'jane@example.com',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'message',
            'user' => ['id', 'name', 'email'],
            'token',
        ]);
});

test('login fails with invalid credentials', function () {
    User::factory()->create(['email' => 'jane@example.com']);

    $response = $this->postJson('/api/login', [
        'email' => 'jane@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('authenticated user can logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/logout');

    $response->assertOk()
        ->assertJsonPath('message', 'Logged out successfully.');

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

test('unauthenticated user cannot logout', function () {
    $this->postJson('/api/logout')->assertUnauthorized();
});

test('authenticated user can view a user profile', function () {
    $user = User::factory()->create();
    $viewUser = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson("/api/users/{$viewUser->id}");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => ['id', 'name', 'email', 'created_at'],
        ])
        ->assertJsonPath('data.id', $viewUser->id);
});

test('unauthenticated user cannot view a user profile', function () {
    $user = User::factory()->create();

    $this->getJson("/api/users/{$user->id}")->assertUnauthorized();
});

test('authenticated user can list paginated users', function () {
    User::factory(20)->create();
    $user = User::first();

    $response = $this->actingAs($user)
        ->getJson('/api/users');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'email', 'created_at'],
            ],
            'links',
            'meta',
        ])
        ->assertJsonCount(15, 'data');
});

test('authenticated user can list paginated users with custom per_page', function () {
    User::factory(10)->create();
    $user = User::first();

    $response = $this->actingAs($user)
        ->getJson('/api/users?per_page=5');

    $response->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('meta.per_page', 5);
});

test('unauthenticated user cannot list users', function () {
    $this->getJson('/api/users')->assertUnauthorized();
});

test('artisan command can dispatch welcome email job for a user', function () {
    Queue::fake();

    $user = User::factory()->create();

    $this->artisan('app:send-welcome-email', ['user_id' => $user->id])
        ->assertSuccessful()
        ->expectsOutput("Welcome email job dispatched for {$user->name} ({$user->email}).");

    Queue::assertPushed(SendWelcomeEmail::class, function ($job) use ($user) {
        return $job->user->id === $user->id;
    });
});

test('artisan command returns failure when user is not found', function () {
    $this->artisan('app:send-welcome-email', ['user_id' => 99999])
        ->assertFailed()
        ->expectsOutput('User with ID 99999 not found.');
});

test('welcome email job sends email to the user', function () {
    Mail::fake();

    $user = User::factory()->create();

    $job = new SendWelcomeEmail($user);
    $job->handle();

    Mail::assertSent(WelcomeEmail::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email)
            && $mail->user->id === $user->id;
    });
});

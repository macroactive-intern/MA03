<?php

use App\Models\CheckIn;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Helpers ────────────────────────────────────────────────────────────────

function client(array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => 'client'], $attrs));
}

function coach(array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => 'coach'], $attrs));
}

// ─── POST /api/check-ins ────────────────────────────────────────────────────

test('client can log a check-in for today', function () {
    $user = client();

    $response = $this->actingAs($user)->postJson('/api/check-ins', [
        'checked_in_date' => Carbon::today()->toDateString(),
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['id', 'checked_in_date', 'notes']]);

    $this->assertDatabaseHas('check_ins', [
        'user_id'         => $user->id,
        'checked_in_date' => Carbon::today()->toDateString(),
    ]);
});

test('future check-in date is rejected', function () {
    $user = client();

    $response = $this->actingAs($user)->postJson('/api/check-ins', [
        'checked_in_date' => Carbon::tomorrow()->toDateString(),
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['checked_in_date']);
});

test('notes over 500 characters are rejected', function () {
    $user = client();

    $response = $this->actingAs($user)->postJson('/api/check-ins', [
        'checked_in_date' => Carbon::today()->toDateString(),
        'notes'           => str_repeat('a', 501),
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['notes']);
});

test('duplicate check-in for the same date returns a validation error', function () {
    $user = client();

    CheckIn::factory()->create([
        'user_id'         => $user->id,
        'checked_in_date' => Carbon::today()->toDateString(),
    ]);

    $response = $this->actingAs($user)->postJson('/api/check-ins', [
        'checked_in_date' => Carbon::today()->toDateString(),
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['checked_in_date']);
});

// ─── GET /api/check-ins/streak ──────────────────────────────────────────────

test('user can view their current streak', function () {
    $user = client();

    CheckIn::factory()->create([
        'user_id'         => $user->id,
        'checked_in_date' => Carbon::today()->toDateString(),
    ]);

    $response = $this->actingAs($user)->getJson('/api/check-ins/streak');

    $response->assertStatus(200)
        ->assertJson([
            'current_streak'           => 1,
            'weekly_check_in_complete' => true,
        ]);
});

test('streak breaks after a skipped day', function () {
    $user = client();

    CheckIn::factory()->create([
        'user_id'         => $user->id,
        'checked_in_date' => Carbon::today()->subDays(2)->toDateString(),
    ]);

    CheckIn::factory()->create([
        'user_id'         => $user->id,
        'checked_in_date' => Carbon::today()->toDateString(),
    ]);

    $response = $this->actingAs($user)->getJson('/api/check-ins/streak');

    $response->assertStatus(200)
        ->assertJson(['current_streak' => 1]);
});

test('weekly check-in complete is true when user checked in this week', function () {
    $user = client();

    CheckIn::factory()->create([
        'user_id'         => $user->id,
        'checked_in_date' => Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString(),
    ]);

    $response = $this->actingAs($user)->getJson('/api/check-ins/streak');

    $response->assertStatus(200)
        ->assertJson(['weekly_check_in_complete' => true]);
});

test('weekly check-in complete is false when user has not checked in this week', function () {
    $user = client();

    CheckIn::factory()->create([
        'user_id'         => $user->id,
        'checked_in_date' => Carbon::now()->startOfWeek(Carbon::MONDAY)->subDay()->toDateString(),
    ]);

    $response = $this->actingAs($user)->getJson('/api/check-ins/streak');

    $response->assertStatus(200)
        ->assertJson(['weekly_check_in_complete' => false]);
});

test('check-ins are treated as calendar dates not datetimes', function () {
    $user = client();

    CheckIn::factory()->create([
        'user_id'         => $user->id,
        'checked_in_date' => Carbon::today()->toDateString(),
    ]);

    $response = $this->actingAs($user)->getJson('/api/check-ins/streak');

    $response->assertStatus(200)
        ->assertJson(['current_streak' => 1]);

    $this->assertDatabaseHas('check_ins', [
        'user_id'         => $user->id,
        'checked_in_date' => Carbon::today()->toDateString(),
    ]);
});

// ─── Coach endpoints ─────────────────────────────────────────────────────────

test('non-coach gets 403 on coach history endpoint', function () {
    $client  = client();
    $viewer  = client();

    $response = $this->actingAs($viewer)->getJson("/api/coach/clients/{$client->id}/check-ins");

    $response->assertStatus(403);
});

test('coach can view client check-in history', function () {
    $coachUser  = coach();
    $clientUser = client();

    CheckIn::factory()->create([
        'user_id'         => $clientUser->id,
        'checked_in_date' => Carbon::today()->toDateString(),
    ]);

    $response = $this->actingAs($coachUser)->getJson("/api/coach/clients/{$clientUser->id}/check-ins");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'client'    => ['id', 'name', 'email'],
            'check_ins',
            'meta'      => ['current_page', 'per_page', 'total'],
        ]);
});

test('coach can view client streak', function () {
    $coachUser  = coach();
    $clientUser = client();

    CheckIn::factory()->create([
        'user_id'         => $clientUser->id,
        'checked_in_date' => Carbon::today()->toDateString(),
    ]);

    $response = $this->actingAs($coachUser)->getJson("/api/coach/clients/{$clientUser->id}/streak");

    $response->assertStatus(200)
        ->assertJsonStructure(['current_streak', 'weekly_check_in_complete']);
});

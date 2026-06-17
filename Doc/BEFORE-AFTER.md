Before


SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: check_ins.user_id, check_ins.checked_in_date (Connection: sqlite, Database: :memory:, SQL: insert into "check_ins" ("checked_in_date", "notes", "user_id", "updated_at", "created_at") values(2026-06-17 00:00:00, ?, 1, 2026-06-17 00:21:20, 2026-06-17 00:21:20))

  at tests\Feature\CheckInTest.php:75
     71▕     $response = $this->actingAs($user)->postJson('/api/check-ins', [
     72▕         'checked_in_date' => Carbon::today()->toDateString(),
     73▕     ]);
     74▕ 
  ➜  75▕     $response->assertStatus(422)
     76▕         ->assertJsonValidationErrors(['checked_in_date']);
     77▕ });
     78▕ 
     79▕ // ─── GET /api/check-ins/streak ──────────────────────────────────────────────

  ──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────  
   FAILED  Tests\Feature\CheckInTest > check-ins are treated as calendar dates not datetimes                                          
  Failed asserting that a row in the table [check_ins] matches the attributes {
    "user_id": 1,
    "checked_in_date": "2026-06-17"
}.

Found similar results: [
    {
        "user_id": 1,
        "checked_in_date": "2026-06-17 00:00:00"
    }
].

  at tests\Feature\CheckInTest.php:158
    154▕ 
    155▕     $response->assertStatus(200)
    156▕         ->assertJson(['current_streak' => 1]);
    157▕ 
  ➜ 158▕     $this->assertDatabaseHas('check_ins', [
    159▕         'user_id'         => $user->id,
    160▕         'checked_in_date' => Carbon::today()->toDateString(),
    161▕     ]);
    162▕ });


  Tests:    3 failed, 11 passed (40 assertions)
  Duration: 0.59s

  
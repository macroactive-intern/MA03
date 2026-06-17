Before

 FAIL  Tests\Feature\CheckInTest
  ⨯ client can log a check-in for today                                                                                        0.23s  
  ✓ future check-in date is rejected                                                                                           0.01s  
  ✓ notes over 500 characters are rejected                                                                                     0.01s  
  ⨯ duplicate check-in for the same date returns a validation error                                                            0.02s  
  ✓ user can view their current streak                                                                                         0.01s  
  ✓ streak breaks after a skipped day                                                                                          0.01s  
  ✓ weekly check-in complete is true when user checked in this week                                                            0.01s  
  ✓ weekly check-in complete is false when user has not checked in this week                                                   0.01s  
  ⨯ check-ins are treated as calendar dates not datetimes                                                                      0.01s  
  ✓ non-coach gets 403 on coach history endpoint                                                                               0.01s  
  ✓ coach can view client check-in history                                                                                     0.01s  
  ✓ coach can view client streak                                                                                               0.01s  

   PASS  Tests\Feature\ExampleTest
  ✓ the application returns a successful response                                                                              0.03s  
  ──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────  
   FAILED  Tests\Feature\CheckInTest > client can log a check-in for today                                                            
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

  at tests\Feature\CheckInTest.php:34
     30▕ 
     31▕     $response->assertStatus(201)
     32▕         ->assertJsonStructure(['data' => ['id', 'checked_in_date', 'notes']]);
     33▕ 
  ➜  34▕     $this->assertDatabaseHas('check_ins', [
     35▕         'user_id'         => $user->id,
     36▕         'checked_in_date' => Carbon::today()->toDateString(),
     37▕     ]);
     38▕ });

  ──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────  
   FAILED  Tests\Feature\CheckInTest > duplicate check-in for the same date returns a validation error                                
  Expected response status code [422] but received 500.
Failed asserting that 500 is identical to 422.


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


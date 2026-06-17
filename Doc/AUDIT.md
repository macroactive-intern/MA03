# Rubric Audit — Client Check-in Streak Tracker

Audited against: `Doc/rubric.md`
Date: 2026-06-17

Pass/fail only. No partial credit.

---

## Results Summary

| # | Criterion | Result |
|---|-----------|--------|
| 1 | Type Safety | FAIL |
| 2 | Error Handling | PASS |
| 3 | Observability | FAIL |
| 4 | Configuration | FAIL |
| 5 | Validation | PASS |
| 6 | Data Integrity | FAIL |
| 7 | Security | FAIL |
| 8 | API Consistency | FAIL |
| 9 | Tests Pass | PASS |
| 10 | No Hardcoded Environment Values | FAIL |

**3 pass / 7 fail**

---

## Criterion Detail

### 1. Type Safety — FAIL

`declare(strict_types=1)` is absent from every file under `app/`.

Files missing it:
- `app/Models/CheckIn.php`
- `app/Models/User.php`
- `app/Http/Controllers/CheckInController.php`
- `app/Http/Controllers/Api/CoachClientCheckInController.php`
- `app/Http/Requests/StoreCheckInRequest.php`
- `app/Http/Middleware/EnsureUserIsCoach.php`
- `app/Services/CheckInStreakService.php`

Methods do have typed parameters and return types throughout, but without `strict_types=1` PHP will silently coerce mismatched scalar types at call boundaries.

**Fix:** Add `declare(strict_types=1);` after `<?php` in every file under `app/`.

---

### 2. Error Handling — PASS

No `new \Exception(...)` is thrown anywhere for business logic errors. Validation failures are expressed through `ValidationException` via `StoreCheckInRequest`. The duplicate check-in failure adds a specific key (`checked_in_date`) via `$validator->errors()->add()`. The coach role check and client-only guard return structured JSON responses rather than thrown exceptions.

---

### 3. Observability — FAIL

No `Log::info()` calls exist anywhere in `app/`. The check-in creation in `CheckInController@store` is a state-changing operation that emits no log entry. There is no way to answer "did user #12 check in on 2026-06-17?" from a log aggregator without querying the database directly.

**Fix:** Add a `Log::info()` call in `CheckInController@store` after the check-in is created, including the user ID, check-in ID, and date.

```php
Log::info('check_in.created', [
    'user_id'         => $checkIn->user_id,
    'check_in_id'     => $checkIn->id,
    'checked_in_date' => $checkIn->checked_in_date->toDateString(),
]);
```

---

### 4. Configuration — FAIL

Two magic numbers appear in application logic with no corresponding config entry:

- `max:500` — the notes character limit in `StoreCheckInRequest::rules()`
- `paginate(15)` — the page size in `CoachClientCheckInController@index`

Neither value is referenced via `config()`. A developer cannot change either without touching business logic code.

**Fix:** Create a `config/check_ins.php` file and reference values via `config('check_ins.notes_max_length')` and `config('check_ins.history_per_page')`.

---

### 5. Validation — PASS

The duplicate check-in validation in `StoreCheckInRequest::withValidator()` performs one `exists()` query per request. There are no collection-based validation loops and no repeated DB queries for the same data within a single request lifecycle.

---

### 6. Data Integrity — FAIL

`CheckInController@store` follows a read-then-write pattern: `withValidator()` reads to check for a duplicate, then the controller writes the new row. This read is not wrapped in `lockForUpdate()`.

Two concurrent POST requests from the same user for the same date can both pass the duplicate check simultaneously. One will succeed; the other will hit the database unique constraint and return a `500` instead of a clean `422`.

**Fix:** Wrap the duplicate check and insert in a `DB::transaction()` with `lockForUpdate()`:

```php
DB::transaction(function () use ($request) {
    $existing = CheckIn::where('user_id', $request->user()->id)
        ->whereDate('checked_in_date', $request->validated('checked_in_date'))
        ->lockForUpdate()
        ->exists();

    if ($existing) {
        throw ValidationException::withMessages([
            'checked_in_date' => 'You have already checked in for this date.',
        ]);
    }

    return $request->user()->checkIns()->create([...]);
});
```

---

### 7. Security — FAIL

All four routes are correctly behind `auth:sanctum` and coach routes are additionally gated by `EnsureUserIsCoach`. No stack traces are returned in tested paths.

However, the rubric requires authorization policies for resource mutations. No Laravel policy exists for `CheckIn`. The controller does not call `$this->authorize()` or `Gate::authorize()`. A client can currently call `POST /api/check-ins` and create a check-in attributed to any `user_id` they supply in the payload — the `user_id` comes from `$request->user()` in the controller, which is correct, but there is no policy preventing a future developer from passing `user_id` directly without the protection being enforced at the policy layer.

**Fix:** Create a `CheckInPolicy` with a `create` method and register it. Call `$this->authorize('create', CheckIn::class)` in the controller.

---

### 8. API Consistency — FAIL

HTTP status codes are consistent and correct (`201` for creation, `200` for reads, `422` for validation, `403` for authorization, `404` for coach viewing non-client).

However, all controllers return raw arrays. No `JsonResource` classes exist. The rubric requires response shapes to use API resources consistently with no controller returning raw arrays alongside resource objects.

**Fix:** Create `CheckInResource` and `CheckInCollection` (or equivalent) and return them from all controllers instead of `response()->json([...])` with hand-built arrays.

---

### 9. Tests Pass — PASS

`php vendor/bin/pest` exits with code 0. All 14 tests pass (12 check-in feature tests, 2 example tests). No tests are pending or skipped. The suite covers happy paths and primary failure paths for all service methods and endpoints.

---

### 10. No Hardcoded Environment Values — FAIL

`.env.example` contains `APP_DEBUG=true`. The rubric requires `APP_DEBUG=false` in `.env.example`.

A developer copying `.env.example` verbatim for a production deployment will run with debug mode on, exposing full stack traces and request data in HTTP error responses.

**Fix:** Change `APP_DEBUG=true` to `APP_DEBUG=false` in `.env.example`.

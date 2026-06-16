# APPROACH.md

## Project summary

This project is a Laravel JSON API for a daily client check-in and streak tracking system.

Clients can log one check-in per calendar date to say they completed their work for that day. The API will also calculate the client's current streak and whether they have completed at least one check-in during the current calendar week.

Coaches can view a client's check-in history and streak information, but coach routes must only be available to authenticated users with the `coach` role.

There is no browser UI for this task. The output is JSON responses from API endpoints.

---

## Data model

### `users` table

The existing Laravel `users` table will be used for both clients and coaches. I will not create a separate `clients` table because the brief says the users table is the clients.

I will add a `role` column to the users table.

| Column | Type | Notes |
|--------|------|-------|
| `role` | string | Defaults to `client`. Allowed values will be `client` or `coach`. |

The default role will be `client` so new users are clients unless explicitly created as coaches.

### `check_ins` table

I will create a new `check_ins` table.

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigIncrements | Primary key |
| `user_id` | foreignId | References the `users` table |
| `checked_in_date` | date | Calendar date of the check-in, not a datetime |
| `notes` | text nullable | Optional client note, max 500 characters through validation |
| `created_at` | timestamp nullable | Laravel timestamp |
| `updated_at` | timestamp nullable | Laravel timestamp |

### Constraints

A user must only have one check-in per date, so I will add a unique database constraint:

```php
$table->unique(['user_id', 'checked_in_date']);
```

This protects the database even if validation is bypassed or two requests arrive close together.

I will also validate duplicates before insert so the API can return a clean validation error instead of a database exception.

### Model relationships

`User` will have many check-ins:

```php
public function checkIns()
{
    return $this->hasMany(CheckIn::class);
}
```

`CheckIn` will belong to a user:

```php
public function user()
{
    return $this->belongsTo(User::class);
}
```

The `CheckIn` model will cast `checked_in_date` as a date.

---

## Endpoints and routes

All routes will use `auth:sanctum`.

### Client routes

| Method | URI | Controller method | Purpose |
|--------|-----|-------------------|---------|
| `POST` | `/api/check-ins` | `CheckInController@store` | Log a check-in for the authenticated user |
| `GET` | `/api/check-ins/streak` | `CheckInController@streak` | Return the authenticated user's streak and weekly status |

### Coach routes

Coach routes will use both `auth:sanctum` and coach role middleware.

| Method | URI | Controller method | Purpose |
|--------|-----|-------------------|---------|
| `GET` | `/api/coach/clients/{user}/check-ins` | `CoachClientCheckInController@index` | View a client's check-in history |
| `GET` | `/api/coach/clients/{user}/streak` | `CoachClientCheckInController@streak` | View a client's streak and weekly status |

The `{user}` route parameter will use Laravel route model binding.

---

## Request validation

I will create a `StoreCheckInRequest` FormRequest for `POST /api/check-ins`.

Validation rules:

```php
'checked_in_date' => [
    'required',
    'date_format:Y-m-d',
    'before_or_equal:today',
],
'notes' => [
    'nullable',
    'string',
    'max:500',
],
```

I will use `Y-m-d` because the API example uses `2026-06-16`, the database column is a date-only field, and it avoids timezone parsing issues from full datetime strings.

Although I originally considered `DD/MM/YYYY`, I am choosing `YYYY-MM-DD` for the API because it is easier to validate, easier to store, and matches the brief's example JSON.

The FormRequest or controller will also check whether the authenticated user already has a check-in for the same `checked_in_date`. If one exists, the API will return a validation error.

---

## Response shapes

### `POST /api/check-ins`

On success, the API will return the created check-in.

Example:

```json
{
  "data": {
    "id": 1,
    "checked_in_date": "2026-06-16",
    "notes": "Completed workout and meal prep."
  }
}
```

### `GET /api/check-ins/streak`

Example:

```json
{
  "current_streak": 5,
  "weekly_check_in_complete": true
}
```

### `GET /api/coach/clients/{user}/check-ins`

The brief does not define an exact response shape for history, so I will return the client's basic details once at the top and then a paginated list of check-ins newest-first.

Example:

```json
{
  "client": {
    "id": 12,
    "name": "Jane Client",
    "email": "jane@example.com"
  },
  "check_ins": [
    {
      "id": 41,
      "checked_in_date": "2026-06-16",
      "notes": "Completed workout and meal prep."
    },
    {
      "id": 40,
      "checked_in_date": "2026-06-15",
      "notes": null
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 42
  }
}
```

I am choosing newest-first because coaches are most likely to care about the most recent activity first. I am including notes because notes are part of the check-in model and the coach history endpoint is the natural place to read them. I am including client information once so the coach can confirm whose history they are viewing without repeating the client object on every check-in.

### `GET /api/coach/clients/{user}/streak`

Example:

```json
{
  "current_streak": 5,
  "weekly_check_in_complete": true
}
```

---

## Libraries and packages

### Laravel

Laravel will be used for the API, routing, controllers, migrations, Eloquent models, validation, and testing structure.

### Laravel Sanctum

Sanctum will be used for API authentication because the brief requires all endpoints to use `auth:sanctum`.

### SQLite

SQLite will be used for local development and testing because the brief asks for SQLite configuration.

### Pest

Pest will be used for the test suite because the track expects Laravel tests and Pest gives readable feature tests for API endpoints.

### Carbon

Laravel includes Carbon for date handling. I will use Carbon for calendar date calculations like today, yesterday, start of week, and end of week.

---

## Streak algorithm

The streak is based on calendar dates, not 24-hour windows.

The algorithm will:

1. Get all check-in dates for the user.
2. Compare against calendar dates in the user's local-date context.
3. Start from today if the user has checked in today.
4. If the user has not checked in today but did check in yesterday, start counting from yesterday because the user may still have time left today to check in.
5. Walk backward one calendar day at a time.
6. Add 1 to the streak for each consecutive checked-in date.
7. Stop as soon as a missing date is found.
8. Return the count.

Example:

```text
Monday: checked in
Tuesday: checked in
Wednesday: skipped
Thursday: checked in
```

If the streak is checked on Thursday after the Thursday check-in, the current streak is `1` because Wednesday broke the chain.

Example:

```text
Monday: checked in
Tuesday: checked in
Wednesday: checked in
Thursday: checked in
Friday: not checked in yet
```

If it is still Friday, the current streak is `4` because the user still has time to check in for Friday. If Friday ends without a check-in, the streak becomes `0` the next day.

---

## Weekly completion algorithm

`weekly_check_in_complete` means the client has checked in at least once during the current calendar week.

I am choosing a Monday to Sunday calendar week, not a rolling 7-day window.

The algorithm will:

1. Get the start of the current week, Monday.
2. Get the end of the current week, Sunday.
3. Check whether the user has at least one `check_ins` record where `checked_in_date` is between those two dates.
4. Return `true` if one exists.
5. Return `false` if none exist.

I am choosing Monday to Sunday because it is a clear calendar week and is easier for coaches and clients to understand than a rolling 7-day window.

---

## Timezone approach

The brief says the check-in date is the client's local date, but it does not provide a timezone field on the users table.

For this version, I will trust the client app to send the already-correct local calendar date in `checked_in_date`.

For example, if a client is in Auckland and it is Wednesday morning there, the client app should send Wednesday's date, even if it is still Tuesday in UTC.

The API will store that submitted local date as-is in the `checked_in_date` date column.

Because the system stores date-only values, the streak logic will treat each check-in as a calendar day instead of trying to calculate 24-hour windows.

If the product later needs stronger timezone support, I would add a `timezone` column to the users table and calculate today/week boundaries from that timezone. I will not add that now because the brief does not ask for a timezone column.

---

## Authorization approach

All endpoints require authentication through Sanctum.

Coach endpoints require an extra role check. I will create middleware that checks:

```php
$request->user()->role === 'coach'
```

If the user is not a coach, the API returns `403 Forbidden`.

For this task, coaches can view all client users because the brief does not include a coach-client assignment table. However, the endpoint should only be used for users with the `client` role. A coach should not be able to view another coach's streak through a client route.

---

## Edge cases and how I will handle them

### No check-ins exist

Return:

```json
{
  "current_streak": 0,
  "weekly_check_in_complete": false
}
```

### Duplicate check-in for the same date

The API returns a validation error.

This is handled in two places:

1. Validation checks for an existing check-in before creating a new row.
2. The database has a unique constraint on `user_id` and `checked_in_date`.

### Future check-in date

The API rejects future dates using validation.

### Past check-in date

I will not allow clients to create past check-ins. The user can only check in for the current local date. This prevents users from repairing broken streaks by backfilling missed days.

This means I will validate that `checked_in_date` is equal to today's local date for the intended client-date context.

### Missing today but checked in yesterday

The streak remains active during the current day.

For example, if the user checked in Monday to Thursday and today is Friday, their streak is still `4` until Friday ends. If they check in Friday, it becomes `5`. If they do not check in Friday, the streak becomes `0` on Saturday.

### Skipped day

A skipped calendar day breaks the streak.

Example:

```text
Monday checked in
Tuesday checked in
Wednesday skipped
Thursday checked in
```

Current streak on Thursday after checking in is `1`.

### Non-coach accessing coach endpoints

Return `403 Forbidden`.

### Coach tries to view another coach as if they are a client

Return an error rather than showing another coach's check-ins. The route says `/clients/{user}`, so the target user should have role `client`.

### Notes are too long

Reject with a validation error if `notes` is longer than 500 characters.

### Notes are missing

Allow the check-in because `notes` is nullable.

### Date includes a time

Reject it. The API expects `Y-m-d` only, because the business logic is based on dates, not datetimes.

---

## Testing approach

I will write feature tests before completing the implementation, then run them to show they fail first.

Test cases will cover:

1. A client can log a check-in for today.
2. A future date is rejected.
3. A past date is rejected.
4. Notes over 500 characters are rejected.
5. Duplicate same-day check-ins return a validation error.
6. `GET /api/check-ins/streak` returns the correct streak.
7. A skipped day breaks the streak.
8. A streak remains active if today has not been checked in yet but yesterday was checked in.
9. `weekly_check_in_complete` is true after at least one check-in in the current Monday-Sunday week.
10. `weekly_check_in_complete` is false when there are no check-ins in the current week.
11. Non-coach users receive `403` from coach endpoints.
12. Coaches can view client check-in history.
13. Coaches can view client streak.
14. Coaches cannot view another coach through a client route.

After implementation, I will run the tests again and paste both failing and passing terminal output into `BEFORE-AFTER.md`.

---

## Decisions made from ambiguous parts of the brief

| Ambiguous part | Decision | Reason |
|----------------|----------|--------|
| Where does the client timezone come from? | Trust the client app to send the correct local date. | The brief does not include a timezone column. |
| What date format should the API accept? | `Y-m-d`, for example `2026-06-16`. | Matches the brief's example and avoids datetime parsing issues. |
| Does the streak need to include today? | Not necessarily. If today is not checked in yet but yesterday was, the streak remains active until the end of today. | This is fairer for daily check-ins. |
| What is the current week? | Monday to Sunday calendar week. | Clear and easy for coaches to understand. |
| Can clients check in for past dates? | No. Only the current local date is allowed. | Prevents users from backfilling missed days and changing streak history. |
| How are duplicates prevented? | Both validation and a database unique constraint. | Validation gives a clean API error; the database protects data integrity. |
| What should history look like? | Newest-first, paginated, notes included, basic client info once at the top. | Useful for coaches and avoids huge responses. |
| Can coaches view all users? | Coaches can view all clients, but not other coaches through client routes. | No coach-client assignment exists in the brief, but `/clients/{user}` should mean client users. |
| How is coach access checked? | Middleware checking `role === 'coach'`. | Simple, testable, and matches the brief. |

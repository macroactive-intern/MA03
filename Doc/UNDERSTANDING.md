What I need to make.

This task is asking me to build a Laravel JSON API for daily client check-ins.

MacroActive clients should be able to log one check-in per calendar date to say they completed their work for that day. The API also needs to calculate the client's current check-in streak and whether they have completed at least one check-in during the current week.

-----------------------------------------------------------------------------------------------------------------

What inputs does it need to take?

POST /api/check-ins

Authenticated client logs a check-in.

Expected input:

{
  "checked_in_date": "2026-06-16",
  "notes": "Optional message"
}

Validation rules:

        checked_in_date is required
        checked_in_date must be a valid date
        checked_in_date cannot be in the future
        notes is optional
        notes must be a string if provided
        notes must be no longer than 500 characters

----------------------------------------------------------

GET /api/check-ins/streak

Authenticated client asks for their own current streak and weekly status.

No request body.

Expected response shape:

{
  "current_streak": 5,
  "weekly_check_in_complete": true
}
GET /api/coach/clients/{user}/check-ins

Authenticated coach views a specific user's check-in history.

Input:

{user} route parameter, using Laravel route model binding for a User.

Expected response:

a list of that user's check-ins

The exact response shape is not specified.

GET /api/coach/clients/{user}/streak

Authenticated coach views a specific user's streak and weekly status.

Input:

{user} route parameter, using Laravel route model binding for a User.

Expected response:

{
  "current_streak": 5,
  "weekly_check_in_complete": true
}

-----------------------------------------------------------------------------------------------------------------

What should it Display?

This is a JSON API, so nothing is displayed in a browser UI

        1. A successful JSON response after creating a check-in
        2. Validation errors when input is invalid
        3. A validation error when the same user tries to check in twice for the same date
        4. A streak response with current_streak and weekly_check_in_complete
        5. Check-in history for coach endpoints
        6. 403 Forbidden when a non-coach user tries to access coach-only endpoints

-----------------------------------------------------------------------------------------------------------------

Where does the client's timezone come from?

trust the client app to send the already-correct local date

----------------------------------------------

What exactly counts as the current streak?

If the user checks in Monday, Tuesday, Wednesday, and Thursday it'll be a streak of 4 even if its friday and haven't checked in for the day yet. If the user checks in before midnight the streak will become 5 if they dont it resets to 0

----------------------------------------------

What is the current week?

the current week should always be Monday to Sunday

----------------------------------------------

Which timezone determines the current week?

The users local timezone

----------------------------------------------

Can a client check in for past dates?

no the user can only check in on the current dateand cant change the past dates. or future dates

----------------------------------------------

Should duplicate check-ins be prevented by validation, database constraint, or both?

1. A unique database index on (user_id, checked_in_date) for data integrity
2. A FormRequest or service-level validation rule to return a clean validation error before hitting the database

----------------------------------------------

What should the check-in history response look like?

Newest-first, paginated, notes included, with minimal client info included once at the top

----------------------------------------------

Are coaches allowed to view every user?

For now i will allow coaches to see all uses, since the brief doesn't mention it, so for now i will go the basic route and let coaches see all clients

----------------------------------------------

Are coaches allowed to view every user?

should only be able to see clients not be able to see other coaches streaks

----------------------------------------------

What is the exact role-check mechanism?

I will likely implement a small middleware that checks auth()->user()->role === 'coach'.

----------------------------------------------

What date format should be accepted?

the date format should be "DD/MM/YYYY"

----------------------------------------------


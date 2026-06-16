Step 1

    Project set up
                1. Start new Laravel project
                2. connect to Github repo
                                                                                                    10 mins

----------------------------------------------------------------------------------------------------------------

Step 2

    Documentation
                1. Write out the Understand.md
                2. Write out the Time Estimate.md
                3. Add the Ai Time estimate to the Estimate.md
                4. Write out the Aproach.md
                                                                                                        120 mins

----------------------------------------------------------------------------------------------------------------

Step 3

    Finish Project set up
                1. Install dependencies
                2. Install Sanctum
                3. Install Pest
                4. Confirm API/auth setup
                                                                                                    20 mins

----------------------------------------------------------------------------------------------------------------

Step 4

    Update users table
                1. Add role column to users migration or create a new migration.
                2. Use values like:
                        - client
                        - coach
                3. Set default role, probably client
                                                                                                    15 mins

----------------------------------------------------------------------------------------------------------------

Step 5

    Create check_ins migration
                1. Create migration
                2. Add columns:
                        id
                        user_id
                        checked_in_date
                        notes
                        created_at
                        updated_at
                3. Add foreign key
                4. Add date column
                5. Add nullable notes
                6. Add unique constraint
                                                                                                    20 mins

----------------------------------------------------------------------------------------------------------------

Step 6

    Create CheckIn model
                1. Create model
                2. Add $fillable
                3. Add casts
                4. Add relationship
                5. Add relationship on User
                                                                                                    20 mins

----------------------------------------------------------------------------------------------------------------

Step 7

    Create FormRequest for check-in validation
                1. Create request
                2. Authorize authenticated users
                3. Validate:
                        - checked_in_date
                        - notes
                4. Require date format
                        'checked_in_date' => [
                            'required',
                            'date_format:Y-M-D',
                            'before_or_equal:today',
                        ]
                5. Add duplicate check-in validation
                6. Return validation error if same user already checked in for that date
                                                                                                    40 mins

----------------------------------------------------------------------------------------------------------------

Step 8

    Create streak calculation logic
                1. Decide where streak logic lives
                        example: app/Services/CheckInStreakService.php
                2. Create method
                3. Load user check-in dates
                4. Start from today
                5. If today checked in, count from today backwards
                6. Stop when a missing date is found
                7. Return count
                            Example result:
                                Monday checked in
                                Tuesday checked in
                                Wednesday skipped
                                Thursday checked in

                                Current streak on Thursday = 1
                                                                                                    40 mins

----------------------------------------------------------------------------------------------------------------

Step 9

    Create weekly completion logic
            1. Create method
            2. Choose calendar week Monday–Sunday
            3. Get start of current week & Get end of current week
            4. Check whether user has at least one check-in between those dates
            5. Return true or false
                                                                                                    30 mins            

----------------------------------------------------------------------------------------------------------------

Step 10

    Create controller
            1. Create controller
            2. Add store() method for:
                POST /api/check-ins
            3. Add streak() method for:
                GET /api/check-ins/streak
            4. Create check-in using authenticated user:
                $request->user()->checkIns()->create(...)
            5. Return JSON response.
                                                                                                    45 mins

----------------------------------------------------------------------------------------------------------------

Step 11

    Create coach controller or coach methods
            1. Create controller
            2. php artisan make:controller Api/CoachClientCheckInController
            3. Add history method:
                GET /api/coach/clients/{user}/check-ins
            4. Add streak method:
                GET /api/coach/clients/{user}/streak
                                                                                                    45 mins

----------------------------------------------------------------------------------------------------------------

Step 12

    Add coach role check
            1. Decide implementation
                    - Middleware
            2. Create middleware
            3. Register middleware
            4. Apply middleware to coach routes only
                                                                                                    25 mins

----------------------------------------------------------------------------------------------------------------

Step 13

    Add API routes
            1. In routes/api.php, add authenticated routes
                                                                                                    20 mins

----------------------------------------------------------------------------------------------------------------

Step 14

    Write tests first
            1. Create test file
                    Test cases:
                        - Client can log check-in for today.
                        - Future check-in date is rejected.
                        - Notes over 500 characters are rejected.
                        - Duplicate check-in for same date returns validation error.
                        - User can view current streak.
                        - Streak breaks after skipped day.
                        - Weekly check-in complete is true when user checked in this week.
                        - Weekly check-in complete is false when user has not checked in this week.
                        - Non-coach gets 403 on coach history endpoint.
                        - Coach can view client check-in history.
                        - Coach can view client streak.
                        - Check-ins are treated as calendar dates, not datetimes.
                                                                                                    60 mins

----------------------------------------------------------------------------------------------------------------

Step 15

    Implement code until tests pass
                                                                                                    45 mins

----------------------------------------------------------------------------------------------------------------

Step 16

    Create BEFORE-AFTER.md
                                                                                                    15 mins

----------------------------------------------------------------------------------------------------------------

                                                                                                    9.5 hrs

----------------------------------------------------------------------------------------------------------------             

AI estimate: 10.5–12 hrs total

Step	Your estimate	My estimate	Notes
1. Project setup	10 mins	15 mins	GitHub setup, first commit, Laravel install can take a bit longer.
2. Documentation	120 mins	120 mins	This is fair. UNDERSTANDING, ESTIMATE, and APPROACH need careful writing.
3. Finish project setup	20 mins	30 mins	Sanctum + Pest + SQLite + API auth config can have small issues.
4. Update users table	15 mins	20 mins	Simple, but include migration/test factory updates.
5. Create check_ins migration	20 mins	20 mins	Your estimate is good.
6. Create CheckIn model	20 mins	20 mins	Good estimate.
7. FormRequest validation	40 mins	45 mins	Duplicate validation + future-date rule needs care. Also use Y-m-d, not D-M-Y.
8. Streak calculation logic	40 mins	60 mins	This is the trickiest part because of skipped days and “today vs yesterday” logic.
9. Weekly completion logic	30 mins	35 mins	Mostly simple, but timezone/calendar week needs documenting.
10. Create controller	45 mins	45 mins	Good estimate.
11. Coach controller	45 mins	45 mins	Good estimate.
12. Coach role check	25 mins	30 mins	Middleware registration in Laravel 11/12 can be slightly different.
13. API routes	20 mins	20 mins	Good estimate.
14. Write tests first	60 mins	90 mins	I’d increase this. 12 tests with auth, roles, dates, and streak setup will take time.
15. Implement until tests pass	45 mins	90 mins	This is too low. Debugging tests and date logic usually takes longer.
16. BEFORE-AFTER.md	15 mins	20 mins	Need pasted failing and passing output.
Final review                                                                             
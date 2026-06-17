<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCheckInRequest;
use App\Http\Resources\CheckInResource;
use App\Models\CheckIn;
use App\Services\CheckInStreakService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CheckInController extends Controller
{
    public function __construct(private CheckInStreakService $streakService) {}

    public function store(StoreCheckInRequest $request): JsonResponse
    {
        Gate::authorize('create', CheckIn::class);

        $checkIn = DB::transaction(function () use ($request) {
            $exists = CheckIn::where('user_id', $request->user()->id)
                ->whereDate('checked_in_date', $request->validated('checked_in_date'))
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'checked_in_date' => 'You have already checked in for this date.',
                ]);
            }

            return $request->user()->checkIns()->create([
                'checked_in_date' => $request->validated('checked_in_date'),
                'notes'           => $request->validated('notes'),
            ]);
        });

        Log::info('check_in.created', [
            'user_id'         => $checkIn->user_id,
            'check_in_id'     => $checkIn->id,
            'checked_in_date' => $checkIn->checked_in_date->toDateString(),
        ]);

        return (new CheckInResource($checkIn))
            ->response()
            ->setStatusCode(201);
    }

    public function streak(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'current_streak'           => $this->streakService->currentStreak($user),
            'weekly_check_in_complete' => $this->streakService->weeklyCheckInComplete($user),
        ]);
    }
}

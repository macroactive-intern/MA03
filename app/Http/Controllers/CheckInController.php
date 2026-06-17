<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCheckInRequest;
use App\Services\CheckInStreakService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckInController extends Controller
{
    public function __construct(private CheckInStreakService $streakService) {}

    public function store(StoreCheckInRequest $request): JsonResponse
    {
        $checkIn = $request->user()->checkIns()->create([
            'checked_in_date' => $request->validated('checked_in_date'),
            'notes'           => $request->validated('notes'),
        ]);

        return response()->json(['data' => [
            'id'              => $checkIn->id,
            'checked_in_date' => $checkIn->checked_in_date->toDateString(),
            'notes'           => $checkIn->notes,
        ]], 201);
    }

    public function streak(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'current_streak'          => $this->streakService->currentStreak($user),
            'weekly_check_in_complete' => $this->streakService->weeklyCheckInComplete($user),
        ]);
    }
}

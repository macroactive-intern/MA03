<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CheckInStreakService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoachClientCheckInController extends Controller
{
    public function __construct(private CheckInStreakService $streakService) {}

    public function index(Request $request, User $user): JsonResponse
    {
        if ($user->role !== 'client') {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $checkIns = $user->checkIns()
            ->orderByDesc('checked_in_date')
            ->paginate(15);

        return response()->json([
            'client' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'check_ins' => $checkIns->map(fn ($c) => [
                'id'              => $c->id,
                'checked_in_date' => $c->checked_in_date->toDateString(),
                'notes'           => $c->notes,
            ]),
            'meta' => [
                'current_page' => $checkIns->currentPage(),
                'per_page'     => $checkIns->perPage(),
                'total'        => $checkIns->total(),
            ],
        ]);
    }

    public function streak(Request $request, User $user): JsonResponse
    {
        if ($user->role !== 'client') {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json([
            'current_streak'           => $this->streakService->currentStreak($user),
            'weekly_check_in_complete' => $this->streakService->weeklyCheckInComplete($user),
        ]);
    }
}

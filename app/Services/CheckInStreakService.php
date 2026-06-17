<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CheckInStreakService
{
    public function currentStreak(User $user): int
    {
        $dates = $this->getCheckedInDates($user);

        if ($dates->isEmpty()) {
            return 0;
        }

        $today = Carbon::today()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();

        if ($dates->contains($today)) {
            $start = Carbon::today();
        } elseif ($dates->contains($yesterday)) {
            $start = Carbon::yesterday();
        } else {
            return 0;
        }

        $streak = 0;
        $current = $start->copy();

        while ($dates->contains($current->toDateString())) {
            $streak++;
            $current->subDay();
        }

        return $streak;
    }

    public function weeklyCheckInComplete(User $user): bool
    {
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $endOfWeek   = Carbon::now()->endOfWeek(Carbon::SUNDAY)->toDateString();

        return $user->checkIns()
            ->whereBetween('checked_in_date', [$startOfWeek, $endOfWeek])
            ->exists();
    }

    private function getCheckedInDates(User $user): Collection
    {
        return $user->checkIns()
            ->pluck('checked_in_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString());
    }
}

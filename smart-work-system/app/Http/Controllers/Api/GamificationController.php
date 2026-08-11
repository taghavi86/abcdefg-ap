<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAchievement;
use App\Models\WeeklyRanking;
use App\Models\Challenge;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class GamificationController extends Controller
{
    /**
     * Get user's achievements/badges
     */
    public function getAchievements(): JsonResponse
    {
        $user = auth()->user();

        $achievements = $user->achievements()
            ->with('achievement')
            ->latest('earned_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'achievements' => $achievements->map(function ($userAchievement) {
                    return [
                        'id' => $userAchievement->id,
                        'name' => $userAchievement->achievement->name,
                        'description' => $userAchievement->achievement->description,
                        'icon' => $userAchievement->achievement->icon,
                        'earned_at' => $userAchievement->earned_at->toJalaliDateTime(),
                    ];
                }),
                'total_achievements' => $achievements->count(),
            ],
        ]);
    }

    /**
     * Get weekly rankings
     */
    public function getWeeklyRankings(): JsonResponse
    {
        // Get current week's rankings
        $currentWeek = now()->startOfWeek();
        
        $rankings = WeeklyRanking::where('week_start', $currentWeek)
            ->orderBy('rank')
            ->limit(10)
            ->get();

        $currentUserRank = WeeklyRanking::where('week_start', $currentWeek)
            ->where('user_id', auth()->id())
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'week_start' => $currentWeek->toJalaliDate(),
                'week_end' => $currentWeek->copy()->endOfWeek()->toJalaliDate(),
                'top_users' => $rankings->map(function ($ranking) {
                    return [
                        'rank' => $ranking->rank,
                        'user_name' => $ranking->user->name . ' ' . $ranking->user->family,
                        'score' => $ranking->score,
                        'tasks_completed' => $ranking->tasks_completed,
                        'accuracy_rate' => $ranking->accuracy_rate,
                    ];
                }),
                'my_rank' => $currentUserRank ? [
                    'rank' => $currentUserRank->rank,
                    'score' => $currentUserRank->score,
                    'tasks_completed' => $currentUserRank->tasks_completed,
                ] : null,
            ],
        ]);
    }

    /**
     * Get active challenges
     */
    public function getChallenges(): JsonResponse
    {
        $challenges = Challenge::where('is_active', true)
            ->where('end_time', '>=', now())
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'challenges' => $challenges->map(function ($challenge) {
                    return [
                        'id' => $challenge->id,
                        'title' => $challenge->title,
                        'description' => $challenge->description,
                        'start_time' => $challenge->start_time->toJalaliDateTime(),
                        'end_time' => $challenge->end_time->toJalaliDateTime(),
                        'bonus_multiplier' => $challenge->bonus_multiplier,
                        'is_active' => $challenge->isActive(),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Get motivational message for user
     */
    public function getMotivationalMessage(): JsonResponse
    {
        $user = auth()->user();
        
        // Get user's recent activity
        $todayResponses = UserResponse::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->where('is_assessment', false)
            ->count();

        $yesterdayResponses = UserResponse::where('user_id', $user->id)
            ->whereDate('created_at', today()->subDay())
            ->where('is_assessment', false)
            ->count();

        $message = '';
        
        if ($todayResponses > 0 && $yesterdayResponses > 0) {
            $improvement = (($todayResponses - $yesterdayResponses) / $yesterdayResponses) * 100;
            
            if ($improvement > 0) {
                $message = sprintf(
                    'عالیه! امروز %d درصد بیشتر از دیروز فعالیت کردی. با همین روند ادامه بده!',
                    round($improvement)
                );
            } elseif ($improvement < 0) {
                $message = sprintf(
                    'دیروز بهتر فعالیت کردی. امروز می‌تونی جبران کنی و به رتبه بهتری برسی!',
                    abs(round($improvement))
                );
            } else {
                $message = 'فعالیتت مثل دیروز بوده. سعی کن امروز رکورد جدیدی ثبت کنی!';
            }
        } elseif ($todayResponses > 0) {
            $message = 'آفرین! امروز شروع خوبی داشتی. ادامه بده!';
        } else {
            $message = 'هنوز امروز فعالیت نکردی. بیا شروع کنیم و سکه جمع کنی!';
        }

        // Check if user is close to next level
        if ($user->level && $user->level->name !== 'ممتاز') {
            $nextLevelScore = $user->level->name === 'پایه' ? 70 : 90;
            $pointsNeeded = $nextLevelScore - $user->current_score;
            
            if ($pointsNeeded > 0 && $pointsNeeded <= 20) {
                $message .= " فقط {$pointsNeeded} امتیاز تا سطح بعدی فاصله داری!";
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'message' => $message,
                'today_responses' => $todayResponses,
                'yesterday_responses' => $yesterdayResponses,
            ],
        ]);
    }

    /**
     * Get referral info and rewards
     */
    public function getReferralInfo(): JsonResponse
    {
        $user = auth()->user();

        $referralsCount = $user->referrals()->count();
        $referralReward = config('referral.reward_amount', 100000); // Default 100,000 Tomans

        return response()->json([
            'success' => true,
            'data' => [
                'referral_code' => $user->referral_code,
                'referral_link' => url('/register?ref=' . $user->referral_code),
                'total_referrals' => $referralsCount,
                'reward_per_referral' => $referralReward,
                'total_earned' => $referralsCount * $referralReward,
                'referrals' => $user->referrals()
                    ->select('id', 'name', 'family', 'phone', 'created_at')
                    ->latest('created_at')
                    ->limit(10)
                    ->get()
                    ->map(function ($referral) {
                        return [
                            'name' => $referral->name . ' ' . $referral->family,
                            'registered_at' => $referral->created_at->toJalaliDateTime(),
                        ];
                    }),
            ],
        ]);
    }
}

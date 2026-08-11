<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Models\UserLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Create user
        $user = User::create([
            'name' => $validated['name'],
            'family' => $validated['family'],
            'phone' => $validated['phone'],
            'national_code' => $validated['national_code'],
            'password' => Hash::make($validated['password']),
            'referral_code' => User::generateReferralCode(),
            'referred_by' => $request->has('referral_code') 
                ? User::where('referral_code', $validated['referral_code'])->first()?->id 
                : null,
            'current_score' => 0,
            'total_coins' => 0,
            'verified_coins' => 0,
        ]);

        // Assign default role
        $user->assignRole('user');

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'ثبت‌نام با موفقیت انجام شد',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'family' => $user->family,
                    'phone' => $user->phone,
                    'referral_code' => $user->referral_code,
                    'level' => null,
                    'current_score' => $user->current_score,
                    'total_coins' => $user->total_coins,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Find user by phone
        $user = User::where('phone', $validated['phone'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'شماره تلفن یا رمز عبور اشتباه است',
            ], 401);
        }

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Load level relationship
        $user->load('level');

        return response()->json([
            'success' => true,
            'message' => 'ورود با موفقیت انجام شد',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'family' => $user->family,
                    'phone' => $user->phone,
                    'referral_code' => $user->referral_code,
                    'level' => $user->level ? [
                        'id' => $user->level->id,
                        'name' => $user->level->name,
                        'income_multiplier' => $user->level->income_multiplier,
                    ] : null,
                    'current_score' => $user->current_score,
                    'total_coins' => $user->total_coins,
                    'verified_coins' => $user->verified_coins,
                    'needs_assessment' => !$user->level_id,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Logout user
     */
    public function logout(): JsonResponse
    {
        $user = auth()->user();
        
        // Revoke current token
        $user->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'خروج با موفقیت انجام شد',
        ]);
    }

    /**
     * Get authenticated user profile
     */
    public function profile(): JsonResponse
    {
        $user = auth()->user()->load(['level', 'referrer', 'achievements']);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'family' => $user->family,
                    'phone' => $user->phone,
                    'national_code' => $user->national_code,
                    'referral_code' => $user->referral_code,
                    'level' => $user->level ? [
                        'id' => $user->level->id,
                        'name' => $user->level->name,
                        'income_multiplier' => $user->level->income_multiplier,
                    ] : null,
                    'current_score' => $user->current_score,
                    'total_coins' => $user->total_coins,
                    'verified_coins' => $user->verified_coins,
                    'referrer' => $user->referrer ? [
                        'name' => $user->referrer->name . ' ' . $user->referrer->family,
                    ] : null,
                    'achievements_count' => $user->achievements->count(),
                ],
            ],
        ]);
    }
}

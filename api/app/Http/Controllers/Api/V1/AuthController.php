<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\SendOtpEmailJob;
use App\Jobs\SendOtpSmsJob;
use App\Models\PearlPointsBalance;
use App\Models\Profile;
use App\Models\ProviderWallet;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly OtpService $otpService) {}

    /**
     * POST /v1/auth/send-otp
     * Accepts phone (international format) or email.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'identifier'      => ['required', 'string', 'max:255'],
            'identifier_type' => ['required', Rule::in(['phone', 'email'])],
            'purpose'         => ['sometimes', Rule::in(['auth', 'password_reset', 'phone_verify'])],
        ]);

        $identifier     = $data['identifier'];
        $identifierType = $data['identifier_type'];
        $purpose        = $data['purpose'] ?? 'auth';
        $ip             = $request->ip();

        $otp = $this->otpService->generateAndStore($identifier, $identifierType, $purpose, $ip);

        if ($identifierType === 'phone') {
            SendOtpSmsJob::dispatch($identifier, $otp->code, $purpose);
        } else {
            SendOtpEmailJob::dispatch($identifier, $otp->code, $purpose);
        }

        $isNew = !User::where($identifierType, $identifier)->exists();

        return response()->json([
            'success'    => true,
            'is_new_user' => $isNew,
            'expires_at' => $otp->expires_at->toIso8601String(),
            'message'    => 'OTP sent successfully.',
        ]);
    }

    /**
     * POST /v1/auth/verify-otp
     * Verifies OTP. If user exists, issues token. If new, returns session token
     * that must be used immediately for the /register endpoint.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'identifier'      => ['required', 'string'],
            'identifier_type' => ['required', Rule::in(['phone', 'email'])],
            'code'            => ['required', 'string', 'size:6'],
        ]);

        $this->otpService->verify(
            $data['identifier'],
            $data['identifier_type'],
            $data['code'],
        );

        $user = User::where($data['identifier_type'], $data['identifier'])->first();

        if ($user) {
            $token = $user->createToken('mobile')->plainTextToken;
            return response()->json([
                'success'  => true,
                'is_new'   => false,
                'token'    => $token,
                'user'     => $this->formatUser($user),
            ]);
        }

        return response()->json([
            'success'  => true,
            'is_new'   => true,
            'message'  => 'OTP verified. Please complete registration.',
        ]);
    }

    /**
     * POST /v1/auth/register
     * Called only after a successful OTP verification for new users.
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'identifier'      => ['required', 'string'],
            'identifier_type' => ['required', Rule::in(['phone', 'email'])],
            'full_name'       => ['required', 'string', 'max:120'],
            'email'           => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone'           => ['sometimes', 'nullable', 'string', 'max:20', Rule::unique('users', 'phone')],
            'referral_code'   => ['sometimes', 'nullable', 'string', 'max:20'],
            'role'            => ['sometimes', Rule::in(['customer', 'provider_stays', 'provider_vehicles', 'provider_taxi'])],
        ]);

        if (!$this->otpService->wasRecentlyVerified($data['identifier'], $data['identifier_type'])) {
            throw ValidationException::withMessages([
                'code' => ['OTP session expired. Please verify your OTP again.'],
            ]);
        }

        $user = DB::transaction(function () use ($data) {
            $identifierType = $data['identifier_type'];
            $identifier     = $data['identifier'];

            $userPayload = [
                'role'      => $data['role'] ?? 'customer',
                'is_active' => true,
            ];
            if ($identifierType === 'phone') {
                $userPayload['phone'] = $identifier;
                $userPayload['phone_verified_at'] = now();
                if (!empty($data['email'])) {
                    $userPayload['email'] = $data['email'];
                }
            } else {
                $userPayload['email'] = $identifier;
                $userPayload['email_verified_at'] = now();
                if (!empty($data['phone'])) {
                    $userPayload['phone'] = $data['phone'];
                }
            }

            $user = User::create($userPayload);

            Profile::create([
                'user_id'      => $user->id,
                'full_name'    => $data['full_name'],
                'referral_code' => strtoupper('GR-' . substr(str_replace('-', '', $user->id), 0, 8)),
                'account_status' => 'active',
            ]);

            PearlPointsBalance::create(['user_id' => $user->id]);
            ProviderWallet::create(['user_id' => $user->id]);

            // Handle referral
            if (!empty($data['referral_code'])) {
                $referrer = Profile::where('referral_code', strtoupper($data['referral_code']))->first();
                if ($referrer) {
                    $user->update(['referred_by' => $referrer->user_id]);
                }
            }

            return $user;
        });

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => $this->formatUser($user),
        ], 201);
    }

    /**
     * POST /v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'message' => 'Logged out.']);
    }

    /**
     * GET /v1/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('profile', 'pearlPoints', 'wallet');
        return response()->json(['success' => true, 'user' => $this->formatUser($user)]);
    }

    private function formatUser(User $user): array
    {
        $profile    = $user->profile ?? null;
        $points     = $user->pearlPoints ?? null;
        $wallet     = $user->wallet ?? null;

        return [
            'id'                => $user->id,
            'email'             => $user->email,
            'phone'             => $user->phone,
            'role'              => $user->role,
            'is_active'         => $user->is_active,
            'email_verified_at' => $user->email_verified_at,
            'phone_verified_at' => $user->phone_verified_at,
            'profile' => $profile ? [
                'full_name'      => $profile->full_name,
                'avatar_url'     => $profile->avatar_url,
                'account_status' => $profile->account_status,
                'referral_code'  => $profile->referral_code,
            ] : null,
            'pearl_points' => $points ? [
                'balance'  => $points->balance,
                'tier'     => $points->tier,
            ] : null,
            'wallet' => $wallet ? [
                'balance'  => $wallet->balance,
                'on_hold'  => $wallet->on_hold,
            ] : null,
        ];
    }
}

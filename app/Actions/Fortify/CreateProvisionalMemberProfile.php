<?php

namespace App\Actions\Fortify;

use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CreateProvisionalMemberProfile
{
    private const CREATE_RETRY_MAX_ATTEMPTS = 5;

    private const CODE_GENERATION_MAX_ATTEMPTS = 100;

    /**
     * Create a provisional member profile for a verified user.
     */
    public function createFor(User $user): MemberProfile
    {
        /** @var MemberProfile|null $existingProfile */
        $existingProfile = $user->memberProfile()->first();

        if ($existingProfile instanceof MemberProfile) {
            return $existingProfile;
        }

        for ($attempt = 0; $attempt < self::CREATE_RETRY_MAX_ATTEMPTS; $attempt++) {
            try {
                return MemberProfile::query()->create([
                    'user_id' => $user->id,
                    'code' => $this->generateUniqueCode($user->id),
                    'member_status' => MemberProfile::STATUS_PROVISIONAL,
                ]);
            } catch (UniqueConstraintViolationException $exception) {
                /** @var MemberProfile|null $profile */
                $profile = $user->memberProfile()->first();

                if ($profile instanceof MemberProfile) {
                    return $profile;
                }

                Log::warning('Retrying provisional member profile creation after unique constraint violation.', [
                    'user_id' => $user->id,
                    'attempt' => $attempt + 1,
                    'max_attempts' => self::CREATE_RETRY_MAX_ATTEMPTS,
                    'exception' => $exception,
                ]);
            }
        }

        Log::error('Failed to create provisional member profile after retries.', [
            'user_id' => $user->id,
            'max_attempts' => self::CREATE_RETRY_MAX_ATTEMPTS,
        ]);

        throw new RuntimeException('Failed to create provisional member profile.');
    }

    private function generateUniqueCode(int $userId): string
    {
        for ($attempt = 0; $attempt < self::CODE_GENERATION_MAX_ATTEMPTS; $attempt++) {
            $code = sprintf('MB%06d', random_int(0, 999999));

            if (! MemberProfile::query()->where('code', $code)->exists()) {
                return $code;
            }
        }

        Log::error('Failed to generate a unique provisional member code within attempt limit.', [
            'user_id' => $userId,
            'max_attempts' => self::CODE_GENERATION_MAX_ATTEMPTS,
        ]);

        throw new RuntimeException('Failed to generate a unique provisional member code.');
    }
}

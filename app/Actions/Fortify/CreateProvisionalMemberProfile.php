<?php

namespace App\Actions\Fortify;

use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use RuntimeException;

class CreateProvisionalMemberProfile
{
    /**
     * Create a provisional member profile for a verified user.
     */
    public function createFor(User $user): MemberProfile
    {
        /** @var MemberProfile|null $existingProfile */
        $existingProfile = MemberProfile::query()->where('user_id', $user->id)->first();

        if ($existingProfile instanceof MemberProfile) {
            return $existingProfile;
        }

        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                return MemberProfile::query()->create([
                    'user_id' => $user->id,
                    'code' => $this->generateUniqueCode(),
                    'member_status' => MemberProfile::STATUS_PROVISIONAL,
                ]);
            } catch (UniqueConstraintViolationException $exception) {
                /** @var MemberProfile|null $profile */
                $profile = MemberProfile::query()->where('user_id', $user->id)->first();

                if ($profile instanceof MemberProfile) {
                    return $profile;
                }
            }
        }

        throw new RuntimeException('Failed to create provisional member profile.');
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = sprintf('MB%06d', random_int(0, 999999));
        } while (MemberProfile::query()->where('code', $code)->exists());

        return $code;
    }
}

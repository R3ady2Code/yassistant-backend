<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\AI\Models\BotSettings;
use App\Domain\Identity\DataObjects\AuthResultData;
use App\Domain\Identity\DataObjects\GoogleLoginData;
use App\Domain\Identity\Models\Tenant;
use App\Domain\Identity\Models\User;
use App\Domain\Scenario\Actions\SeedDefaultScenariosAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

final class GoogleLoginAction extends AbstractAction
{
    public function __construct(
        private readonly SeedDefaultScenariosAction $seedScenarios,
    ) {
        parent::__construct();
    }

    public function handle(GoogleLoginData $data): AuthResultData
    {
        $googleUser = Socialite::driver('google')->stateless()->userFromToken($data->token);

        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            $token = $user->createToken('auth')->plainTextToken;

            return new AuthResultData(user: $user, token: $token);
        }

        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
            ]);

            $token = $user->createToken('auth')->plainTextToken;

            return new AuthResultData(user: $user, token: $token);
        }

        return DB::transaction(function () use ($googleUser): AuthResultData {
            $name = $googleUser->getName() ?? $googleUser->getEmail();

            $tenant = Tenant::create([
                'name' => $name,
                'slug' => Str::slug($name) . '-' . Str::random(6),
                'is_active' => true,
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $name,
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
            ]);

            BotSettings::create([
                'tenant_id' => $tenant->id,
            ]);

            $this->seedScenarios->handle($tenant);

            $token = $user->createToken('auth')->plainTextToken;

            return new AuthResultData(user: $user, token: $token);
        });
    }
}

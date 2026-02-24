<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\AI\Models\BotSettings;
use App\Domain\Identity\DataObjects\AuthResultData;
use App\Domain\Identity\DataObjects\RegisterTenantData;
use App\Domain\Identity\Enums\TenantStatus;
use App\Domain\Identity\Models\Tenant;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RegisterTenantAction extends AbstractAction
{
    public function __construct()
    {
        parent::__construct();
    }

    public function handle(RegisterTenantData $data): AuthResultData
    {
        return DB::transaction(function () use ($data): AuthResultData {
            $tenant = Tenant::create([
                'name' => $data->tenantName,
                'slug' => Str::slug($data->tenantName).'-'.Str::random(6),
                'status' => TenantStatus::Active,
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $data->name,
                'email' => $data->email,
                'password' => $data->password,
            ]);

            BotSettings::create([
                'tenant_id' => $tenant->id,
            ]);

            $token = $user->createToken('auth')->plainTextToken;

            return new AuthResultData(user: $user, token: $token);
        });
    }
}

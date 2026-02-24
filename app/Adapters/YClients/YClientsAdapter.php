<?php

declare(strict_types=1);

namespace App\Adapters\YClients;

use App\Domain\Booking\Contracts\YClientsContract;
use Illuminate\Support\Facades\Http;
use RuntimeException;

readonly class YClientsAdapter implements YClientsContract
{
    private const string BASE_URL = 'https://api.yclients.com/api/v1';

    public function getBranches(string $apiToken): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiToken}",
            'Accept' => 'application/vnd.yclients.v2+json',
        ])->get(self::BASE_URL.'/companies');

        if ($response->failed()) {
            throw new RuntimeException(
                "YClients API request failed: {$response->status()} {$response->body()}"
            );
        }

        return $response->json('data', []);
    }

    public function getServices(string $apiToken, int $branchId): array
    {
        throw new RuntimeException('Not implemented');
    }

    public function getStaff(string $apiToken, int $branchId): array
    {
        throw new RuntimeException('Not implemented');
    }

    public function getAvailableSlots(string $apiToken, int $branchId, int $staffId, int $serviceId, string $date): array
    {
        throw new RuntimeException('Not implemented');
    }

    public function createBooking(string $apiToken, int $branchId, array $bookingData): array
    {
        throw new RuntimeException('Not implemented');
    }
}

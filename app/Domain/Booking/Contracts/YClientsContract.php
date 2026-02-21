<?php

declare(strict_types=1);

namespace App\Domain\Booking\Contracts;

interface YClientsContract
{
    public function getBranches(string $apiToken): array;

    public function getServices(string $apiToken, int $branchId): array;

    public function getStaff(string $apiToken, int $branchId): array;

    public function getAvailableSlots(string $apiToken, int $branchId, int $staffId, int $serviceId, string $date): array;

    public function createBooking(string $apiToken, int $branchId, array $bookingData): array;
}

<?php

declare(strict_types=1);

namespace App\Domain\Scenario\Actions;

use App\Abstracts\AbstractAction;
use App\Domain\Identity\Models\Tenant;
use App\Domain\Scenario\Enums\ScenarioStatus;
use App\Domain\Scenario\Enums\ScenarioType;
use App\Domain\Scenario\Models\Scenario;

final class SeedDefaultScenariosAction extends AbstractAction
{
    public function handle(Tenant $tenant): void
    {
        Scenario::create([
            'tenant_id' => $tenant->id,
            'name' => 'FAQ',
            'slug' => 'faq',
            'type' => ScenarioType::Default,
            'schema' => $this->faqSchema(),
            'status' => ScenarioStatus::Active,
        ]);

        Scenario::create([
            'tenant_id' => $tenant->id,
            'name' => 'YClients Pipeline',
            'slug' => 'yclients_pipeline',
            'type' => ScenarioType::Default,
            'schema' => $this->yclientsPipelineSchema(),
            'status' => ScenarioStatus::Active,
        ]);
    }

    private function faqSchema(): array
    {
        return [
            'nodes' => [
                ['id' => 'start', 'type' => 'start', 'data' => ['label' => 'Start']],
                ['id' => 'check_faq', 'type' => 'check_faq', 'data' => ['label' => 'Check FAQ']],
                ['id' => 'respond', 'type' => 'respond', 'data' => ['label' => 'Respond with FAQ']],
                ['id' => 'fallback', 'type' => 'fallback', 'data' => ['label' => 'AI Fallback']],
            ],
            'edges' => [
                ['source' => 'start', 'target' => 'check_faq'],
                ['source' => 'check_faq', 'target' => 'respond', 'label' => 'found'],
                ['source' => 'check_faq', 'target' => 'fallback', 'label' => 'not_found'],
            ],
            'start_node' => 'start',
        ];
    }

    private function yclientsPipelineSchema(): array
    {
        return [
            'nodes' => [
                ['id' => 'start', 'type' => 'start', 'data' => ['label' => 'Start']],
                ['id' => 'collect_service', 'type' => 'collect', 'data' => ['label' => 'Select Service']],
                ['id' => 'collect_master', 'type' => 'collect', 'data' => ['label' => 'Select Master']],
                ['id' => 'collect_slot', 'type' => 'collect', 'data' => ['label' => 'Select Time Slot']],
                ['id' => 'confirm', 'type' => 'confirm', 'data' => ['label' => 'Confirm Booking']],
                ['id' => 'book', 'type' => 'action', 'data' => ['label' => 'Create Booking']],
                ['id' => 'done', 'type' => 'end', 'data' => ['label' => 'Done']],
            ],
            'edges' => [
                ['source' => 'start', 'target' => 'collect_service'],
                ['source' => 'collect_service', 'target' => 'collect_master'],
                ['source' => 'collect_master', 'target' => 'collect_slot'],
                ['source' => 'collect_slot', 'target' => 'confirm'],
                ['source' => 'confirm', 'target' => 'book', 'label' => 'yes'],
                ['source' => 'confirm', 'target' => 'collect_service', 'label' => 'no'],
                ['source' => 'book', 'target' => 'done'],
            ],
            'start_node' => 'start',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\BookingFlows\Controllers;

use App\Abstracts\AbstractController;
use App\Abstracts\Empty204Resource;
use App\Domain\Booking\Actions\CreateBookingFlowAction;
use App\Domain\Booking\Actions\DeleteBookingFlowAction;
use App\Domain\Booking\Actions\ReorderFlowStepsAction;
use App\Domain\Booking\Actions\ToggleBookingFlowAction;
use App\Domain\Booking\Actions\UpdateBookingFlowAction;
use App\Domain\Booking\DataObjects\BookingFlowData;
use App\Domain\Booking\DataObjects\FlowStepData;
use App\Domain\Booking\Enums\AnswerType;
use App\Domain\Booking\Models\BookingFlow;
use App\Http\BookingFlows\Requests\CreateBookingFlowRequest;
use App\Http\BookingFlows\Requests\ReorderFlowStepsRequest;
use App\Http\BookingFlows\Requests\UpdateBookingFlowRequest;
use App\Http\BookingFlows\Resources\BookingFlowResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class BookingFlowController extends AbstractController
{
    public function index(): AnonymousResourceCollection
    {
        $flows = BookingFlow::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->with('steps')
            ->orderByDesc('created_at')
            ->get();

        return BookingFlowResource::collection($flows);
    }

    public function show(BookingFlow $bookingFlow): BookingFlowResource
    {
        $bookingFlow->load('steps');

        return BookingFlowResource::make($bookingFlow);
    }

    public function store(
        CreateBookingFlowRequest $request,
        CreateBookingFlowAction $action,
    ): BookingFlowResource {
        $data = new BookingFlowData(
            tenantId: auth()->user()->tenant_id,
            name: $request->validated('name'),
            askStaff: $request->validated('ask_staff', false),
            isActive: $request->validated('is_active', true),
            steps: $this->mapSteps($request->validated('steps', [])),
        );

        $flow = $action->handle($data);

        return BookingFlowResource::make($flow);
    }

    public function update(
        BookingFlow $bookingFlow,
        UpdateBookingFlowRequest $request,
        UpdateBookingFlowAction $action,
    ): BookingFlowResource {
        $data = new BookingFlowData(
            tenantId: $bookingFlow->tenant_id,
            name: $request->validated('name'),
            askStaff: $request->validated('ask_staff', false),
            isActive: $request->validated('is_active', true),
            steps: $this->mapSteps($request->validated('steps', [])),
        );

        $flow = $action->handle($bookingFlow, $data);

        return BookingFlowResource::make($flow);
    }

    public function destroy(
        BookingFlow $bookingFlow,
        DeleteBookingFlowAction $action,
    ): Empty204Resource {
        $action->handle($bookingFlow);

        return Empty204Resource::make(null);
    }

    public function reorder(
        BookingFlow $bookingFlow,
        ReorderFlowStepsRequest $request,
        ReorderFlowStepsAction $action,
    ): Empty204Resource {
        $action->handle($bookingFlow, $request->validated('ids'));

        return Empty204Resource::make(null);
    }

    public function toggle(
        BookingFlow $bookingFlow,
        ToggleBookingFlowAction $action,
    ): BookingFlowResource {
        $flow = $action->handle($bookingFlow);

        return BookingFlowResource::make($flow);
    }

    /**
     * @return FlowStepData[]
     */
    private function mapSteps(array $steps): array
    {
        return array_map(
            fn (array $step, int $index) => new FlowStepData(
                questionText: $step['question_text'],
                answerType: AnswerType::from($step['answer_type']),
                isRequired: $step['is_required'] ?? true,
                config: $step['config'] ?? [],
                sortOrder: $step['sort_order'] ?? $index,
            ),
            $steps,
            array_keys($steps),
        );
    }
}

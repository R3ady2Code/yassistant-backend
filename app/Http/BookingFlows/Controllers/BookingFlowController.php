<?php

declare(strict_types=1);

namespace App\Http\BookingFlows\Controllers;

use App\Abstracts\AbstractController;
use App\Domain\Booking\Actions\CreateBookingFlowAction;
use App\Domain\Booking\Actions\DeleteBookingFlowAction;
use App\Domain\Booking\Actions\ReorderFlowStepsAction;
use App\Domain\Booking\Actions\UpdateBookingFlowAction;
use App\Domain\Booking\DataObjects\BookingFlowData;
use App\Domain\Booking\DataObjects\FlowStepData;
use App\Domain\Booking\Enums\AnswerType;
use App\Domain\Booking\Models\BookingFlow;
use App\Http\BookingFlows\Requests\CreateBookingFlowRequest;
use App\Http\BookingFlows\Requests\ReorderFlowStepsRequest;
use App\Http\BookingFlows\Requests\UpdateBookingFlowRequest;
use App\Http\BookingFlows\Resources\BookingFlowResource;
use Illuminate\Http\JsonResponse;

final class BookingFlowController extends AbstractController
{
    public function index(): JsonResponse
    {
        $flows = BookingFlow::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->with('steps')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(BookingFlowResource::collection($flows));
    }

    public function show(BookingFlow $bookingFlow): JsonResponse
    {
        $bookingFlow->load('steps');

        return response()->json(new BookingFlowResource($bookingFlow));
    }

    public function store(
        CreateBookingFlowRequest $request,
        CreateBookingFlowAction $action,
    ): JsonResponse {
        $data = new BookingFlowData(
            tenantId: auth()->user()->tenant_id,
            name: $request->validated('name'),
            yclientsServiceId: $request->validated('yclients_service_id'),
            yclientsServiceName: $request->validated('yclients_service_name'),
            yclientsBranchId: $request->validated('yclients_branch_id'),
            askStaff: $request->validated('ask_staff', false),
            isActive: $request->validated('is_active', true),
            steps: $this->mapSteps($request->validated('steps', [])),
        );

        $flow = $action->handle($data);

        return response()->json(new BookingFlowResource($flow), 201);
    }

    public function update(
        BookingFlow $bookingFlow,
        UpdateBookingFlowRequest $request,
        UpdateBookingFlowAction $action,
    ): JsonResponse {
        $data = new BookingFlowData(
            tenantId: $bookingFlow->tenant_id,
            name: $request->validated('name'),
            yclientsServiceId: $request->validated('yclients_service_id'),
            yclientsServiceName: $request->validated('yclients_service_name'),
            yclientsBranchId: $request->validated('yclients_branch_id'),
            askStaff: $request->validated('ask_staff', false),
            isActive: $request->validated('is_active', true),
            steps: $this->mapSteps($request->validated('steps', [])),
        );

        $flow = $action->handle($bookingFlow, $data);

        return response()->json(new BookingFlowResource($flow));
    }

    public function destroy(
        BookingFlow $bookingFlow,
        DeleteBookingFlowAction $action,
    ): JsonResponse {
        $action->handle($bookingFlow);

        return response()->json(null, 204);
    }

    public function reorder(
        BookingFlow $bookingFlow,
        ReorderFlowStepsRequest $request,
        ReorderFlowStepsAction $action,
    ): JsonResponse {
        $action->handle($bookingFlow, $request->validated('ids'));

        return response()->json(null, 204);
    }

    public function toggle(BookingFlow $bookingFlow): JsonResponse
    {
        $bookingFlow->update(['is_active' => ! $bookingFlow->is_active]);

        return response()->json(new BookingFlowResource($bookingFlow->load('steps')));
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

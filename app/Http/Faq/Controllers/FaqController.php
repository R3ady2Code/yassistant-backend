<?php

declare(strict_types=1);

namespace App\Http\Faq\Controllers;

use App\Abstracts\AbstractController;
use App\Domain\AI\Actions\CreateFaqEntryAction;
use App\Domain\AI\Actions\DeleteFaqEntryAction;
use App\Domain\AI\Actions\ReorderFaqEntriesAction;
use App\Domain\AI\Actions\UpdateFaqEntryAction;
use App\Domain\AI\DataObjects\CreateFaqEntryData;
use App\Domain\AI\DataObjects\UpdateFaqEntryData;
use App\Domain\AI\Models\FaqEntry;
use App\Http\Faq\Requests\CreateFaqRequest;
use App\Http\Faq\Requests\ReorderFaqRequest;
use App\Http\Faq\Requests\UpdateFaqRequest;
use App\Http\Faq\Resources\FaqEntryResource;
use Illuminate\Http\JsonResponse;

final class FaqController extends AbstractController
{
    public function index(): JsonResponse
    {
        $entries = FaqEntry::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('sort_order')
            ->get();

        return response()->json(FaqEntryResource::collection($entries));
    }

    public function store(
        CreateFaqRequest $request,
        CreateFaqEntryAction $action,
    ): JsonResponse {
        $data = new CreateFaqEntryData(
            tenantId: auth()->user()->tenant_id,
            question: $request->validated('question'),
            answer: $request->validated('answer'),
        );

        $entry = $action->handle($data);

        return response()->json(new FaqEntryResource($entry), 201);
    }

    public function update(
        FaqEntry $faqEntry,
        UpdateFaqRequest $request,
        UpdateFaqEntryAction $action,
    ): JsonResponse {
        $data = new UpdateFaqEntryData(
            question: $request->validated('question'),
            answer: $request->validated('answer'),
            isActive: $request->validated('is_active'),
        );

        $result = $action->handle($faqEntry, $data);

        return response()->json(new FaqEntryResource($result));
    }

    public function destroy(
        FaqEntry $faqEntry,
        DeleteFaqEntryAction $action,
    ): JsonResponse {
        $action->handle($faqEntry);

        return response()->json(null, 204);
    }

    public function reorder(
        ReorderFaqRequest $request,
        ReorderFaqEntriesAction $action,
    ): JsonResponse {
        $action->handle(
            auth()->user()->tenant_id,
            $request->validated('ids'),
        );

        return response()->json(null, 204);
    }
}

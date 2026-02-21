<?php

declare(strict_types=1);

namespace App\Http\Channels;

use App\Abstracts\AbstractController;
use App\Domain\Channel\Actions\ActivateChannelAction;
use App\Domain\Channel\Actions\CreateChannelAction;
use App\Domain\Channel\Actions\DeactivateChannelAction;
use App\Domain\Channel\Actions\DeleteChannelAction;
use App\Domain\Channel\Actions\UpdateChannelAction;
use App\Domain\Channel\DataObjects\CreateChannelData;
use App\Domain\Channel\DataObjects\UpdateChannelData;
use App\Domain\Channel\Enums\ChannelType;
use App\Domain\Channel\Models\Channel;
use App\Http\Channels\Requests\CreateChannelRequest;
use App\Http\Channels\Requests\UpdateChannelRequest;
use App\Http\Channels\Resources\ChannelResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ChannelController extends AbstractController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $channels = Channel::where('tenant_id', $request->user()->tenant_id)
            ->orderByDesc('created_at')
            ->get();

        return ChannelResource::collection($channels);
    }

    public function store(
        CreateChannelRequest $request,
        CreateChannelAction $action,
    ): JsonResponse {
        $data = new CreateChannelData(
            tenantId: $request->user()->tenant_id,
            type: ChannelType::from($request->validated('type')),
            name: $request->validated('name'),
            botToken: $request->validated('bot_token'),
        );

        $channel = $action->handle($data);

        return response()->json(new ChannelResource($channel), 201);
    }

    public function show(Request $request, Channel $channel): JsonResponse
    {
        $this->authorizeChannel($request, $channel);

        return response()->json(new ChannelResource($channel));
    }

    public function update(
        UpdateChannelRequest $request,
        Channel $channel,
        UpdateChannelAction $action,
    ): JsonResponse {
        $this->authorizeChannel($request, $channel);

        $data = new UpdateChannelData(
            name: $request->validated('name'),
        );

        $channel = $action->handle($channel, $data);

        return response()->json(new ChannelResource($channel));
    }

    public function destroy(
        Request $request,
        Channel $channel,
        DeleteChannelAction $action,
    ): JsonResponse {
        $this->authorizeChannel($request, $channel);

        $action->handle($channel);

        return response()->json(null, 204);
    }

    public function activate(
        Request $request,
        Channel $channel,
        ActivateChannelAction $action,
    ): JsonResponse {
        $this->authorizeChannel($request, $channel);

        $channel = $action->handle($channel);

        return response()->json(new ChannelResource($channel));
    }

    public function deactivate(
        Request $request,
        Channel $channel,
        DeactivateChannelAction $action,
    ): JsonResponse {
        $this->authorizeChannel($request, $channel);

        $channel = $action->handle($channel);

        return response()->json(new ChannelResource($channel));
    }

    private function authorizeChannel(Request $request, Channel $channel): void
    {
        abort_unless($channel->tenant_id === $request->user()->tenant_id, 403);
    }
}

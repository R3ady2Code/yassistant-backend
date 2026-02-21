<?php

declare(strict_types=1);

namespace App\Http\Channels\Controllers;

use App\Abstracts\AbstractController;
use App\Abstracts\Empty204Resource;
use App\Domain\Channel\Actions\ActivateChannelAction;
use App\Domain\Channel\Actions\CreateChannelAction;
use App\Domain\Channel\Actions\DeactivateChannelAction;
use App\Domain\Channel\Actions\DeleteChannelAction;
use App\Domain\Channel\Actions\UpdateChannelAction;
use App\Domain\Channel\DataObjects\CreateChannelData;
use App\Domain\Channel\Enums\ChannelType;
use App\Domain\Channel\Models\Channel;
use App\Http\Channels\Requests\CreateChannelRequest;
use App\Http\Channels\Requests\UpdateChannelRequest;
use App\Http\Channels\Resources\ChannelResource;
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
        CreateChannelAction $createChannelAction,
    ): ChannelResource {
        $data = new CreateChannelData(
            tenantId: $request->user()->tenant_id,
            type: ChannelType::from($request->type),
            name: $request->name,
            botToken: $request->bot_token,
        );

        $channel = $createChannelAction->handle($data);

        return ChannelResource::make($channel);
    }

    public function show(Channel $channel): ChannelResource
    {
        return ChannelResource::make($channel);
    }

    public function update(
        UpdateChannelRequest $request,
        Channel $channel,
        UpdateChannelAction $updateChannelAction,
    ): ChannelResource {
        $channel = $updateChannelAction->handle($channel, $request->name);

        return ChannelResource::make($channel);
    }

    public function destroy(
        Channel $channel,
        DeleteChannelAction $deleteChannelAction,
    ): Empty204Resource {
        $deleteChannelAction->handle($channel);

        return Empty204Resource::make(null);
    }

    public function activate(
        Channel $channel,
        ActivateChannelAction $activateChannelAction,
    ): ChannelResource {
        $channel = $activateChannelAction->handle($channel);

        return ChannelResource::make($channel);
    }

    public function deactivate(
        Channel $channel,
        DeactivateChannelAction $deactivateChannelAction,
    ): ChannelResource {
        $channel = $deactivateChannelAction->handle($channel);

        return ChannelResource::make($channel);
    }
}

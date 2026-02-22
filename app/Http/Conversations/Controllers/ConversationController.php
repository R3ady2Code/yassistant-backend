<?php

declare(strict_types=1);

namespace App\Http\Conversations\Controllers;

use App\Abstracts\AbstractController;
use App\Abstracts\Empty204Resource;
use App\Domain\Conversation\Actions\ReleaseConversationAction;
use App\Domain\Conversation\Actions\SendAdminMessageAction;
use App\Domain\Conversation\Actions\TakeoverConversationAction;
use App\Domain\Conversation\Actions\ToggleAIModeAction;
use App\Domain\Conversation\Enums\ConversationStatus;
use App\Domain\Conversation\Models\Conversation;
use App\Http\Conversations\Requests\SendMessageRequest;
use App\Http\Conversations\Resources\ConversationResource;
use App\Http\Conversations\Resources\MessageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ConversationController extends AbstractController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Conversation::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->with('client')
            ->orderByDesc('last_message_at');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('mode')) {
            $query->where('mode', $request->input('mode'));
        }

        return ConversationResource::collection(
            $query->paginate($request->integer('per_page', 20)),
        );
    }

    public function show(Conversation $conversation): ConversationResource
    {
        $conversation->load('client');

        return ConversationResource::make($conversation);
    }

    public function messages(Request $request, Conversation $conversation): AnonymousResourceCollection
    {
        $messages = $conversation->messages()
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 50));

        return MessageResource::collection($messages);
    }

    public function takeover(
        Conversation $conversation,
        TakeoverConversationAction $action,
    ): JsonResponse {
        $action->handle($conversation);

        return response()->json(['mode' => $conversation->fresh()->mode->value]);
    }

    public function release(
        Conversation $conversation,
        ReleaseConversationAction $action,
    ): JsonResponse {
        $action->handle($conversation);

        return response()->json(['mode' => $conversation->fresh()->mode->value]);
    }

    public function toggleAi(
        Conversation $conversation,
        ToggleAIModeAction $action,
    ): JsonResponse {
        $newMode = $action->handle($conversation);

        return response()->json(['mode' => $newMode->value]);
    }

    public function send(
        SendMessageRequest $request,
        Conversation $conversation,
        SendAdminMessageAction $action,
    ): MessageResource {
        $message = $action->handle($conversation, $request->validated('text'));

        return MessageResource::make($message);
    }

    public function close(Conversation $conversation): Empty204Resource
    {
        $conversation->update(['status' => ConversationStatus::Closed]);

        return Empty204Resource::make(null);
    }
}

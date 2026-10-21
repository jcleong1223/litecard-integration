<?php

namespace App\Http\Controllers;

use App\Actions\AcceptMemberEvent;
use App\Http\Requests\AcceptMemberEventRequest;
use App\Traits\ResponseFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberEventController extends Controller
{
    use ResponseFormatter;

    public function __construct() {

    }


    public function store(AcceptMemberEventRequest $request, AcceptMemberEvent $acceptMemberEvent): JsonResponse
    {
        $result = $acceptMemberEvent->handle($request->validated());

        $event = $result['event'];
        $delivery = $result['delivery'];

        if ($result['duplicate']) {
            return $this->duplicateResponse(
                $event->id,
                $delivery?->id ?? '',
                $delivery?->status ?? 'queued'
            );
        }

        return $this->acceptedResponse($event->id, $delivery->id, $delivery->status);
    }
}

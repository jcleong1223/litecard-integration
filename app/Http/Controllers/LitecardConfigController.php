<?php

namespace App\Http\Controllers;

use App\Actions\AcceptLitecardConfigEvent;
use App\Http\Requests\AcceptLitecardConfigEventRequest;
use App\Http\Requests\AcceptMemberEventRequest;
use App\Traits\ResponseFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LitecardConfigController extends Controller
{
    use ResponseFormatter;

    public function __construct() {}

    public function store(AcceptLitecardConfigEventRequest $request, AcceptLitecardConfigEvent $acceptLitecardConfigEvent): JsonResponse
    {
        $result = $acceptLitecardConfigEvent->handle($request->validated());

        $status = $result['status'];
        $message = $result['message'];

        return $this->successResponse($result, $message, $status);
    }
}

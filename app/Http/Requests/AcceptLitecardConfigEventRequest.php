<?php

namespace App\Http\Requests;


use App\Enums\ErrorCode;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

/**
 * Validation for POST /api/internal/v1/webhook-events (spec §31).
 * Renders failures in the §9.6 error envelope.
 */
class AcceptLitecardConfigEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /****** Source ******/
            'source' => 'required|array',
            'source.system' => 'required|string|max:50',
            'source.instance_id' => 'required|string|max:50',
            'source.event_id' => 'required|string|max:100',
            'source.occurred_at' => 'required|date',

            /****** Tenant, merchant and outlet ******/
            'tenant' => 'required|array',
            'tenant.id' => 'required|string|max:50',
            'tenant.code' => 'nullable|string|max:50',
            'outlet' => 'required|array',
            'outlet.outlet_id' => 'required|string',
            'outlet.outlet_name' => 'nullable|string|max:100',

            /****** Event ******/
            'event' => 'required|array',
            'event.type' => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_]*(\.[a-z0-9_]+)*$/'],
            'event.api_version' => 'required|string|max:20',
            'event.aggregate_type' => 'required|string|max:30',
            'event.aggregate_id' => 'required|string|max:100',
            'event.resource_version' => 'nullable|integer|min:0',

            'payload' => 'required|array',
            'payload.config'          => 'required|array',
            'payload.config.username' => 'required|string|max:255',
            'payload.config.password' => 'required|string|max:255',
        ];
    }

    /**
     * Render validation failures in the spec §9.6 envelope.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => ErrorCode::VALIDATION_ERROR->name,
                'message' => 'The webhook event request is invalid.',
                'fields' => $validator->errors(),
            ],
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}

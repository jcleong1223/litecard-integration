<?php

namespace App\Traits;

use App\Enums\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

trait ResponseFormatter
{

    /**
     * Accepted response
     *
     * @param mixed $data
     * @return JsonResponse
     */
    protected function acceptedResponse(string $eventId, string $deliveryId, string $status = 'queued'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'event_id' => $eventId,
            'delivery_id' => $deliveryId,
            'status' => $status
        ], Response::HTTP_ACCEPTED);
    }


    /**
     * Ingestion API 200 OK Duplicate Response (Section 9.5)
     */
    protected function duplicateResponse(string $eventId, string $deliveryId, string $status): JsonResponse
    {
        return response()->json([
            'success' => true,
            'duplicate' => true,
            'event_id' => $eventId,
            'delivery_id' => $deliveryId,
            'status' => $status
        ], Response::HTTP_OK);
    }

    /**
     * Error response
     *
     * @param string $message
     * @param mixed $errors
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function errorResponse(bool $status = false, string $errorCode = ErrorCode::CONNECTION_REFUSED->name, string $message = 'Error', int $statusCode = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        $errorArr = [
            'code' => $errorCode,
            'message' => $message
        ];
        return response()->json([
            'success' => $status,
            'error' => $errorArr
        ], $statusCode);
    }

    /**
     * Validation error response
     *
     * @param mixed $errors
     * @return JsonResponse
     */
    protected function validationErrorResponse($fieldErrors): JsonResponse
    {
        $errorConstructor = [
            'code' => ErrorCode::VALIDATION_ERROR->name,
            'message' => 'The webhook event request is invalid.',
            'fields' => $fieldErrors
        ];

        return response()->json([
            'success' => false,
            'error' => $errorConstructor
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Not found response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function notFoundResponse(bool $status = false, string $message = 'Resource not found'): JsonResponse
    {
        $errArr = [
            'code' => ErrorCode::RESOURCE_NOT_FOUND->name,
            'message' => $message
        ];

        return response()->json([
            'success' => $status,
            'error' => $errArr
        ], Response::HTTP_NOT_FOUND);
    }

    /**
     * Unauthorized response
     *
     * @param string $message
     * @param string $code Specific error code (INVALID_SIGNATURE, INVALID_CLIENT, etc.)
     * @return JsonResponse
     */
    protected function unauthorizedResponse(string $message = 'The request signature is invalid.', string $code = ErrorCode::INVALID_SIGNATURE->name): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Forbidden response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'FORBIDDEN',
                'message' => $message
            ]
        ], Response::HTTP_FORBIDDEN);
    }
}

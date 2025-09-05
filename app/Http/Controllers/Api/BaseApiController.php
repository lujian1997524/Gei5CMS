<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

abstract class BaseApiController extends Controller
{
    protected int $defaultPerPage = 15;
    protected int $maxPerPage = 100;
    protected string $apiVersion = 'v1';

    public function successResponse($data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'version' => $this->apiVersion,
            'timestamp' => now()->toISOString(),
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        do_action('api.response.before', $response, $status);

        return response()->json($response, $status);
    }

    public function errorResponse(string $message = 'Error', int $status = 400, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'version' => $this->apiVersion,
            'timestamp' => now()->toISOString(),
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        do_action('api.error', $response, $status);

        return response()->json($response, $status);
    }

    public function validationErrorResponse(array $errors): JsonResponse
    {
        return $this->errorResponse('Validation failed', 422, $errors);
    }

    public function notFoundResponse(string $resource = 'Resource'): JsonResponse
    {
        return $this->errorResponse("{$resource} not found", 404);
    }

    public function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    public function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    public function paginatedResponse($query, Request $request, string $message = 'Success'): JsonResponse
    {
        $perPage = $this->getPerPage($request);
        $paginated = $query->paginate($perPage);

        $data = [
            'items' => $paginated->items(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
                'has_more_pages' => $paginated->hasMorePages(),
            ],
            'links' => [
                'first' => $paginated->url(1),
                'last' => $paginated->url($paginated->lastPage()),
                'prev' => $paginated->previousPageUrl(),
                'next' => $paginated->nextPageUrl(),
            ],
        ];

        return $this->successResponse($data, $message);
    }

    public function validateRequest(Request $request, array $rules, array $messages = []): array
    {
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }

    protected function getPerPage(Request $request): int
    {
        $perPage = (int) $request->get('per_page', $this->defaultPerPage);
        return min($perPage, $this->maxPerPage);
    }

    protected function applyFilters($query, Request $request, array $allowedFilters = []): mixed
    {
        foreach ($allowedFilters as $field => $type) {
            $value = $request->get($field);
            
            if ($value !== null && $value !== '') {
                switch ($type) {
                    case 'exact':
                        $query->where($field, $value);
                        break;
                    case 'like':
                        $query->where($field, 'like', "%{$value}%");
                        break;
                    case 'date':
                        $query->whereDate($field, $value);
                        break;
                    case 'range':
                        if (is_array($value) && count($value) === 2) {
                            $query->whereBetween($field, $value);
                        }
                        break;
                    case 'in':
                        if (is_array($value)) {
                            $query->whereIn($field, $value);
                        } else {
                            $query->whereIn($field, explode(',', $value));
                        }
                        break;
                }
            }
        }

        return $query;
    }

    protected function applySorting($query, Request $request, array $allowedSorts = []): mixed
    {
        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'asc');

        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
            $sortOrder = 'asc';
        }

        return $query->orderBy($sortBy, $sortOrder);
    }

    protected function logApiRequest(Request $request, string $action): void
    {
        \Illuminate\Support\Facades\Log::info("API Request: {$action}", [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
        ]);
    }

    protected function transformResource($resource, string $transformer = null): array
    {
        if (!$transformer) {
            return $resource->toArray();
        }

        return app($transformer)->transform($resource);
    }

    protected function cacheResponse(string $key, $data, int $minutes = 60): mixed
    {
        return \Illuminate\Support\Facades\Cache::remember($key, $minutes * 60, function () use ($data) {
            return $data;
        });
    }
}
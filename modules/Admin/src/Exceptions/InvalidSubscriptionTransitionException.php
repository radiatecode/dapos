<?php

namespace DA\Admin\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class InvalidSubscriptionTransitionException extends RuntimeException implements ShouldntReport
{
    public function __construct(string $message = 'This subscription change is not allowed.')
    {
        parent::__construct($message);
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => $this->getMessage(),
            ], 422);
        }

        flash($this->getMessage())->error()->important();

        return back();
    }
}

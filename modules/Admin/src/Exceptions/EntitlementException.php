<?php

namespace DA\Admin\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

abstract class EntitlementException extends RuntimeException implements ShouldntReport
{
    public function status(): int
    {
        return 403;
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => $this->getMessage(),
            ], $this->status());
        }

        flash($this->getMessage())->error()->important();

        return back();
    }
}

<?php
namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

trait ApiResponse
{
    protected function success($data = [], string $message = '', int $code = 200): Response|JsonResponse|RedirectResponse
    {
        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => $data
            ], $code);
        }

        return back()->with('success', $message);
    }

    protected function error(string $message, int $code = 400): Response|JsonResponse|RedirectResponse
    {
        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => $message,
            ], $code);
        }

        return back()->with('error', $message);
    }

    protected function inertiaRender(string $component, array $props = []): Response
    {
        return Inertia::render($component, $props);
    }
}
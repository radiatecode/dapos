<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

if (! function_exists('to_json')) {
    function to_json($data = [], $status = 200, array $headers = [], $options = 0): JsonResponse
    {
        return response()->json($data, $status, $headers, $options);
    }
}

if (! function_exists('toJsonWithSaveFlashMessage')) {
    function toJsonWithSaveFlashMessage($data, $status = 200, array $headers = [], $options = 0): JsonResponse
    {
        $message = is_array($data)
            ? $data['message']
            : 'You have successfully saved the data.';

        flash($message)->success()->important();

        return response()->json($data, $status, $headers, $options);
    }
}

if (! function_exists('toJsonWithUpdateFlashMessage')) {
    function toJsonWithUpdateFlashMessage($data, $status = 200, array $headers = [], $options = 0): JsonResponse
    {
        $message = is_array($data)
            ? $data['message']
            : 'You have successfully updated the data.';

        flash($message)->success()->important();

        return response()->json($data, $status, $headers, $options);
    }
}

if (! function_exists('toJsonWithDeleteFlashMessage')) {
    function toJsonWithDeleteFlashMessage($data, $status = 200, array $headers = [], $options = 0): JsonResponse
    {
        $message = is_array($data)
            ? $data['message']
            : 'You have successfully deleted the data.';

        flash($message)->success()->important();

        return response()->json($data, $status, $headers, $options);
    }
}

if (! function_exists('toBackWithSaveMessage')) {
    function toBackWithSaveMessage(string $message = null): RedirectResponse
    {
        $message = $message ?: 'You have successfully saved the data.';

        flash($message)->success()->important();

        return back();
    }
}

if (! function_exists('toBackWithUpdatedMessage')) {
    function toBackWithUpdatedMessage(string $message = null): RedirectResponse
    {
        $message = $message ?: 'You have successfully updated the data.';

        flash($message)->success()->important();

        return back();
    }
}

if (! function_exists('toBackWithDeletedMessage')) {
    function toBackWithDeletedMessage(string $message = null): RedirectResponse
    {
        $message = $message ?: 'You have successfully deleted the data.';

        flash($message)->success()->important();

        return back();
    }
}

if (! function_exists('toUrlWithSaveMessage')) {
    function toUrlWithSaveMessage(string $url, string $message = null): RedirectResponse
    {
        $message = $message ?: 'You have successfully saved the data.';

        flash($message)->success()->important();

        return redirect($url);
    }
}

if (! function_exists('toUrlWithUpdatedMessage')) {
    function toUrlWithUpdatedMessage(string $url, string $message = null): RedirectResponse
    {
        $message = $message ?: 'You have successfully updated the data.';

        flash($message)->success()->important();

        return redirect($url);
    }
}

if (! function_exists('toUrlWithDeletedMessage')) {
    function toUrlWithDeletedMessage(string $url, string $message = null): RedirectResponse
    {
        $message = $message ?: 'You have successfully deleted the data.';

        flash($message)->success()->important();

        return redirect($url);
    }
}
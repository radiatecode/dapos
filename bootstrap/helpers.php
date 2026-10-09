<?php

use Carbon\Carbon;
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
    function toBackWithSaveMessage(?string $message = null): RedirectResponse
    {
        $message = $message ?: 'You have successfully saved the data.';

        flash($message)->success()->important();

        return back();
    }
}

if (! function_exists('toBackWithUpdatedMessage')) {
    function toBackWithUpdatedMessage(?string $message = null): RedirectResponse
    {
        $message = $message ?: 'You have successfully updated the data.';

        flash($message)->success()->important();

        return back();
    }
}

if (! function_exists('toBackWithDeletedMessage')) {
    function toBackWithDeletedMessage(?string $message = null): RedirectResponse
    {
        $message = $message ?: 'You have successfully deleted the data.';

        flash($message)->success()->important();

        return back();
    }
}

if (! function_exists('toUrlWithSaveMessage')) {
    function toUrlWithSaveMessage(string $url, ?string $message = null): RedirectResponse
    {
        $message = $message ?: 'You have successfully saved the data.';

        flash($message)->success()->important();

        return redirect($url);
    }
}

if (! function_exists('toUrlWithUpdatedMessage')) {
    function toUrlWithUpdatedMessage(string $url, ?string $message = null): RedirectResponse
    {
        $message = $message ?: 'You have successfully updated the data.';

        flash($message)->success()->important();

        return redirect($url);
    }
}

if (! function_exists('toUrlWithDeletedMessage')) {
    function toUrlWithDeletedMessage(string $url, ?string $message = null): RedirectResponse
    {
        $message = $message ?: 'You have successfully deleted the data.';

        flash($message)->success()->important();

        return redirect($url);
    }
}

if (! function_exists('_time')) {
    /**
     * Convert a stored timestamp (UTC, per APP_TIMEZONE) into the display timezone.
     *
     * Note the second argument of Carbon::parse() sets the timezone the input is
     * *read as*, so passing it here relabels a UTC time instead of shifting it.
     * The conversion has to happen with setTimezone().
     */
    function _time(DateTimeInterface|string|null $time, ?string $timezone = null): ?Carbon
    {
        if ($time === null || $time === '') {
            return null;
        }

        return Carbon::parse($time)->setTimezone($timezone ?: 'Asia/Dhaka');
    }
}

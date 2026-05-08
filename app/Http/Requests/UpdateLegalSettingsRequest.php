<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class UpdateLegalSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $raw = $this->getContent();
        Log::info('=== LEGAL SETTINGS DEBUG START ===');
        Log::info('Content-Length header: ' . $this->headers->get('Content-Length'));
        Log::info('Actual content length: ' . strlen($raw));
        Log::info('bin2hex of content: ' . bin2hex($raw));
        Log::info('json_decode result: ' . json_encode(json_decode($raw, true)));
        Log::info('json_last_error: ' . json_last_error_msg());

        $trimmed = preg_replace('/\s*\}\s*$/', '}', $raw);
        $decoded = json_decode($trimmed, true);
        Log::info('After trimming, json_decode result: ' . json_encode($decoded));

        Log::info('=== LEGAL SETTINGS DEBUG END ===');
    }

    public function rules(): array
    {
        return [
            'module_slug' => ['required', 'string'],
            'answers' => ['required', 'array'],
        ];
    }
}

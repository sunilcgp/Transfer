<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreTransferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['from_email' => ['required', 'email', 'max:255'], 'to_emails' => ['required', 'array', 'min:1', 'max:50'], 'to_emails.*' => ['required', 'distinct', 'email', 'max:255'], 'message' => ['nullable', 'string', 'max:5000'], 'expires_in' => ['required', 'integer', 'in:1,2,7,30'], 'scheduled_at' => [Rule::prohibitedIf(fn (): bool => ! $this->user()), 'nullable', 'date', 'after:now', 'before_or_equal:'.now()->addDays(90)->toIso8601String()], 'files' => ['required', 'array', 'min:1', 'max:20'], 'files.*' => [File::types(['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'txt', 'mp4', 'mov'])->max('2gb')]];
    }
}

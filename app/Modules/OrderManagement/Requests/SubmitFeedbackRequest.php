<?php

namespace App\Modules\OrderManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubmitFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
            'tags'   => 'nullable|array|max:5',
            'tags.*' => ['string', 'max:50', 'regex:/^[a-z_]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'A star rating is required.',
            'rating.min'      => 'Rating must be at least 1 star.',
            'rating.max'      => 'Rating cannot exceed 5 stars.',
            'review.max'      => 'Your review cannot exceed 1000 characters.',
            'tags.max'        => 'You can select a maximum of 5 feedback tags.',
            'tags.*.regex'    => 'Tags must contain only lowercase letters and underscores.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'The provided data is invalid.',
            'errors'  => $validator->errors()->toArray(),
            'data'    => [],
        ], 422));
    }
}

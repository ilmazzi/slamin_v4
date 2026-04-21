<?php

namespace App\Http\Requests\Api\V1\Report;

use Domain\Report\Enums\ReportStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                ReportStatus::InReview->value,
                ReportStatus::Resolved->value,
                ReportStatus::Dismissed->value,
            ])],
            'resolution_note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

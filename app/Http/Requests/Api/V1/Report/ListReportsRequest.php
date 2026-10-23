<?php

namespace App\Http\Requests\Api\V1\Report;

use Domain\Report\Enums\ReportStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListReportsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'status' => ['nullable', Rule::in([
                ReportStatus::Open->value,
                ReportStatus::InReview->value,
                ReportStatus::Resolved->value,
                ReportStatus::Dismissed->value,
            ])],
            'reportable_type' => ['nullable', Rule::in(['poem', 'article', 'comment', 'event', 'group', 'media', 'forum_post'])],
        ];
    }
}

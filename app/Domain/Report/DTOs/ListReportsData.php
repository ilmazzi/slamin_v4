<?php

namespace Domain\Report\DTOs;

use App\Http\Requests\Api\V1\Report\ListReportsRequest;

class ListReportsData
{
    public function __construct(
        public readonly int $perPage,
        public readonly ?string $status,
        public readonly ?string $reportableType,
    ) {
    }

    public static function fromRequest(ListReportsRequest $request): self
    {
        return new self(
            perPage: (int) $request->validated('per_page', 10),
            status: $request->validated('status'),
            reportableType: $request->validated('reportable_type'),
        );
    }
}

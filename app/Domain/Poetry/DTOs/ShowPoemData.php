<?php

namespace Domain\Poetry\DTOs;

use App\Http\Requests\Api\V1\Poetry\ShowPoemRequest;

class ShowPoemData
{
    public function __construct(
        //
    ) {
    }

    public static function fromRequest(ShowPoemRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            //
        );
    }
}

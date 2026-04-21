<?php

namespace Domain\Poetry\DTOs;

use App\Http\Requests\Api\V1\Poetry\LikePoemRequest;

class LikePoemData
{
    public function __construct(
        //
    ) {
    }

    public static function fromRequest(LikePoemRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            //
        );
    }
}

<?php

namespace Domain\Poetry\DTOs;

use App\Http\Requests\Api\V1\Poetry\DeletePoemRequest;

class DeletePoemData
{
    public function __construct(
        //
    ) {
    }

    public static function fromRequest(DeletePoemRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            //
        );
    }
}

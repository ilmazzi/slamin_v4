<?php

namespace Domain\Poetry\DTOs;

use App\Http\Requests\Api\V1\Poetry\ListMyPoemsRequest;

class ListMyPoemsData
{
    public function __construct(
        //
    ) {
    }

    public static function fromRequest(ListMyPoemsRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            //
        );
    }
}

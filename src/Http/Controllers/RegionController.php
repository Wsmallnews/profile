<?php

namespace Wsmallnews\Profile\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Wsmallnews\Profile\Services\RegionService;

/**
 * 区划级联数据公开接口（静态数据，无租户语义）
 */
class RegionController
{
    public function __construct(protected RegionService $regions) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country' => ['nullable', 'string', 'size:2'],
            'parents' => ['nullable', 'string'], // 逗号分隔的已选父级链
        ]);

        $parents = array_values(array_filter(explode(',', (string) ($validated['parents'] ?? ''))));

        $divisions = $this->regions->divisions(
            countryCode: $validated['country'] ?? null,
            parents: $parents,
        );

        return response()->json(['data' => $divisions]);
    }
}

<?php

namespace App\Services;

use App\Models\CashMovement;

class CashLinkService
{
    public function sync(
        string $shopId,
        string $sourceType,
        int $sourceId,
        array $payload
    ): CashMovement {
        $existing = CashMovement::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->first();

        $data = array_merge($payload, [
            'shop_id' => $shopId,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ]);

        if ($existing) {
            $existing->update($data);
            return $existing->fresh();
        }

        return CashMovement::withoutGlobalScopes()->create($data);
    }

    public function forget(string $shopId, string $sourceType, int $sourceId): void
    {
        CashMovement::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->delete();
    }
}

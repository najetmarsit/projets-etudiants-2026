<?php

namespace App\Services;

use App\Models\PatientBillableItem;
use Illuminate\Support\Carbon;

class BillingItemService
{
    /**
     * Crée une ligne facturable avec prix auto depuis config.
     */
    public function createAutoPriced(
        int $patientId,
        string $kind,
        string $label,
        ?int $createdByUserId = null,
        ?Carbon $performedAt = null,
        ?string $sourceType = null,
        ?int $sourceId = null
    ): PatientBillableItem {
        $amount = $this->priceForKind($kind);

        return PatientBillableItem::create([
            'patient_id' => $patientId,
            'kind' => $kind,
            'label' => $label,
            'amount' => $amount,
            'performed_at' => $performedAt ?? now(),
            'created_by_user_id' => $createdByUserId,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ]);
    }

    public function priceForKind(string $kind): float
    {
        $items = (array) config('clinova_pricing.items', []);
        $raw = $items[$kind] ?? 0;
        return round((float) $raw, 2);
    }
}


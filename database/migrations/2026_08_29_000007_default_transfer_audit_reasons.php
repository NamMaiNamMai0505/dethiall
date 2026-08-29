<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Inventory\Models\InventoryAuditLog;

return new class extends Migration
{
    public function up(): void
    {
        InventoryAuditLog::where('action', 'TRANSFER')->get()->each(function (InventoryAuditLog $log): void {
            $details = $log->details ?: [];
            if (empty($details['reason'])) {
                $details['reason'] = 'Điều động';
                $log->update(['details' => $details]);
            }
        });
    }

    public function down(): void
    {
        // Keep the explicit reason on historical transfer records.
    }
};

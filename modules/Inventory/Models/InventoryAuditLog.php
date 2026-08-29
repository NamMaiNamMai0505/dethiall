<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryAuditLog extends Model
{
    protected $table = 'inventory_audit_logs';

    protected $fillable = ['user_id', 'action', 'entity_type', 'entity_id', 'details'];

    protected $casts = ['details' => 'array'];

    protected static function booted()
    {
        static::addGlobalScope('final-proposal-events', function ($query): void {
            $query->where(function ($builder): void {
                $builder->where('entity_type', '!=', 'proposal')->orWhere('action', 'COMPLETED');
            });
        });
        static::creating(function (self $log) {
            if ($log->entity_type === 'proposal') {
                if (in_array($log->action, ['CREATE', 'APPROVED', 'REJECTED'], true)) {
                    return false;
                }
                $proposal = InventoryProposal::withoutGlobalScopes()->find($log->entity_id);
                $log->action = match ($proposal?->type) {
                    'RECALL' => 'Thu hồi / trả về kho',
                    'REPAIR' => 'Sửa chữa',
                    'LIQUIDATION' => 'Thanh lý',
                    default => $log->action,
                };
            }
            $log->details = self::enrichDetails(
                $log->entity_type,
                $log->entity_id,
                (array) $log->details,
                $log->action
            );
        });
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function resolveDetails(): self
    {
        if ($this->entity_type === 'proposal' && $this->action === 'COMPLETED') {
            $proposal = InventoryProposal::withoutGlobalScopes()->find($this->entity_id);
            $this->action = match ($proposal?->type) {
                'RECALL' => 'Thu hồi / trả về kho',
                'REPAIR' => 'Sửa chữa',
                'LIQUIDATION' => 'Thanh lý',
                default => $this->action,
            };
        }
        $this->details = self::enrichDetails($this->entity_type, $this->entity_id, (array) $this->details, $this->action);
        return $this;
    }

    private static function enrichDetails(?string $entityType, $entityId, array $details, ?string $action): array
    {
        $record = null;

        if ($entityType === 'material' && $entityId) {
            $record = InventoryMaterial::withoutGlobalScopes()->find($entityId);
        } elseif ($entityType === 'asset' && $entityId) {
            $record = InventoryAsset::withoutGlobalScopes()->with(['classroom.building', 'holdingUnit', 'material'])->find($entityId);
        } elseif ($entityType === 'proposal' && $entityId) {
            $proposal = InventoryProposal::withoutGlobalScopes()->with(['items.asset.classroom.building', 'items.material', 'warehouse'])->find($entityId);
            $item = $proposal?->items?->first();
            $record = $item?->asset ?: $item?->material;
            $details += [
                'proposal_type' => $proposal?->type,
                'proposal_title' => $proposal?->title,
                'proposal_status' => $proposal?->status,
                'proposal_reason' => $proposal?->description,
                'warehouse_id' => $proposal?->warehouse_id,
                'warehouse_name' => $proposal?->warehouse?->name,
                'warehouse_code' => $proposal?->warehouse?->code,
            ];
            if ($item) {
                $details += ['quantity' => $item->quantity, 'classroom_id' => $item->from_classroom_id];
            }
        }

        if ($record) {
            $isAsset = $record instanceof InventoryAsset;
            $details += [
                'asset_code' => $isAsset ? $record->asset_code : $record->code,
                'code' => $isAsset ? $record->asset_code : $record->code,
                'name' => $record->name,
                'unit' => $record->unit,
                'current_quantity' => $record->quantity,
            ];

            if ($isAsset) {
                $details += [
                    'classroom_id' => $record->classroom_id,
                    'classroom_name' => $record->classroom?->name,
                    'building_name' => $record->classroom?->building?->name,
                    'holding_unit_name' => $record->holdingUnit?->name,
                ];
            } else {
                $details += [
                    'classroom_id' => $record->classroom_id,
                    'classroom_name' => $record->classroom?->name,
                    'building_name' => $record->building?->name,
                ];
            }
        }

        if ($entityType === 'proposal' && (empty($details['reason']) || $details['reason'] === 'Cập nhật vật tư')) {
            $details['reason'] = $details['proposal_reason'] ?: match ($details['proposal_type'] ?? null) {
                'RECALL' => 'Thu hồi / trả về kho',
                'LIQUIDATION' => 'Thanh lý vật tư',
                'REPAIR' => 'Đề xuất sửa chữa',
                default => 'Xử lý đề xuất vật tư',
            };
        }

        $details['quantity'] ??= $details['change'] ?? $details['after'] ?? 0;
        $details['reason'] ??= $details['note'] ?? $details['decision_note'] ?? match ($action) {
            'RECALL' => 'Thu hồi trả về kho',
            'TRANSFER' => 'Điều động sang phòng đích',
            'ADJUST' => 'Điều chỉnh vật tư',
            default => 'Cập nhật vật tư',
        };

        if (!empty($details['warehouse_id'])) {
            $warehouse = InventoryWarehouse::withoutGlobalScopes()->find($details['warehouse_id']);
            $details['warehouse_name'] = $warehouse?->name;
            $details['warehouse_code'] = $warehouse?->code;
        }

        return $details;
    }
}

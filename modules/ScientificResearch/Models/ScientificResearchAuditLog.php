<?php

namespace Modules\ScientificResearch\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScientificResearchAuditLog extends Model
{
    protected $fillable = ['action', 'target_type', 'target_id', 'title', 'changes', 'created_by'];

    protected $casts = [
        'changes' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getActionLabelAttribute(): string
    {
        return self::actionLabels()[$this->action] ?? 'Cập nhật thông tin';
    }

    public function getDetailLinesAttribute(): array
    {
        $changes = is_array($this->changes) ? $this->changes : [];

        if ($this->action === 'registration.status_updated') {
            return array_values(array_filter([
                isset($changes['from'], $changes['to'])
                    ? 'Trạng thái: '.$this->statusLabel($changes['from']).' -> '.$this->statusLabel($changes['to'])
                    : null,
                filled($changes['review_note'] ?? null) ? 'Ý kiến xử lý: '.$changes['review_note'] : null,
            ]));
        }

        if ($this->action === 'registration.extension_requested') {
            return array_values(array_filter([
                filled($changes['extension_requested_until'] ?? null) ? 'Xin gia hạn đến: '.$changes['extension_requested_until'] : null,
                filled($changes['extension_request_note'] ?? null) ? 'Lý do: '.$changes['extension_request_note'] : null,
            ]));
        }

        if ($this->action === 'registration.extension_reviewed') {
            return array_values(array_filter([
                isset($changes['decision']) ? 'Kết quả duyệt gia hạn: '.$this->decisionLabel($changes['decision']) : null,
                filled($changes['extended_until'] ?? null) ? 'Gia hạn đến: '.$changes['extended_until'] : null,
                filled($changes['extension_review_note'] ?? null) ? 'Ý kiến duyệt: '.$changes['extension_review_note'] : null,
            ]));
        }

        if ($this->action === 'registration.revision_submitted') {
            return array_values(array_filter([
                filled($changes['revision_response_note'] ?? null) ? 'Nội dung bổ sung: '.$changes['revision_response_note'] : null,
                isset($changes['status']) ? 'Trạng thái sau khi gửi: '.$this->statusLabel($changes['status']) : null,
            ]));
        }

        if ($this->action === 'registration.synced_standard_hours') {
            return array_values(array_filter([
                isset($changes['standard_hours_research_record_id'])
                    ? 'Mã kê khai Giờ chuẩn GV: #'.$changes['standard_hours_research_record_id']
                    : null,
            ]));
        }

        if ($this->action === 'plan.task_progress_updated') {
            return array_values(array_filter([
                filled($changes['name'] ?? null) ? 'Người thực hiện: '.$changes['name'] : null,
                filled($changes['task'] ?? null) ? 'Nhiệm vụ: '.$changes['task'] : null,
                isset($changes['progress_percent']) ? 'Tiến độ: '.(int) $changes['progress_percent'].'%' : null,
                filled($changes['progress_note'] ?? null) ? 'Ghi chú: '.$changes['progress_note'] : null,
            ]));
        }

        $lines = [];
        foreach ($changes as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            $label = self::changeLabels()[$key] ?? null;
            if (! $label) {
                continue;
            }

            if (in_array($key, ['status', 'extension_previous_status'], true)) {
                $value = $this->statusLabel($value);
            } elseif ($key === 'decision') {
                $value = $this->decisionLabel($value);
            }

            $lines[] = $label.': '.$this->displayValue($value);
        }

        return $lines;
    }

    private static function actionLabels(): array
    {
        return [
            'report.export.csv' => 'Xuất báo cáo CSV',
            'report.export.excel' => 'Xuất báo cáo Excel',
            'report.export.word' => 'Xuất báo cáo Word',
            'announcement.created' => 'Tạo thông báo',
            'announcement.updated' => 'Cập nhật thông báo',
            'announcement.deleted' => 'Xóa thông báo',
            'registration.created' => 'Gửi đăng ký đề tài',
            'registration.updated' => 'Cập nhật thông tin đề tài',
            'registration.deleted' => 'Xóa đề tài',
            'registration.status_updated' => 'Xử lý trạng thái đề tài',
            'registration.revision_submitted' => 'Gửi hồ sơ bổ sung',
            'registration.extension_requested' => 'Gửi yêu cầu gia hạn',
            'registration.extension_reviewed' => 'Duyệt yêu cầu gia hạn',
            'registration.synced_standard_hours' => 'Đồng bộ sang Giờ chuẩn GV',
            'result.created' => 'Nộp kết quả nghiên cứu',
            'result.updated' => 'Cập nhật kết quả nghiên cứu',
            'result.deleted' => 'Xóa kết quả nghiên cứu',
            'staff.created' => 'Tạo hồ sơ cán bộ',
            'staff.updated' => 'Cập nhật hồ sơ cán bộ',
            'staff.deleted' => 'Xóa hồ sơ cán bộ',
            'plan.created' => 'Tạo kế hoạch',
            'plan.updated' => 'Cập nhật kế hoạch',
            'plan.task_progress_updated' => 'Cập nhật tiến độ nhiệm vụ',
            'plan.deleted' => 'Xóa kế hoạch',
            'council.created' => 'Tạo hội đồng',
            'council.updated' => 'Cập nhật hội đồng',
            'council.deleted' => 'Xóa hội đồng',
            'council.member_created' => 'Thêm thành viên hội đồng',
            'council.member_deleted' => 'Xóa thành viên hội đồng',
            'funding.created' => 'Thêm kinh phí',
            'funding.updated' => 'Cập nhật kinh phí',
            'funding.deleted' => 'Xóa kinh phí',
            'product.created' => 'Thêm sản phẩm',
            'product.updated' => 'Cập nhật sản phẩm',
            'product.deleted' => 'Xóa sản phẩm',
            'repository.created' => 'Thêm tài liệu kho dữ liệu',
            'repository.updated' => 'Cập nhật tài liệu kho dữ liệu',
            'repository.deleted' => 'Xóa tài liệu kho dữ liệu',
        ];
    }

    private static function changeLabels(): array
    {
        return [
            'project_code' => 'Mã đề tài',
            'status' => 'Trạng thái',
            'title' => 'Tiêu đề',
            'topic' => 'Chủ đề/lĩnh vực',
            'content' => 'Nội dung',
            'academic_year' => 'Năm học',
            'implementation_year' => 'Năm thực hiện',
            'duration_years' => 'Số năm thực hiện',
            'start_date' => 'Từ ngày',
            'end_date' => 'Đến ngày',
            'budget' => 'Kinh phí',
            'product_quantity' => 'Số lượng sản phẩm',
            'participant_count' => 'Tổng số người tham gia',
            'lead_contribution_percent' => 'Tỷ lệ chủ nhiệm',
            'registration_id' => 'Mã hồ sơ',
            'review_note' => 'Ghi chú duyệt',
            'summary' => 'Tóm tắt',
            'completed_on' => 'Ngày hoàn thành',
            'progress_percent' => 'Tiến độ',
            'extended_until' => 'Gia hạn đến',
            'extension_previous_status' => 'Trạng thái trước khi xin gia hạn',
            'item_name' => 'Tên khoản mục',
            'amount' => 'Số tiền',
            'note' => 'Ghi chú',
            'name' => 'Tên',
            'full_name' => 'Họ tên',
            'unit_name' => 'Đơn vị',
            'type' => 'Loại',
            'description' => 'Mô tả',
            'document_type' => 'Loại tài liệu',
            'keywords' => 'Từ khóa',
        ];
    }

    private function statusLabel($status): string
    {
        return [
            'DRAFT' => 'Bản nháp',
            'PROPOSED' => 'Đề xuất nhiệm vụ',
            'SUBMITTED' => 'Chờ chỉ huy đơn vị duyệt',
            'UNIT_APPROVED' => 'Chỉ huy đơn vị đã duyệt',
            'UNDER_REVIEW' => 'Đang thẩm định',
            'NEEDS_REVISION' => 'Cần bổ sung',
            'APPROVED' => 'Đã duyệt',
            'IN_PROGRESS' => 'Đang thực hiện',
            'EXTENSION_REQUESTED' => 'Xin gia hạn',
            'ACCEPTANCE_PENDING' => 'Chờ nghiệm thu',
            'COMPLETED' => 'Hoàn thành',
            'REJECTED' => 'Từ chối',
            'ACCEPTED' => 'Đã nghiệm thu',
        ][$status] ?? (string) $status;
    }

    private function decisionLabel($decision): string
    {
        return [
            'approve' => 'Duyệt',
            'reject' => 'Từ chối',
        ][$decision] ?? (string) $decision;
    }

    private function displayValue($value): string
    {
        if ($value === null || $value === '') {
            return 'Chưa nhập';
        }

        if (is_bool($value)) {
            return $value ? 'Có' : 'Không';
        }

        return (string) $value;
    }
}

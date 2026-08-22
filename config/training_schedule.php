<?php

/**
 * Cấu hình nghiệp vụ chung của Lịch đào tạo.
 */
return [

    /**
     * Số giờ ân hạn sau khi một ngày học kết thúc mà vẫn còn sửa được lịch
     * ngày đó — cho phép chỉnh sửa sai sót phát hiện muộn mà không phải chờ
     * ngoại lệ. Quá thời gian này, chỉ Super Admin sửa được (có ghi nhật ký).
     *
     * Khoá tính theo NGÀY, không theo từng tiết — chưa có bảng giờ tiết nên
     * không so sánh được theo giờ học thực tế.
     */
    'edit_grace_hours' => env('SCHEDULE_DETAIL_EDIT_GRACE_HOURS', 48),

];

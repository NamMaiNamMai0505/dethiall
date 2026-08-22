<?php

/**
 * Cấu hình nghiệp vụ Quản lý điểm / Tổng kết TN.
 * Điều chỉnh danh sách chức danh để map đúng BGH / CN PDOT.
 */
return [

    /**
     * Ban giám hiệu — phê duyệt cuối (không gồm Chủ nhiệm khoa).
     * So khớp không phân biệt hoa thường, chứa một phần tên chức danh.
     */
    'board_position_needles' => [
        'hiệu trưởng',
        'hieu truong',
        'giám đốc',
        'giam doc',
        'chính ủy',
        'chinh uy',
        'ban giám hiệu',
        'ban giam hieu',
    ],

    /** Loại trừ khỏi BGH nếu tên chức danh chứa các chuỗi này */
    'board_position_exclude' => [
        'khoa',
        'bộ môn',
        'bo mon',
    ],

    /**
     * Chủ nhiệm / lãnh đạo Phòng Đào tạo — sửa điểm, tính KQ, ghi DL (không duyệt cuối).
     */
    'pdot_director_position_needles' => [
        'trưởng phòng',
        'truong phong',
        'chủ nhiệm phòng',
        'chu nhiem phong',
        'phó trưởng phòng đào tạo',
        'pho truong phong dao tao',
    ],

    /**
     * Quyền Spatie bổ sung (nếu gán tay trên user/role):
     * - grades.approve → BGH
     * - grades.manage → CN PDOT (đã dùng sẵn)
     */
    'permission_board' => 'grades.approve',

    /**
     * Ngưỡng xếp loại khi «Tính kết quả» (điểm hệ 10).
     * Ưu tiên cột final (điểm thi) nếu có; không thì TB có trọng số.
     */
    'result_thresholds' => [
        'gioi' => 8.0,       // >= → Giỏi
        'tot_nghiep' => 5.0, // >= → Tốt nghiệp
        // < tot_nghiep → Không TN
    ],

    /**
     * Trọng số TB môn khi không có điểm thi (final).
     * Khóa = grade_columns.code
     */
    'column_weights' => [
        'oral_15' => 0.1,
        'period_1' => 0.2,
        'midterm' => 0.3,
        'final' => 0.4,
    ],

    /**
     * Header Excel được nhận khi import điểm (không dấu / có dấu, lower).
     * Map → grade_columns.code hoặc student field.
     */
    'import_headers' => [
        'ma' => 'student_code',
        'mã' => 'student_code',
        'mã sv' => 'student_code',
        'mã hv' => 'student_code',
        'mssv' => 'student_code',
        'ma hv' => 'student_code',
        'ma sv' => 'student_code',
        'code' => 'student_code',
        'họ tên' => 'student_name',
        'ho ten' => 'student_name',
        'họ và tên' => 'student_name',
        'ho va ten' => 'student_name',
        'tên' => 'student_name',
        'name' => 'student_name',
        '15 phút' => 'oral_15',
        '15 phut' => 'oral_15',
        'kt 15' => 'oral_15',
        'kiểm tra 15 phút' => 'oral_15',
        '1 tiết' => 'period_1',
        '1 tiet' => 'period_1',
        'kiểm tra 1 tiết' => 'period_1',
        'giữa kỳ' => 'midterm',
        'giua ky' => 'midterm',
        'gk' => 'midterm',
        'điểm thi' => 'final',
        'diem thi' => 'final',
        'thi' => 'final',
        'final' => 'final',
    ],
];

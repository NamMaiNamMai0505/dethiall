<?php

/**
 * Biểu mẫu Lịch huấn luyện (chuẩn file Lịch Huấn Luyện HK2 25-26.xlsx).
 * Chữ ký + tên lưu sẵn để sau gắn chữ ký số vào tài khoản trùng tên.
 */
return [

    'org_left' => "TỔNG CỤC HẬU CẦN - KỸ THUẬT\nTRƯỜNG CAO ĐẲNG HẬU CẦN 2",

    'title' => 'LỊCH HUẤN LUYỆN',

    'respect_line' => 'Kính gửi:…………………………………………………..',

    'note' => 'Các khoa xây dựng phân công giảng dạy gửi về Phòng Đào tạo theo lịch này.',

    /**
     * Khối "KÍ HIỆU CHUNG" đặt bên phải bảng môn, giải thích các ký hiệu không
     * phải môn học xuất hiện trong lưới lịch. Sửa tại đây, không hardcode trong
     * exporter.
     *
     * @var list<array{term: string, meaning: string}>
     */
    'common_notes' => [
        [
            'term' => 'Ngày CTVHTT',
            'meaning' => 'Ngày chính trị văn hóa tinh thần',
        ],
        [
            'term' => 'Ngày pháp luật',
            'meaning' => 'Thực hiện ngày pháp luật theo kế hoạch, hướng dẫn chỉ đạo của cơ quan chính trị',
        ],
        [
            'term' => 'ÔN THI',
            'meaning' => 'Giờ ôn thi do đơn vị quản lý học viên duy trì thực hiện',
        ],
        [
            'term' => 'Sinh hoạt lớp',
            'meaning' => 'Do Tiểu đoàn hoặc Đại đội tổ chức thực hiện',
        ],
    ],

    'common_notes_title' => 'KÍ HIỆU CHUNG',

    /*
     * Rollout an toàn: ưu tiên template Active, chỉ quay về exporter cũ khi chưa
     * có template hoặc quá trình render gặp lỗi. Có thể tắt fallback sau khi
     * nghiệm thu đủ các template đang dùng trong thực tế.
     */
    'template_engine_enabled' => env('LHL_TEMPLATE_ENGINE_ENABLED', true),
    'template_engine_fallback' => env('LHL_TEMPLATE_ENGINE_FALLBACK', true),

    /** File mẫu Excel gốc (tham chiếu / clone khi cần). */
    'template_xlsx' => resource_path('templates/lhl/Lich_Huan_Luyen_template.xlsx'),

    /**
     * Bản clone riêng cho lịch có nhóm tiết nhỏ hơn một buổi
     * (1-3, 2-3, 3-4, 4-5, 6-9...). Không thay thế mẫu cũ.
     */
    'template_xlsx_grouped' => resource_path('templates/lhl/Lich_Huan_Luyen_grouped_periods_template.xlsx'),

    /**
     * 3 chữ ký chuẩn từ mẫu HK2 25-26.
     * - image: path trong disk public (storage/app/public/...)
     * - match_names: tên để map user → chữ ký số (giai đoạn sau)
     */
    'signers' => [
        [
            'key' => 'nguoi_lam_lich',
            'role_line1' => 'NGƯỜI LÀM LỊCH',
            'role_line2' => '',
            'name' => 'Thượng úy Lưu Phước Bảo Trung',
            'image' => 'signatures/lhl/nguoi_lam_lich.png',
            'match_names' => [
                'Lưu Phước Bảo Trung',
                'Luu Phuoc Bao Trung',
                'Thượng úy Lưu Phước Bảo Trung',
            ],
        ],
        [
            'key' => 'kt_truong_phong',
            'role_line1' => 'KT. TRƯỞNG PHÒNG',
            'role_line2' => 'PHÓ TRƯỞNG PHÒNG',
            'name' => 'Thượng tá Đinh Văn Hà',
            'image' => 'signatures/lhl/kt_truong_phong.png',
            'match_names' => [
                'Đinh Văn Hà',
                'Dinh Van Ha',
                'Thượng tá Đinh Văn Hà',
            ],
        ],
        [
            'key' => 'kt_hieu_truong',
            'role_line1' => 'KT. HIỆU TRƯỞNG',
            'role_line2' => 'PHÓ HIỆU TRƯỞNG ĐÀO TẠO',
            'name' => 'Đại tá Nguyễn Ngọc Huy',
            'image' => 'signatures/lhl/kt_hieu_truong.png',
            'match_names' => [
                'Nguyễn Ngọc Huy',
                'Nguyen Ngoc Huy',
                'Đại tá Nguyễn Ngọc Huy',
            ],
        ],
    ],
];

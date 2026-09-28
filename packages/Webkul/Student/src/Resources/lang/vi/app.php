<?php

return [
    'acl' => [
        'students' => 'Sinh viên',
        'create' => 'Tạo',
        'edit' => 'Chỉnh sửa',
        'view' => 'Xem',
        'delete' => 'Xóa',
    ],

    'students' => [
        'title' => 'Sinh viên',
        'create-success' => 'Sinh viên đã được tạo thành công.',
        'update-success' => 'Sinh viên đã được cập nhật thành công.',
        'delete-success' => 'Sinh viên đã được xóa thành công.',
        'delete-failed' => 'Xóa sinh viên thất bại.',
        'all-delete-success' => 'Các sinh viên đã chọn đã được xóa thành công.',
        'no-selection' => 'Không có sinh viên nào được chọn.',

        'index' => [
            'title' => 'Sinh viên',
            'create-btn' => 'Thêm sinh viên',

            'datagrid' => [
                'id' => 'ID',
                'name' => 'Họ và tên',
                'university-card-number' => 'Số thẻ sinh viên',
                'registration-number' => 'Số đăng ký',
                'major' => 'Chuyên ngành',
                'academic-level' => 'Trình độ học vấn',
                'created-at' => 'Ngày tạo',
                'view' => 'Xem',
                'edit' => 'Chỉnh sửa',
                'delete' => 'Xóa',
            ],
        ],

        'create' => [
            'title' => 'Thêm sinh viên',
            'save-btn' => 'Lưu sinh viên',
        ],

        'edit' => [
            'title' => 'Chỉnh sửa sinh viên',
            'save-btn' => 'Lưu thay đổi',
        ],

        'view' => [
            'title' => 'Sinh viên: :name',
            'heading' => 'Chi tiết sinh viên',
            'edit-btn' => 'Chỉnh sửa sinh viên',
            'general-info' => 'Thông tin chung',
        ],

        'form' => [
            'name' => 'Họ và tên',
            'university-card-number' => 'Số thẻ sinh viên',
            'registration-number' => 'Số đăng ký',
            'major' => 'Chuyên ngành',
            'academic-level' => 'Trình độ học vấn',
            'password' => 'Mật khẩu',
            'password-confirmation' => 'Xác nhận mật khẩu',
            'profile-image' => 'Ảnh đại diện',
        ],
    ],

    'configuration' => [
        'student-login' => [
            'title' => 'Trang đăng nhập sinh viên',
            'info' => 'Cấu hình thương hiệu và nội dung trang đăng nhập sinh viên.',
            'logo-image' => 'Logo đăng nhập',
            'primary-color' => 'Màu chính',
            'accent-color' => 'Màu nhấn',
            'surface-start' => 'Bắt đầu chuyển màu nền',
            'surface-end' => 'Kết thúc chuyển màu nền',
            'panel-start' => 'Bắt đầu chuyển màu bảng điều khiển',
            'panel-end' => 'Kết thúc chuyển màu bảng điều khiển',
            'field-title' => 'Tiêu đề đầu trang',
            'description' => 'Mô tả đầu trang',
            'eyebrow' => 'Văn bản giới thiệu',
            'panel-lead' => 'Đoạn giới thiệu bảng điều khiển bên',
            'card-number' => 'Nhãn trường số thẻ',
            'password' => 'Nhãn trường mật khẩu',
            'remember' => 'Nhãn ghi nhớ tôi',
            'submit' => 'Nhãn nút gửi',
            'back-portal' => 'Nhãn nút quay lại',
        ],

        'university-api' => [
            'title' => 'Tích hợp API đại học',
            'info' => 'Cấu hình điểm cuối xác minh sinh viên từ xa.',
            'endpoint-settings' => [
                'title' => 'Cài đặt điểm cuối',
                'info' => 'Cấu hình URL điểm cuối xác minh.',
                'endpoint' => 'URL điểm cuối xác minh API',
                'endpoint-info' => 'Nhập URL đầy đủ cho điểm cuối xác minh.',
            ],
        ],
    ],

    'components' => [
        'layouts' => [
            'header' => [
                'mega-search' => [
                    'explore-all-students' => 'Khám phá tất cả sinh viên',
                ],
            ],
        ],
    ],

    'login' => [
        'title' => 'Đăng nhập sinh viên',
        'description' => 'Sử dụng số thẻ sinh viên và mật khẩu do trường đại học cấp.',
        'eyebrow' => 'Truy cập an toàn',
        'panel_title' => 'Cổng thông tin đại học của bạn',
        'panel_lead' => 'Nơi tổng hợp các sự kiện, thông báo và mọi thứ bạn cần.',
        'feature_verify' => 'Xác minh danh tính qua trường đại học trong lần đăng nhập đầu tiên',
        'feature_profile' => 'Hồ sơ được đồng bộ hóa từ hồ sơ chính thức',
        'feature_portal' => 'Truy cập liền mạch vào cổng sinh viên',
        'trust_note' => 'Thông tin đăng nhập được kiểm tra với cơ sở đào tạo của bạn.',
        'back_portal' => 'Quay lại trang chủ',
        'show_password' => 'Hiển thị mật khẩu',
        'hide_password' => 'Ẩn mật khẩu',
        'card_number' => 'Số thẻ sinh viên',
        'password' => 'Mật khẩu',
        'remember' => 'Ghi nhớ đăng nhập',
        'submit' => 'Đăng nhập',
        'failed' => 'Thông tin đăng nhập không khớp với hồ sơ của chúng tôi.',
        'welcome_back' => 'Chào mừng quay trở lại.',
        'registered' => 'Tài khoản của bạn đã được tạo. Chào mừng.',
        'logged_out' => 'Bạn đã đăng xuất.',
    ],

    'university' => [
        'unavailable' => 'Dịch vụ xác minh của trường hiện không khả dụng.',
        'invalid_credentials' => 'Trường đại học không chấp nhận thông tin đăng nhập này.',
        'invalid_response' => 'Phản hồi bất thường từ máy chủ trường đại học.',
    ],
];

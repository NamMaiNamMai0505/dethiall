<?php

namespace Modules\StandardHours\Requests;

class UpdateResearchRecordRequest extends StoreResearchRecordRequest
{
    // Cùng một hợp đồng dữ liệu với thao tác tạo. Lớp riêng được giữ lại để
    // route/controller thể hiện rõ ý nghĩa và dễ mở rộng quyền cập nhật.
}

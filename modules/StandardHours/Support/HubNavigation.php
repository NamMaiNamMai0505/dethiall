<?php

namespace Modules\StandardHours\Support;

class HubNavigation
{
    /**
     * @return array{url: string, label: string, icon: string, color: string}
     */
    public static function backAction(): array
    {
        return [
            'url' => route('standard-hours.hub'),
            'label' => 'Quay lại',
            'icon' => 'arrow-left',
            'color' => 'gray',
        ];
    }
}

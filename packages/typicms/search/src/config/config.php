<?php

use TypiCMS\Modules\Pages\Models\Page;

return [
    'pages' => [
        'model' => Page::class,
        'columns' => [
            'title',
            'body',
        ],
    ],
    // 'news' => [
    //     'model' => 'TypiCMS\Modules\News\Models\News',
    //     'columns' => [
    //         'title',
    //         'body',
    //     ],
    // ],
];

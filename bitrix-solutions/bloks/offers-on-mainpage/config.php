<?php

return [
    'iblock_id' => 16,

    'properties' => [
        'offers' => 'OFFERS',
        'usage'  => 'USAGE',
    ],

    'sections' => [
        'hits' => [
            'title' => 'Хиты продаж',
            'offers_id' => 9,
        ],
        'news' => [
            'title' => 'Новинки',
            'offers_id' => 10,
        ],
        'best' => [
            'title' => 'Лучшие предложения',
            'offers_id' => 11,
        ],
    ],

    'tabs' => [
        'all' => [
            'title' => 'Все товары',
            'usage_id' => null,
        ],
        'student' => [
            'title' => 'Для студентов',
            'usage_id' => 175,
        ],
        'busines' => [
            'title' => 'Для бизнеса',
            'usage_id' => 176,
        ],
        'office' => [
            'title' => 'Для офиса',
            'usage_id' => 178,
        ],
        'home' => [
            'title' => 'Для дома',
            'usage_id' => 177,
        ],
        'exam' => [
            'title' => 'Для экзаменов',
            'usage_id' => 179,
        ],
    ],
];
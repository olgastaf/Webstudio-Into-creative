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
        'tab1' => [
            'title' => 'Если нельзя пронести телефон',
            'usage_id' => 175,
        ],
        'tab2' => [
            'title' => 'Если стоят глушилки',
            'usage_id' => 176,
        ],
        'tab3' => [
            'title' => 'Если стоят обнаружители микронаушников',
            'usage_id' => 177,
        ],
        'tab4' => [
            'title' => 'Для ЕГЭ и ОГЭ',
            'usage_id' => 178,
        ],
        'tab5' => [
            'title' => 'Микрокамера для экзаменов с наушником',
            'usage_id' => 179,
        ],
        'tab6' => [
            'title' => 'Камеры для офиса и дома',
            'usage_id' => 180,
        ],
    ],
];
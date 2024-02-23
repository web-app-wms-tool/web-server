<?php

return [
    'parameters' => [
        'include' => 'with',

        // 'filter' => 'filters',

        'sort' => 'sort',

        'fields' => 'fields',

        'append' => 'append',
        'pagination' => [
            'page' => 'page',
            'per_page' => 'itemsPerPage',
            'is_paginate' => 'paginate'
        ],
        "search" => "search",
        'limit' => 'limit',
    ],
    'pagination' => [
        'default_size' => 10,
        'is_paginate' => true
    ],
    'count_suffix' => 'Count',
    'delimiter' => [
        'include' => ',',

        // 'filter' => ';',

        'sort' => ',',

        'fields' => ',',

        'append' => ',',
    ]
];

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Blog API Configuration
    |--------------------------------------------------------------------------
    */

    'posts_per_page' => 15,
    
    'route_prefix' => 'api/blog',
    
    'default_sort_field' => 'published_at',
    
    'default_sort_direction' => 'desc',
    
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
    ],
    
    'pagination' => [
        'max_per_page' => 100,
    ],
];
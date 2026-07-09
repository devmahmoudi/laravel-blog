<?php

return [
    'title' => 'Blog API Documentation',
    'description' => 'RESTful API for managing blog posts',
    'base_url' => 'http://localhost:8000',
    'postman' => [
        'enabled' => true,
        'collection_name' => 'Blog API',
        'base_url' => '{{base_url}}',
    ],
    'openapi' => [
        'enabled' => true,
    ],
];
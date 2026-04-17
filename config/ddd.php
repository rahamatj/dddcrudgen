<?php

return [
    'domains' => [
        'Post' => [
            'fields' => [
                'title' => 'string',
                'description' => 'text',
            ],
            'rules' => [
                'title' => 'required | string',
                'description' => 'required | string',
            ],
            'factory' => [
                'title' => 'sentence',
                'description' => 'paragraph',
            ],
            'seed' => 20,
            'migrate' => true,
            'scaffold' => true,
        ],
        'Category' => [
            'fields' => [
                'name' => 'string',
                'description' => 'text',
            ],
            'rules' => [
                'name' => 'required | string',
                'description' => 'required | string',
            ],
            'factory' => [
                'name' => 'sentence',
                'description' => 'paragraph',
            ],
            'seed' => 20,
            'migrate' => true,
            'scaffold' => true,
        ],
        'Student' => [
            'fields' => [
                'name' => 'string',
                'age' => 'integer',
            ],
            'rules' => [
                'name' => 'required | string',
                'age' => 'required | integer',
            ],
            'factory' => [
                'name' => 'word',
                'age' => 'randomNumber',
            ],
            'seed' => 20,
            'migrate' => true,
            'scaffold' => true,
        ],
        'Teacher' => [
            'fields' => [
                'name' => 'string',
                'salary' => 'integer',
            ],
            'rules' => [
                'name' => 'required | string',
                'salary' => 'required | integer',
            ],
            'factory' => [
                'name' => 'word',
                'salary' => 'randomNumber',
            ],
            'seed' => 20,
            'migrate' => true,
            'scaffold' => true,
        ],
        'Product' => [
            'fields' => [
                'name' => 'string',
                'price' => 'integer',
            ],
            'rules' => [
                'name' => 'required | string',
                'price' => 'required | integer',
            ],
            'factory' => [
                'name' => 'word',
                'price' => 'randomNumber',
            ],
            'seed' => 30,
            'migrate' => true,
            'scaffold' => true,
        ],
        'Admin' => [
            'fields' => [
                'name' => 'string',
                'age' => 'integer',
            ],
            'rules' => [
                'name' => 'required | string',
                'age' => 'required | integer',
            ],
            'factory' => [
                'name' => 'word',
                'age' => 'randomNumber',
            ],
            'seed' => 30,
            'migrate' => true,
            'scaffold' => true,
        ],
        'Employee' => [
            'fields' => [
                'name' => 'string',
                'salary' => 'integer',
            ],
            'rules' => [
                'name' => 'required | string',
                'salary' => 'required | integer',
            ],
            'factory' => [
                'name' => 'word',
                'salary' => 'randomNumber',
            ],
            'seed' => 30,
            'migrate' => true,
            'scaffold' => true,
        ],
        'Radio' => [
            'fields' => [
                'name' => 'string',
                'price' => 'integer',
            ],
            'rules' => [
                'name' => 'required | string',
                'price' => 'required | integer',
            ],
            'factory' => [
                'name' => 'word',
                'price' => 'randomNumber',
            ],
            'seed' => 30,
            'migrate' => true,
            'scaffold' => true,
        ],
    ]
];
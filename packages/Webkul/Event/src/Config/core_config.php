<?php

return [
    'items' => [
        [
            'key' => 'general.store.events_page',
            'name' => 'event::app.events.title',
            'info' => 'event::app.events.index.title',
            'sort' => 2,
            'fields' => [
                [
                    'name' => 'heading',
                    'title' => 'event::app.events.create.name',
                    'type' => 'text',
                    'default' => 'Events for students',
                    'validation' => 'max:255',
                ], [
                    'name' => 'description',
                    'title' => 'event::app.events.create.description',
                    'type' => 'textarea',
                    'default' => 'Browse published events you can attend or follow.',
                ], [
                    'name' => 'per_page',
                    'title' => 'event::app.events.index.datagrid.per-page',
                    'type' => 'number',
                    'default' => 12,
                    'validation' => 'integer|min:1|max:48',
                ],
            ],
        ],
    ],

    'fields' => [
        'general.store.navigation' => [
            [
                'name' => 'show_events',
                'title' => 'event::app.events.title',
                'type' => 'boolean',
                'default' => true,
            ], [
                'name' => 'events_label',
                'title' => 'event::app.events.title',
                'type' => 'text',
                'default' => 'Events',
                'validation' => 'max:100',
            ],
        ],
        'general.settings.menu' => [
            [
                'name' => 'events',
                'title' => 'event::app.events.title',
                'type' => 'text',
                'default' => 'Events',
                'validation' => 'max:20',
            ], [
                'name' => 'events.event',
                'title' => 'event::app.events.title',
                'type' => 'text',
                'default' => 'Events',
                'validation' => 'max:20',
            ], [
                'name' => 'events.categories',
                'title' => 'event::app.event-categories.title',
                'type' => 'text',
                'default' => 'Categories',
                'validation' => 'max:30',
            ],
        ],
    ],
];

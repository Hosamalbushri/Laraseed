<?php

return [
    'items' => [
        [
            'key' => 'general.store.student_login',
            'name' => 'student::app.configuration.student-login.title',
            'info' => 'student::app.configuration.student-login.info',
            'sort' => 4,
            'fields' => [
                [
                    'name' => 'logo_image',
                    'title' => 'student::app.configuration.student-login.logo-image',
                    'type' => 'image',
                    'channel_based' => false,
                    'validation' => 'mimes:bmp,jpeg,jpg,png,webp,svg',
                ], [
                    'name' => 'primary_color',
                    'title' => 'student::app.configuration.student-login.primary-color',
                    'type' => 'color',
                    'default' => '#2563eb',
                ], [
                    'name' => 'accent_color',
                    'title' => 'student::app.configuration.student-login.accent-color',
                    'type' => 'color',
                    'default' => '#7c3aed',
                ], [
                    'name' => 'surface_start',
                    'title' => 'student::app.configuration.student-login.surface-start',
                    'type' => 'color',
                    'default' => '#f9fafb',
                ], [
                    'name' => 'surface_end',
                    'title' => 'student::app.configuration.student-login.surface-end',
                    'type' => 'color',
                    'default' => '#ffffff',
                ], [
                    'name' => 'panel_start',
                    'title' => 'student::app.configuration.student-login.panel-start',
                    'type' => 'color',
                    'default' => '#0f172a',
                ], [
                    'name' => 'panel_end',
                    'title' => 'student::app.configuration.student-login.panel-end',
                    'type' => 'color',
                    'default' => '#1d4ed8',
                ], [
                    'name' => 'title',
                    'title' => 'student::app.configuration.student-login.field-title',
                    'type' => 'text',
                    'validation' => 'max:255',
                ], [
                    'name' => 'description',
                    'title' => 'student::app.configuration.student-login.description',
                    'type' => 'textarea',
                ], [
                    'name' => 'eyebrow',
                    'title' => 'student::app.configuration.student-login.eyebrow',
                    'type' => 'text',
                    'validation' => 'max:100',
                ], [
                    'name' => 'panel_lead',
                    'title' => 'student::app.configuration.student-login.panel-lead',
                    'type' => 'textarea',
                ], [
                    'name' => 'card_number',
                    'title' => 'student::app.configuration.student-login.card-number',
                    'type' => 'text',
                    'validation' => 'max:255',
                ], [
                    'name' => 'password',
                    'title' => 'student::app.configuration.student-login.password',
                    'type' => 'text',
                    'validation' => 'max:255',
                ], [
                    'name' => 'remember',
                    'title' => 'student::app.configuration.student-login.remember',
                    'type' => 'text',
                    'validation' => 'max:255',
                ], [
                    'name' => 'submit',
                    'title' => 'student::app.configuration.student-login.submit',
                    'type' => 'text',
                    'validation' => 'max:255',
                ], [
                    'name' => 'back_portal',
                    'title' => 'student::app.configuration.student-login.back-portal',
                    'type' => 'text',
                    'validation' => 'max:255',
                ],
            ],
        ], [
            'key' => 'general.university_api',
            'name' => 'student::app.configuration.university-api.title',
            'info' => 'student::app.configuration.university-api.info',
            'icon' => 'icon-configuration',
            'sort' => 5,
        ], [
            'key' => 'general.university_api.endpoint_settings',
            'name' => 'student::app.configuration.university-api.endpoint-settings.title',
            'info' => 'student::app.configuration.university-api.endpoint-settings.info',
            'sort' => 1,
            'fields' => [
                [
                    'name' => 'endpoint',
                    'title' => 'student::app.configuration.university-api.endpoint-settings.endpoint',
                    'info' => 'student::app.configuration.university-api.endpoint-settings.endpoint-info',
                    'type' => 'text',
                    'default' => 'https://api.university.example/students/verify',
                ],
            ],
        ],
    ],

    'fields' => [
        'general.settings.menu' => [
            [
                'name' => 'students',
                'title' => 'student::app.students.title',
                'type' => 'text',
                'default' => 'Students',
                'validation' => 'max:20',
            ],
        ],
    ],
];

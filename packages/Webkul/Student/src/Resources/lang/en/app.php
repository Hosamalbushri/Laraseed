<?php

return [
    'acl' => [
        'students' => 'Students',
        'create' => 'Create',
        'edit' => 'Edit',
        'view' => 'View',
        'delete' => 'Delete',
    ],

    'students' => [
        'title' => 'Students',
        'create-success' => 'Student created successfully.',
        'update-success' => 'Student updated successfully.',
        'delete-success' => 'Student deleted successfully.',
        'delete-failed' => 'Student deletion failed.',
        'all-delete-success' => 'Selected students deleted successfully.',
        'no-selection' => 'No students were selected.',

        'index' => [
            'title' => 'Students',
            'create-btn' => 'Add Student',

            'datagrid' => [
                'id' => 'ID',
                'name' => 'Name',
                'university-card-number' => 'University Card Number',
                'registration-number' => 'Registration Number',
                'major' => 'Major',
                'academic-level' => 'Academic Level',
                'created-at' => 'Created At',
                'view' => 'View',
                'edit' => 'Edit',
                'delete' => 'Delete',
            ],
        ],

        'create' => [
            'title' => 'Add Student',
            'save-btn' => 'Save Student',
        ],

        'edit' => [
            'title' => 'Edit Student',
            'save-btn' => 'Save Changes',
        ],

        'view' => [
            'title' => 'Student: :name',
            'heading' => 'Student Details',
            'edit-btn' => 'Edit Student',
            'general-info' => 'General Information',
        ],

        'form' => [
            'name' => 'Name',
            'university-card-number' => 'University Card Number',
            'registration-number' => 'Registration Number',
            'major' => 'Major',
            'academic-level' => 'Academic Level',
            'password' => 'Password',
            'password-confirmation' => 'Password Confirmation',
            'profile-image' => 'Profile Image',
        ],
    ],

    'configuration' => [
        'student-login' => [
            'title' => 'Student Login Page',
            'info' => 'Configure student portal login page branding and content.',
            'logo-image' => 'Login Logo',
            'primary-color' => 'Primary Color',
            'accent-color' => 'Accent Color',
            'surface-start' => 'Surface Gradient Start',
            'surface-end' => 'Surface Gradient End',
            'panel-start' => 'Side Panel Gradient Start',
            'panel-end' => 'Side Panel Gradient End',
            'field-title' => 'Header Title',
            'description' => 'Header Description',
            'eyebrow' => 'Eyebrow Text',
            'panel-lead' => 'Side Panel Lead Paragraph',
            'card-number' => 'Card Number Field Label',
            'password' => 'Password Field Label',
            'remember' => 'Remember Me Label',
            'submit' => 'Submit Button Label',
            'back-portal' => 'Back Button Label',
        ],

        'university-api' => [
            'title' => 'University API Integration',
            'info' => 'Configure remote university student verification endpoint.',
            'endpoint-settings' => [
                'title' => 'Endpoint Settings',
                'info' => 'Configure the verification endpoint URL.',
                'endpoint' => 'API Verification Endpoint',
                'endpoint-info' => 'Enter the full URL for the university verification endpoint.',
            ],
        ],
    ],

    'components' => [
        'layouts' => [
            'header' => [
                'mega-search' => [
                    'explore-all-students' => 'Explore all Students',
                ],
            ],
        ],
    ],

    'login' => [
        'title' => 'Student sign in',
        'description' => 'Use your university card number and the password issued by your university. First-time sign in verifies with the university and creates your portal account.',
        'eyebrow' => 'Secure access',
        'panel_title' => 'Your campus portal',
        'panel_lead' => 'One place for events, updates, and everything you need as a student.',
        'feature_verify' => 'University-verified identity on first sign-in',
        'feature_profile' => 'Profile synced from your official student record',
        'feature_portal' => 'Seamless access to the student portal',
        'trust_note' => 'Credentials are checked against your institution. We never store your password in plain text.',
        'back_portal' => 'Back to home',
        'show_password' => 'Show password',
        'hide_password' => 'Hide password',
        'card_number' => 'University card number',
        'password' => 'Password',
        'remember' => 'Remember me',
        'submit' => 'Sign in',
        'failed' => 'These credentials do not match our records.',
        'welcome_back' => 'Welcome back.',
        'registered' => 'Your account has been created. Welcome.',
        'logged_out' => 'You have been signed out.',
    ],

    'university' => [
        'unavailable' => 'University verification service is unavailable. Please try again later.',
        'invalid_credentials' => 'The university did not accept these credentials.',
        'invalid_response' => 'Unexpected response from the university. Please contact support.',
    ],
];

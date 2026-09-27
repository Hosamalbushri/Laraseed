<?php

/**
 * Student portal + university API integration.
 *
 * Example JSON body your API might expect:
 *   { "card_number": "20201234", "password": "secret" }
 *
 * Example JSON response (map keys below with dot notation, e.g. data.name):
 *   {
 *     "success": true,
 *     "data": {
 *       "full_name": "Student Name",
 *       "student_id": "20201234",
 *       "major": "Computer Science",
 *       "level": "3"
 *     }
 *   }
 */
return [

    'university' => [

        /**
         * When true, skips HTTP and returns FakeUniversityStudentApiClient data (local dev).
         */
        'fake' => env('STUDENT_UNIVERSITY_API_FAKE', false),

        'base_url' => env('STUDENT_UNIVERSITY_API_BASE_URL', 'https://api.university.example'),

        /**
         * Appended to base_url (no leading slash required).
         */
        'verify_path' => env('STUDENT_UNIVERSITY_API_VERIFY_PATH', '/students/verify'),

        /**
         * Guzzle timeout in seconds.
         */
        'timeout' => (float) env('STUDENT_UNIVERSITY_API_TIMEOUT', 15),

        /**
         * Extra headers, e.g. Authorization: Bearer ...
         */
        'headers' => [],

        /**
         * JSON keys sent to the university API (plain values from the form).
         */
        'request_body_keys' => [
            'card_number' => 'university_card_number',
            'password' => 'password',
        ],

        /**
         * Dot-path into decoded JSON for each profile field (null = skip / leave empty).
         */
        'response_map' => [
            'name' => env('STUDENT_UNIVERSITY_RESPONSE_NAME', 'data.full_name'),
            'registration_number' => env('STUDENT_UNIVERSITY_RESPONSE_REG', 'data.student_id'),
            'major' => env('STUDENT_UNIVERSITY_RESPONSE_MAJOR', 'data.major'),
            'academic_level' => env('STUDENT_UNIVERSITY_RESPONSE_LEVEL', 'data.level'),
        ],

        /**
         * Dot-path to boolean/string success flag. If null, HTTP 2xx + decodeable JSON is treated as success.
         */
        'success_key' => env('STUDENT_UNIVERSITY_RESPONSE_SUCCESS_KEY', 'success'),

        /**
         * When success_key is set, compared with == to the JSON value (true, 1, "ok", etc.).
         */
        'success_value' => true,
    ],

    /**
     * Where to send the student after login/register.
     */
    'redirect_after_login' => env('STUDENT_REDIRECT_AFTER_LOGIN', '/'),

];

<?php

/**
 * Mock university API for local development (standalone, outside Laravel).
 *
 * Run from this directory:
 *   php -S 127.0.0.1:8090 router.php
 *
 * In .env (CRM):
 *   STUDENT_UNIVERSITY_API_FAKE=false
 *   STUDENT_UNIVERSITY_API_BASE_URL=http://127.0.0.1:8090
 *   STUDENT_UNIVERSITY_API_VERIFY_PATH=students/verify
 *
 * Expected POST JSON body (matches default student.php request_body_keys):
 *   { "card_number": "<university card>", "password": "<password>" }
 *
 * Success response (matches default response_map):
 *   { "success": true, "data": { "full_name", "student_id", "major", "level" } }
 *
 * الطلاب المعرفون أدناه فقط يقبلون؛ أي رقم آخر أو كلمة سر خاطئة → خطأ.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli-server') {
    fwrite(STDERR, "Run with: php -S 127.0.0.1:8090 router.php\n");
    exit(1);
}

/**
 * بيانات ثابتة: المفتاح = رقم البطاقة الجامعية (كما يُرسل في الطلب).
 * عدّل هذا الجدول لإضافة/حذف طلاب التجربة.
 *
 * @var array<string, array{password: string, full_name: string, student_id: string, major: string, level: string}>
 */
$students = [
    'demo' => [
        'password' => 'demo1234',
        'full_name' => 'طالب تجريبي',
        'student_id' => 'DEMO-001',
        'major' => 'علوم الحاسوب',
        'level' => '3',
    ],
    '20201234' => [
        'password' => 'pass2020',
        'full_name' => 'أحمد محمد',
        'student_id' => '20201234',
        'major' => 'هندسة البرمجيات',
        'level' => '2',
    ],
    '20214567' => [
        'password' => 'secret5678',
        'full_name' => 'فاطمة علي',
        'student_id' => '20214567',
        'major' => 'نظم المعلومات',
        'level' => '4',
    ],
];

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rtrim($path, '/') ?: '/';

$isVerify = str_ends_with($path, '/students/verify') || $path === '/students/verify';

if (! $isVerify) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'not_found', 'hint' => 'POST /students/verify'], JSON_UNESCAPED_UNICODE);

    return true;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE);

    return true;
}

$raw = file_get_contents('php://input') ?: '';
$input = json_decode($raw, true);

if (! is_array($input)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'invalid_json'], JSON_UNESCAPED_UNICODE);

    return true;
}

$card = isset($input['card_number']) ? trim((string) $input['card_number']) : '';
$password = isset($input['password']) ? (string) $input['password'] : '';

// نفس قاعدة FakeUniversityStudentApiClient: كلمة سر قصيرة جداً → رفض
if ($card === '' || strlen($password) < 4) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'invalid_credentials',
    ], JSON_UNESCAPED_UNICODE);

    return true;
}

if (! isset($students[$card])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'student_not_found',
    ], JSON_UNESCAPED_UNICODE);

    return true;
}

$record = $students[$card];
if (! hash_equals($record['password'], $password)) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'invalid_credentials',
    ], JSON_UNESCAPED_UNICODE);

    return true;
}

header('Content-Type: application/json; charset=utf-8');
http_response_code(200);
echo json_encode([
    'success' => true,
    'data' => [
        'full_name' => $record['full_name'],
        'student_id' => $record['student_id'],
        'major' => $record['major'],
        'level' => $record['level'],
    ],
], JSON_UNESCAPED_UNICODE);

return true;

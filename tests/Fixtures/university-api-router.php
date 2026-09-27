<?php

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$payload = json_decode(file_get_contents('php://input'), true);

header('Content-Type: application/json');

if ($path === '/timeout') {
    usleep(300000);
}

if ($path === '/redirect') {
    http_response_code(307);
    header('Location: /success');
    echo json_encode(['redirected' => true]);

    return;
}

if ($path === '/unauthorized') {
    http_response_code(401);
    echo json_encode(['error' => 'rejected']);

    return;
}

if ($path === '/forbidden') {
    http_response_code(403);
    echo json_encode(['error' => 'rejected']);

    return;
}

if ($path === '/malformed') {
    echo 'not-json';

    return;
}

if ($path === '/missing-name') {
    echo json_encode(['success' => true, 'data' => ['student_id' => 'HTTP-TEST']]);

    return;
}

if ($path === '/invalid') {
    echo json_encode(['success' => false]);

    return;
}

if (
    $path !== '/success'
    || ($payload['card_number'] ?? null) !== 'HTTP-TEST'
    || ($payload['password'] ?? null) !== 'controlled-password'
) {
    http_response_code(422);
    echo json_encode(['error' => 'unexpected_request']);

    return;
}

echo json_encode([
    'success' => true,
    'data' => [
        'full_name' => 'Controlled Student',
        'student_id' => 'HTTP-TEST',
        'major' => 'Security Engineering',
        'level' => '4',
    ],
]);

<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Models\CoreConfig;
use Webkul\Student\Services\Exceptions\UniversityApiException;
use Webkul\Student\Services\UniversityStudentApiClient;

uses(DatabaseTransactions::class);

function universityApiEndpoint(string $path): void
{
    CoreConfig::query()->updateOrCreate([
        'code' => 'general.university_api.endpoint_settings.endpoint',
    ], [
        'value' => 'http://127.0.0.1:'.$GLOBALS['university_api_test_port'].$path,
    ]);
}

beforeAll(function () {
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);

    if ($socket === false) {
        throw new RuntimeException("Unable to allocate University API test port: {$errorMessage}", $errorCode);
    }

    $address = stream_socket_get_name($socket, false);
    fclose($socket);

    $port = (int) substr(strrchr($address, ':'), 1);
    $projectRoot = dirname(__DIR__, 2);
    $log = sys_get_temp_dir().'/campushub-university-api-test.log';
    $process = proc_open([
        PHP_BINARY,
        '-S',
        '127.0.0.1:'.$port,
        $projectRoot.'/tests/Fixtures/university-api-router.php',
    ], [
        ['pipe', 'r'],
        ['file', $log, 'a'],
        ['file', $log, 'a'],
    ], $pipes, $projectRoot);

    if (! is_resource($process)) {
        throw new RuntimeException('Unable to start the controlled University API test server.');
    }

    $ready = false;
    for ($attempt = 0; $attempt < 50; $attempt++) {
        $connection = @fsockopen('127.0.0.1', $port);
        if (is_resource($connection)) {
            fclose($connection);
            $ready = true;
            break;
        }

        usleep(20000);
    }

    if (! $ready) {
        proc_terminate($process);
        proc_close($process);
        throw new RuntimeException('Controlled University API test server did not start.');
    }

    $GLOBALS['university_api_test_port'] = $port;
    $GLOBALS['university_api_test_process'] = $process;
});

afterAll(function () {
    if (isset($GLOBALS['university_api_test_process']) && is_resource($GLOBALS['university_api_test_process'])) {
        proc_terminate($GLOBALS['university_api_test_process']);
        proc_close($GLOBALS['university_api_test_process']);
    }
});

afterEach(function () {
    app()->detectEnvironment(fn () => 'testing');
    config()->set('student.university.timeout', 15);
});

it('maps a controlled successful university response without putting credentials in the URL', function () {
    universityApiEndpoint('/success');

    $profile = (new UniversityStudentApiClient)->verifyAndFetchProfile('HTTP-TEST', 'controlled-password');

    expect($profile->name)->toBe('Controlled Student')
        ->and($profile->registrationNumber)->toBe('HTTP-TEST')
        ->and($profile->major)->toBe('Security Engineering')
        ->and($profile->academicLevel)->toBe('4');
});

it('maps a rejected university response to invalid credentials', function () {
    universityApiEndpoint('/invalid');

    expect(fn () => (new UniversityStudentApiClient)->verifyAndFetchProfile('HTTP-TEST', 'controlled-password'))
        ->toThrow(UniversityApiException::class, __('student::app.university.invalid_credentials'));
});

it('maps university HTTP authorization failures to invalid credentials', function (string $path) {
    universityApiEndpoint($path);

    expect(fn () => (new UniversityStudentApiClient)->verifyAndFetchProfile('HTTP-TEST', 'controlled-password'))
        ->toThrow(UniversityApiException::class, __('student::app.university.invalid_credentials'));
})->with(['/unauthorized', '/forbidden']);

it('fails safely when the university API times out', function () {
    universityApiEndpoint('/timeout');
    config()->set('student.university.timeout', 0.05);

    expect(fn () => (new UniversityStudentApiClient)->verifyAndFetchProfile('HTTP-TEST', 'controlled-password'))
        ->toThrow(UniversityApiException::class, __('student::app.university.unavailable'));
});

it('fails safely on a university API connection failure without logging credentials', function () {
    CoreConfig::query()->updateOrCreate([
        'code' => 'general.university_api.endpoint_settings.endpoint',
    ], [
        'value' => 'http://127.0.0.1:1/unreachable',
    ]);
    config()->set('student.university.timeout', 0.05);
    Log::spy();

    expect(fn () => (new UniversityStudentApiClient)->verifyAndFetchProfile('HTTP-TEST', 'controlled-password'))
        ->toThrow(UniversityApiException::class, __('student::app.university.unavailable'));

    Log::shouldHaveReceived('warning')->with(
        'student.university_api',
        Mockery::on(function (array $context): bool {
            $serialized = json_encode($context);

            return array_keys($context) === ['exception', 'status']
                && ! str_contains($serialized, 'controlled-password')
                && ! str_contains($serialized, 'HTTP-TEST')
                && ! str_contains($serialized, 'unreachable');
        })
    )->once();
});

it('rejects a malformed university response', function () {
    universityApiEndpoint('/malformed');

    expect(fn () => (new UniversityStudentApiClient)->verifyAndFetchProfile('HTTP-TEST', 'controlled-password'))
        ->toThrow(UniversityApiException::class, __('student::app.university.invalid_response'));
});

it('rejects a university response missing the student name', function () {
    universityApiEndpoint('/missing-name');

    expect(fn () => (new UniversityStudentApiClient)->verifyAndFetchProfile('HTTP-TEST', 'controlled-password'))
        ->toThrow(UniversityApiException::class, __('student::app.university.invalid_response'));
});

it('does not follow redirects that could forward authentication credentials', function () {
    universityApiEndpoint('/redirect');

    expect(fn () => (new UniversityStudentApiClient)->verifyAndFetchProfile('HTTP-TEST', 'controlled-password'))
        ->toThrow(UniversityApiException::class, __('student::app.university.invalid_credentials'));
});

it('requires HTTPS for the real university client in production', function () {
    universityApiEndpoint('/success');
    app()->detectEnvironment(fn () => 'production');

    expect(fn () => (new UniversityStudentApiClient)->verifyAndFetchProfile('HTTP-TEST', 'controlled-password'))
        ->toThrow(UniversityApiException::class, __('student::app.university.unavailable'));
});

it('fails safely when the university endpoint configuration is missing', function () {
    CoreConfig::query()->updateOrCreate([
        'code' => 'general.university_api.endpoint_settings.endpoint',
    ], [
        'value' => '',
    ]);
    config()->set('student.university.base_url', '');
    config()->set('student.university.verify_path', '');

    expect(fn () => (new UniversityStudentApiClient)->verifyAndFetchProfile('HTTP-TEST', 'controlled-password'))
        ->toThrow(UniversityApiException::class, __('student::app.university.unavailable'));
});

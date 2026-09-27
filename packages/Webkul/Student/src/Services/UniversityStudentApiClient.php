<?php

namespace Webkul\Student\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Webkul\Student\DataTransferObjects\StudentProfileDto;
use Webkul\Student\Services\Contracts\UniversityStudentApiContract;
use Webkul\Student\Services\Exceptions\UniversityApiException;

class UniversityStudentApiClient implements UniversityStudentApiContract
{
    public function verifyAndFetchProfile(string $universityCardNumber, string $password): StudentProfileDto
    {
        $configuredEndpoint = trim((string) core()->getConfigData('general.university_api.endpoint_settings.endpoint'));

        if ($configuredEndpoint === '') {
            // Backward compatibility with the previous nested settings key.
            $configuredEndpoint = trim((string) core()->getConfigData('general.settings.university_api.endpoint'));
        }

        if ($configuredEndpoint !== '') {
            $url = $configuredEndpoint;
        } else {
            $baseUrl = rtrim((string) config('student.university.base_url'), '/');
            $path = ltrim((string) config('student.university.verify_path'), '/');
            $url = $baseUrl.'/'.$path;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = parse_url($url, PHP_URL_HOST);

        if (! in_array($scheme, ['http', 'https'], true) || ! is_string($host) || $host === '') {
            Log::warning('student.university_api_configuration', [
                'reason' => 'invalid_endpoint',
            ]);

            throw new UniversityApiException(__('student::app.university.unavailable'));
        }

        if (app()->environment('production') && $scheme !== 'https') {
            Log::warning('student.university_api_configuration', [
                'reason' => 'https_required',
            ]);

            throw new UniversityApiException(__('student::app.university.unavailable'));
        }

        $bodyKeys = config('student.university.request_body_keys', []);
        $payload = [];
        foreach ($bodyKeys as $jsonKey => $sourceField) {
            $payload[$jsonKey] = match ($sourceField) {
                'university_card_number' => $universityCardNumber,
                'password' => $password,
                default => null,
            };
        }

        $client = new Client([
            'timeout' => config('student.university.timeout', 15),
            'allow_redirects' => false,
            'http_errors' => false,
            'headers' => array_merge(
                ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
                (array) config('student.university.headers', [])
            ),
        ]);

        try {
            $response = $client->post($url, ['json' => $payload]);
        } catch (GuzzleException $e) {
            Log::warning('student.university_api', [
                'exception' => $e::class,
                'status' => $e instanceof RequestException
                    ? $e->getResponse()?->getStatusCode()
                    : null,
            ]);

            throw new UniversityApiException(__('student::app.university.unavailable'));
        }

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new UniversityApiException(__('student::app.university.invalid_credentials'));
        }

        $decoded = json_decode((string) $response->getBody(), true);
        if (! is_array($decoded)) {
            throw new UniversityApiException(__('student::app.university.invalid_response'));
        }

        $successKey = config('student.university.success_key');
        if ($successKey !== null && $successKey !== '') {
            $ok = Arr::get($decoded, $successKey);
            $expected = config('student.university.success_value');
            if ($ok != $expected) {
                throw new UniversityApiException(__('student::app.university.invalid_credentials'));
            }
        }

        $map = config('student.university.response_map', []);
        $namePath = $map['name'] ?? 'name';
        $name = trim((string) Arr::get($decoded, $namePath, ''));
        if ($name === '') {
            throw new UniversityApiException(__('student::app.university.invalid_response'));
        }

        return new StudentProfileDto(
            name: $name,
            registrationNumber: $this->mapField($decoded, $map['registration_number'] ?? null),
            major: $this->mapField($decoded, $map['major'] ?? null),
            academicLevel: $this->mapField($decoded, $map['academic_level'] ?? null),
        );
    }

    private function mapField(array $decoded, ?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return $this->nullableString(Arr::get($decoded, $path));
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_string($value) ? $value : (string) $value;
    }
}

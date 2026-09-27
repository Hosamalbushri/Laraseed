<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Address;
use Webkul\Admin\Notifications\Common;

uses(DatabaseTransactions::class);

it('serves the public homepage with HTTP 200 OK via HttpKernel', function () {
    $this->get('/')->assertOk();
});

it('returns HTTP 404 Not Found for non-existent routes', function () {
    $this->get('/non-existent-route-for-http-kernel-test')->assertNotFound();
});

it('returns HTTP 422 Unprocessable Entity for JSON validation failures', function () {
    $this->postJson(route('admin.session.create'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});

it('rejects unauthenticated requests to protected admin routes with a redirect to admin login', function () {
    $this->get(route('admin.dashboard.index'))
        ->assertRedirect(route('admin.session.create'));
});

it('rejects unauthenticated requests to protected student routes with a redirect to student login', function () {
    $this->get(route('shop.student.account.edit'))
        ->assertRedirect(route('student.login'));
});

it('properly constructs and validates email addresses preventing CRLF injection in Symfony Mime', function () {
    // Valid address with Arabic and English
    $address = new Address('student@example.edu', 'طالب جامعي');
    expect($address->getAddress())->toBe('student@example.edu')
        ->and($address->getName())->toBe('طالب جامعي');

    // CRLF injection attempt in email address must throw InvalidArgumentException
    expect(fn () => new Address("injected\r\nBcc:attacker@example.com@example.com"))
        ->toThrow(InvalidArgumentException::class);

    // CRLF injection attempt in display name must not produce multiline header output
    $injectedNameAddress = new Address('valid@example.com', "Injected\r\nHeader: value");
    expect($injectedNameAddress->toString())
        ->not->toContain("\r")
        ->not->toContain("\n");
});

it('builds mailables with attachments and Unicode content via Symfony Mime', function () {
    Mail::fake();

    $mailable = new Common([
        'to' => 'recipient@example.com',
        'subject' => 'إشعار تجريبي لاختبار Mime',
        'body' => '<p>محتوى الإشعار بالعربية مع رموز: © ★ 2026</p>',
        'attachments' => [
            [
                'name' => 'test-file.txt',
                'content' => 'محتوى المرفق التجريبي',
                'mime' => 'text/plain; charset=UTF-8',
            ],
        ],
    ]);

    $message = $mailable->build();

    expect($message->subject)->toBe('إشعار تجريبي لاختبار Mime')
        ->and($message->rawAttachments)->toHaveCount(1)
        ->and($message->rawAttachments[0]['name'])->toBe('test-file.txt');
});

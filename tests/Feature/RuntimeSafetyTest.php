<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function runtimeAuditUser(array $permissions): User
{
    $role = Role::create([
        'name' => 'Runtime audit '.uniqid(),
        'description' => 'Disposable runtime verification role',
        'permission_type' => 'custom',
        'permissions' => $permissions,
    ]);

    return User::create([
        'name' => 'Runtime Audit User',
        'email' => uniqid('runtime-audit-').'@example.test',
        'password' => Hash::make('runtime-audit-password'),
        'role_id' => $role->id,
        'status' => 1,
        'view_permission' => 'global',
    ]);
}


it('keeps admin guest authentication on the admin login', function () {
    $this->get(route('admin.dashboard.index'))
        ->assertRedirect(route('admin.session.create'));
});

it('authenticates the seeded active administrator and honors remember-me', function () {
    session()->put('url.intended', route('admin.dashboard.index'));

    $response = $this->post(route('admin.session.store'), [
        'email' => 'admin@example.com',
        'password' => 'admin123',
        'remember' => true,
    ])->assertRedirect(route('admin.dashboard.index'));

    $response->assertCookie(auth()->guard('user')->getRecallerName());
    $this->assertAuthenticatedAs(getDefaultAdmin(), 'user');
});

it('rejects invalid administrator credentials', function () {
    $this->post(route('admin.session.store'), [
        'email' => 'admin@example.com',
        'password' => 'incorrect-password',
    ])->assertRedirect();

    $this->assertGuest('user');
});

it('rejects a disabled administrator after valid credentials', function () {
    $user = runtimeAuditUser(['dashboard']);
    $user->update(['status' => 0]);

    $this->post(route('admin.session.store'), [
        'email' => $user->email,
        'password' => 'runtime-audit-password',
    ])->assertRedirect(route('admin.session.create'));

    $this->assertGuest('user');
});

it('boots core admin pages', function () {
    $this->actingAs(getDefaultAdmin(), 'user')
        ->get(route('admin.dashboard.index'))
        ->assertOk();
    $this->get(route('admin.settings.users.index'))->assertOk();
});

it('does not expose the installer after an installation marker exists', function () {
    $this->get(route('installer.index'))
        ->assertRedirect(route('admin.dashboard.index'));
});

it('rejects an unauthenticated TinyMCE upload', function () {
    $this->postJson(route('admin.tinymce.upload'), [
        'file' => UploadedFile::fake()->image('image.png'),
    ])->assertRedirect(route('admin.session.create'));
});

it('rejects TinyMCE upload by staff without its dedicated permission', function () {
    $this->actingAs(runtimeAuditUser(['dashboard']), 'user')
        ->postJson(route('admin.tinymce.upload'), [
            'file' => UploadedFile::fake()->image('image.png'),
        ])->assertUnauthorized();
});

it('stores a valid TinyMCE image under a generated public filename', function () {
    Storage::fake('public');

    $response = $this->actingAs(runtimeAuditUser(['content_upload']), 'user')
        ->postJson(route('admin.tinymce.upload'), [
            'file' => UploadedFile::fake()->image('original.png'),
        ])
        ->assertOk()
        ->assertJsonStructure(['location']);

    $path = ltrim(parse_url($response->json('location'), PHP_URL_PATH), '/');
    $path = preg_replace('#^storage/#', '', $path);

    expect($path)->toMatch('#^tinymce/[0-9a-f-]{36}\.png$#');
    Storage::disk('public')->assertExists($path);
});

it('rejects PHP-like TinyMCE uploads', function () {
    $this->actingAs(runtimeAuditUser(['content_upload']), 'user')
        ->postJson(route('admin.tinymce.upload'), [
            'file' => UploadedFile::fake()->createWithContent('shell.php', '<?php echo "safe fixture";'),
        ])->assertUnprocessable();
});

it('rejects a TinyMCE file whose MIME does not match its image extension', function () {
    $this->actingAs(runtimeAuditUser(['content_upload']), 'user')
        ->postJson(route('admin.tinymce.upload'), [
            'file' => UploadedFile::fake()->create('not-an-image.png', 1, 'text/plain'),
        ])->assertUnprocessable();
});

it('rejects oversized TinyMCE images', function () {
    $this->actingAs(runtimeAuditUser(['content_upload']), 'user')
        ->postJson(route('admin.tinymce.upload'), [
            'file' => UploadedFile::fake()->image('large.png')->size(5121),
        ])->assertUnprocessable();
});

it('rejects SVG TinyMCE images', function () {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"></svg>';

    $this->actingAs(runtimeAuditUser(['content_upload']), 'user')
        ->postJson(route('admin.tinymce.upload'), [
            'file' => UploadedFile::fake()->createWithContent('image.svg', $svg),
        ])->assertUnprocessable();
});

it('allows an explicitly classified staff self-service route', function () {
    $this->actingAs(runtimeAuditUser(['dashboard']), 'user')
        ->get(route('admin.user.account.edit'))
        ->assertOk();
});

it('rejects a custom role on a mapped route it lacks', function () {
    $this->actingAs(runtimeAuditUser(['content_upload']), 'user')
        ->get(route('admin.dashboard.index'))
        ->assertUnauthorized();
});

it('logs out a custom role with an empty permission state', function () {
    $this->actingAs(runtimeAuditUser([]), 'user')
        ->get(route('admin.dashboard.index'))
        ->assertRedirect(route('admin.session.create'));
});

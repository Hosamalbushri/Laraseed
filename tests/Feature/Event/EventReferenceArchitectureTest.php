<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Webkul\Event\Models\Event;
use Webkul\Event\Models\EventCategory;
use Webkul\Event\Services\EventSubscriptionService;
use Webkul\Student\Models\Student;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function eventReferenceUser(array $permissions, string $permissionType = 'custom'): User
{
    $role = Role::create([
        'name' => 'Event reference '.uniqid(),
        'description' => 'Disposable Event reference role',
        'permission_type' => $permissionType,
        'permissions' => $permissions,
    ]);

    return User::create([
        'name' => 'Event Reference User',
        'email' => uniqid('event-reference-').'@example.test',
        'password' => Hash::make('event-reference-password'),
        'role_id' => $role->id,
        'status' => 1,
        'view_permission' => 'global',
    ]);
}

function eventReferenceCategory(): EventCategory
{
    return EventCategory::create([
        'name' => 'Reference category '.uniqid(),
        'sort_order' => 0,
        'status' => true,
    ]);
}

function validEventPayload(EventCategory $category, array $overrides = []): array
{
    return [
        'event_date' => now()->toDateString(),
        'event_end_date' => now()->addDay()->toDateString(),
        'organizer' => 'CampusHub',
        'title' => 'Reference Event',
        'category_ids' => [$category->id],
        'available_seats' => 10,
        'availability_use_seats' => true,
        'status' => true,
        'event_custom_fields_form' => true,
        'fields' => [],
        ...$overrides,
    ];
}

it('preserves exact Event write authentication authorization validation and success responses', function () {
    $category = eventReferenceCategory();

    $this->post(route('admin.events.store'), validEventPayload($category))
        ->assertStatus(302)
        ->assertRedirect(route('admin.session.create'));

    $this->actingAs(eventReferenceUser(['students']), 'user')
        ->post(route('admin.events.store'), validEventPayload($category))
        ->assertStatus(401);

    $this->actingAs(eventReferenceUser(['events.create']), 'user')
        ->from(route('admin.events.create'))
        ->post(route('admin.events.store'), validEventPayload($category, [
            'event_end_date' => now()->subDay()->toDateString(),
            'title' => '',
        ]))
        ->assertStatus(302)
        ->assertRedirect(route('admin.events.create'))
        ->assertSessionHasErrors(['event_end_date', 'title']);

    $this->actingAs(eventReferenceUser([], 'all'), 'user')
        ->post(route('admin.events.store'), validEventPayload($category, [
            'title' => 'Created through FormRequest',
        ]))
        ->assertStatus(302)
        ->assertRedirect(route('admin.events.index'));

    $this->assertDatabaseHas('events', [
        'title' => 'Created through FormRequest',
        'availability_use_end_date' => true,
    ]);
});

it('uses package FormRequests for category and subscription validation', function () {
    $user = eventReferenceUser([], 'all');
    $category = eventReferenceCategory();
    $student = Student::create([
        'name' => 'Request Student',
        'university_card_number' => 'REQUEST-'.uniqid(),
        'password' => 'request-password',
    ]);

    $this->actingAs($user, 'user')
        ->post(route('admin.events.categories.store'), [])
        ->assertStatus(302)
        ->assertSessionHasErrors('name');

    $this->actingAs($user, 'user')
        ->put(route('admin.events.categories.update', $category->id), [
            'name' => 'Self parent',
            'parent_id' => $category->id,
        ])
        ->assertStatus(302)
        ->assertSessionHasErrors('parent_id');

    $this->actingAs($user, 'user')
        ->post(route('admin.students.subscriptions.store', $student->id), [])
        ->assertStatus(302)
        ->assertSessionHasErrors('event_id');
});

it('uses the canonical Event gallery without the removed single-image fallback', function () {
    Storage::fake('public');

    $user = eventReferenceUser([], 'all');
    $category = eventReferenceCategory();
    $event = Event::create([
        'title' => 'Pre-gallery Event',
        'event_date' => now()->toDateString(),
        'event_end_date' => now()->addDay()->toDateString(),
        'organizer' => 'CampusHub',
        'image' => 'events/pre-gallery.jpg',
        'status' => true,
        'availability_use_seats' => false,
        'availability_use_end_date' => true,
    ]);
    $event->categories()->attach($category->id);

    $this->actingAs($user, 'user')
        ->put(route('admin.events.update', $event->id), validEventPayload($category, [
            'title' => 'Canonical gallery Event',
            'images' => [UploadedFile::fake()->image('event.jpg')],
        ]))
        ->assertStatus(302)
        ->assertRedirect(route('admin.events.index'));

    $event->refresh();
    $image = $event->images()->sole();

    expect($event->image)->toBe($image->path)
        ->and($event->image)->not->toBe('events/pre-gallery.jpg');

    Storage::disk('public')->assertExists($image->path);

    $this->actingAs($user, 'user')
        ->put(route('admin.events.update', $event->id), validEventPayload($category, [
            'title' => 'Gallery cleared',
        ]))
        ->assertStatus(302);

    expect($event->fresh()->image)->toBeNull()
        ->and($event->images()->count())->toBe(0);
    Storage::disk('public')->assertMissing($image->path);
});

it('registers subscribedEvents from Event while Student source remains Event-free', function () {
    $student = Student::create([
        'name' => 'Relation Student',
        'university_card_number' => 'RELATION-'.uniqid(),
        'password' => 'relation-password',
    ]);
    $event = Event::create([
        'title' => 'Relation Event',
        'event_date' => now()->toDateString(),
        'event_end_date' => now()->addDay()->toDateString(),
        'organizer' => 'CampusHub',
        'status' => true,
        'availability_use_seats' => false,
        'availability_use_end_date' => true,
    ]);
    $event->subscribers()->attach($student->id);

    expect($student->subscribedEvents()->pluck('events.id')->all())->toBe([$event->id])
        ->and(file_get_contents(base_path('packages/Webkul/Student/src/Models/Student.php')))
        ->not->toContain('Webkul\\Event', 'subscribedEvents');
});

it('keeps Student deletion cleanup owned by Event and restores tracked seats', function () {
    $student = Student::create([
        'name' => 'Lifecycle Student',
        'university_card_number' => 'LIFECYCLE-'.uniqid(),
        'password' => 'lifecycle-password',
    ]);
    $event = Event::create([
        'title' => 'Lifecycle Event',
        'event_date' => now()->toDateString(),
        'event_end_date' => now()->addDay()->toDateString(),
        'organizer' => 'CampusHub',
        'available_seats' => 2,
        'status' => true,
        'availability_use_seats' => true,
        'availability_use_end_date' => true,
    ]);

    expect(app(EventSubscriptionService::class)->subscribeForAdmin($event->id, $student->id))->toBeTrue()
        ->and((int) $event->fresh()->available_seats)->toBe(1);

    $student->delete();

    expect((int) $event->fresh()->available_seats)->toBe(2)
        ->and(DB::table('event_student')->where('student_id', $student->id)->exists())->toBeFalse();
});

it('enforces Event isolation dependency metadata and request extraction statically', function () {
    $eventRoot = base_path('packages/Webkul/Event');
    $controllers = implode("\n", array_map(
        fn (string $path): string => file_get_contents($path),
        glob($eventRoot.'/src/Http/Controllers/Admin/*.php'),
    ));
    $studentReferences = [];
    $studentIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
        base_path('packages/Webkul/Student'),
        FilesystemIterator::SKIP_DOTS,
    ));

    foreach ($studentIterator as $file) {
        if ($file->isFile() && str_contains(file_get_contents($file->getPathname()), 'Webkul\\Event')) {
            $studentReferences[] = $file->getPathname();
        }
    }

    $manifest = json_decode(file_get_contents($eventRoot.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    $eventSource = '';
    $eventIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
        $eventRoot.'/src',
        FilesystemIterator::SKIP_DOTS,
    ));

    foreach ($eventIterator as $file) {
        if ($file->isFile()) {
            $eventSource .= file_get_contents($file->getPathname())."\n";
        }
    }

    expect($controllers)
        ->not->toContain('$this->validate(', '->validate(', 'Validator::')
        ->and($studentReferences)->toBe([])
        ->and($eventSource)->not->toContain(
            'class_exists(',
            'Schema::hasTable(',
            'Route::has(',
            'ReflectionClass',
            'Webkul\\Shop',
            'shop::',
        )
        ->and($manifest['require']['webkul/student'] ?? null)->toBe('dev-main')
        ->and(file_get_contents($eventRoot.'/src/Providers/EventServiceProvider.php'))
        ->toContain("resolveRelationUsing('subscribedEvents'")
        ->and(file_exists($eventRoot.'/src/Repositories/EventFieldRepository.php'))->toBeFalse()
        ->and(file_exists($eventRoot.'/ EVENT-README.md'))->toBeFalse()
        ->and(file_exists($eventRoot.'/package.json'))->toBeFalse()
        ->and(file_exists($eventRoot.'/tailwind.config.js'))->toBeFalse();
});

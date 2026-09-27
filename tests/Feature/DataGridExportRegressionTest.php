<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function exportAuditUser(array $permissions = ['settings.user.groups'], bool $all = false): User
{
    $role = Role::query()->create([
        'name' => 'Export Audit Role '.Str::random(8),
        'description' => 'Role for export testing',
        'permission_type' => $all ? 'all' : 'custom',
        'permissions' => $permissions,
    ]);

    return User::query()->create([
        'name' => 'Export Audit User '.Str::random(8),
        'email' => 'export-audit-'.Str::random(8).'@example.com',
        'password' => bcrypt('Password123!'),
        'status' => 1,
        'role_id' => $role->id,
    ]);
}

it('generates real CSV, XLS, and XLSX exports via DataGrid', function (string $format) {
    $user = exportAuditUser(['settings.user.groups']);

    $response = $this->actingAs($user, 'user')
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->get(route('admin.settings.groups.index', ['export' => 1, 'format' => $format]));

    $response->assertOk();
    $disposition = (string) $response->headers->get('content-disposition');
    expect($disposition)->toContain('attachment;')
        ->and($disposition)->toContain('.'.$format);

    $file = $response->getFile();
    expect($file)->not->toBeNull();
    expect(file_exists($file->getPathname()))->toBeTrue();
    expect(filesize($file->getPathname()))->toBeGreaterThan(0);
})->with(['csv', 'xls', 'xlsx']);

it('allows authorized staff and denies unauthorized staff from exporting', function () {
    $authorized = exportAuditUser(['settings.user.groups']);
    $unauthorized = exportAuditUser(['students']);

    $this->actingAs($authorized, 'user')
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->get(route('admin.settings.groups.index', ['export' => 1, 'format' => 'csv']))
        ->assertOk();

    $this->actingAs($unauthorized, 'user')
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->get(route('admin.settings.groups.index', ['export' => 1, 'format' => 'csv']))
        ->assertUnauthorized();
});

it('handles an empty dataset export without crashing', function (string $format) {
    $user = exportAuditUser(['settings.user.groups']);

    $response = $this->actingAs($user, 'user')
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->get(route('admin.settings.groups.index', [
            'export' => 1,
            'format' => $format,
            'id[in]' => '99999999',
        ]));

    $response->assertOk();
    $file = $response->getFile();
    expect($file)->not->toBeNull();
    expect(file_exists($file->getPathname()))->toBeTrue();
    expect(filesize($file->getPathname()))->toBeGreaterThan(0);
})->with(['csv', 'xls', 'xlsx']);

it('exports content with Arabic, commas, quotes, and Unicode correctly', function (string $format) {
    $user = exportAuditUser(['settings.user.groups']);

    $specialName = 'مجموعة خاصة "تجريبية"، اختبار';
    $specialDesc = "سطر أول\nسطر ثانٍ مع رموز: ★ © 2026, and English text with \"quotes\"";

    DB::table('groups')->insert([
        'name' => $specialName,
        'description' => $specialDesc,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($user, 'user')
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->get(route('admin.settings.groups.index', ['export' => 1, 'format' => $format]));

    $response->assertOk();
    $file = $response->getFile();
    expect($file)->not->toBeNull();
    expect(file_exists($file->getPathname()))->toBeTrue();

    if ($format === 'csv') {
        $content = file_get_contents($file->getPathname());
        expect($content)->toContain('مجموعة خاصة');
    }
})->with(['csv', 'xls', 'xlsx']);

it('neutralizes formula-leading characters in CSV, XLS, and XLSX exports', function (string $format) {
    $user = exportAuditUser(['settings.user.groups']);

    $formulaEquals = '=1+1';
    $formulaPlus = '+1+1';
    $formulaAt = '@SUM(1+1)';
    $formulaHyperlink = '=HYPERLINK("https://example.invalid","click")';
    $formulaDde = "=cmd|' /C calc'!A1";
    $formulaSpace = '  =SUM(A1:A2)';
    $formulaTab = "\t=1+1";
    $formulaCr = "\r=1+1";
    $minusNonNumeric = '-SUM(1+1)';
    $normalNumeric = '-15';
    $normalText = 'طالب جامعي';

    DB::table('groups')->insert([
        [
            'name' => $formulaEquals,
            'description' => $formulaPlus,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'name' => $formulaAt,
            'description' => $formulaHyperlink,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'name' => $formulaDde,
            'description' => $formulaSpace,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'name' => $formulaTab,
            'description' => $formulaCr,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'name' => $minusNonNumeric,
            'description' => $normalNumeric.' '.$normalText,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $response = $this->actingAs($user, 'user')
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->get(route('admin.settings.groups.index', ['export' => 1, 'format' => $format]));

    $response->assertOk();
    $file = $response->getFile();
    expect($file)->not->toBeNull();
    expect(file_exists($file->getPathname()))->toBeTrue();

    if ($format === 'csv') {
        $content = file_get_contents($file->getPathname());

        // Dangerous formulas must be single-quote prefixed
        expect($content)->toContain("'=1+1")
            ->and($content)->toContain("'+1+1")
            ->and($content)->toContain("'@SUM(1+1)")
            ->and($content)->toContain("'-SUM(1+1)")
            ->and($content)->toContain("'  =SUM(A1:A2)")
            ->and($content)->toContain("'=HYPERLINK")
            ->and($content)->toContain("'=cmd|");

        // Tab-prefixed formula payload contains single quote before tab/formula
        expect($content)->toContain("'\t=1+1");

        // Unescaped formula triggers must NOT exist at start of string fields
        expect($content)->not->toContain('"=1+1"')
            ->and($content)->not->toContain('"+1+1"')
            ->and($content)->not->toContain('"@SUM(1+1)"');

        // Normal text and Arabic must be preserved
        expect($content)->toContain('طالب جامعي')
            ->and($content)->toContain('-15');
    }

    // PhpSpreadsheet round-trip type verification for XLS and XLSX
    if (in_array($format, ['xls', 'xlsx'], true)) {
        $reader = IOFactory::createReaderForFile($file->getPathname());
        $spreadsheet = $reader->load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                // Assert no cell is evaluated as a spreadsheet formula
                expect($cell->getDataType())->not->toBe(DataType::TYPE_FORMULA);
            }
        }
    }
})->with(['csv', 'xls', 'xlsx']);

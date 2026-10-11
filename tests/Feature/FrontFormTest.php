<?php

namespace Tests\Feature;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Lara\App\Models\Contactform;
use Lara\Common\Models\Blacklist;
use Lara\Common\Models\Entity;
use Lara\Common\Models\EntityCustomField;
use Lara\Common\Models\MenuItem;
use Lara\Common\Routes\MenuRouteName;
use Lara\Front\Mail\MailConfirmation;
use Mcamara\LaravelLocalization\LaravelLocalization;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The contact form on the public site (form entity "contactforms": name and
 * email required, telephone and comment optional).
 *
 * The test database has no form in its menu, and front routes are built from
 * the menu when the application boots. Each test therefore adds a menu item for
 * the contact form and rebuilds the application. A rebuilt application does not
 * see uncommitted rows, so these tests cannot use DatabaseTransactions: they
 * record the highest id of every table a form request writes to, and delete
 * what was added after it in tearDown().
 */
class FrontFormTest extends TestCase
{
    private const FORM_ROUTE = 'zz-contact-test';

    /**
     * The menu item, the submission, the IP blacklist (spam), the module and
     * email pages (with their SEO rows) that are created on first use, and
     * custom fields a test adds to the form.
     *
     * @var array<string, int>
     */
    private array $maxIds = [];

    /**
     * Columns a test adds to the contact form table, dropped in tearDown().
     *
     * @var list<string>
     */
    private array $addedColumns = [];

    protected function setUp(): void
    {
        putenv(LaravelLocalization::ENV_ROUTE_KEY.'=nl');

        parent::setUp();

        foreach (['lara_menu_menu_items', 'lara_form_contactforms', 'lara_sys_blacklist', 'lara_content_pages', 'lara_object_seo', 'lara_resource_entity_custom_fields'] as $table) {
            $this->maxIds[$table] = (int) DB::table($table)->max('id');
        }

        $this->makeContactMenuItem();

        $this->refreshApplication();
    }

    protected function tearDown(): void
    {
        foreach ($this->maxIds as $table => $maxId) {
            DB::table($table)->where('id', '>', $maxId)->delete();
        }

        foreach ($this->addedColumns as $column) {
            Schema::table('lara_form_contactforms', fn (Blueprint $table) => $table->dropColumn($column));
        }

        if ($this->addedColumns !== []) {
            $this->forgetGuardableColumns();
        }

        putenv(LaravelLocalization::ENV_ROUTE_KEY.'=');

        parent::tearDown();
    }

    public function test_the_contact_form_renders(): void
    {
        $this->get('/nl/'.self::FORM_ROUTE)->assertOk();

        $this->assertSame('form.contactforms.'.self::FORM_ROUTE.'.form', Route::current()?->getName());
    }

    public function test_a_valid_submission_stores_every_field_and_queues_both_mails(): void
    {
        Mail::fake();

        $response = $this->postJson('/nl/'.self::FORM_ROUTE, [
            'name' => 'Jan Jansen',
            'email' => 'jan@example.com',
            'telephone' => '0612345678',
            'comment' => 'Graag meer informatie over jullie diensten.',
        ]);

        $response->assertOk()->assertJson(['sendstatus' => 1]);
        $this->assertDatabaseHas('lara_form_contactforms', [
            'name' => 'Jan Jansen',
            'email' => 'jan@example.com',
            'telephone' => '0612345678',
            'comment' => 'Graag meer informatie over jullie diensten.',
            'deleted_at' => null,
        ]);
        Mail::assertQueued(MailConfirmation::class, 2);
    }

    public function test_a_submission_cannot_fill_columns_that_are_not_form_fields(): void
    {
        Mail::fake();

        $this->postJson('/nl/'.self::FORM_ROUTE, [
            'name' => 'Jan Jansen',
            'email' => 'jan@example.com',
            'locked_at' => '2030-01-01 00:00:00',
        ])->assertOk();

        $this->assertNull(Contactform::where('name', 'Jan Jansen')->sole()->locked_at);
    }

    public function test_an_unticked_toggle_and_an_empty_number_field_get_their_column_default(): void
    {
        Mail::fake();
        $this->addFormField('zznewsletter', 'toggle', fn (Blueprint $table, string $name) => $table->boolean($name)->default(0));
        $this->addFormField('zzguests', 'number', fn (Blueprint $table, string $name) => $table->integer($name)->default(0));

        $this->postJson('/nl/'.self::FORM_ROUTE, [
            'name' => 'Jan Jansen',
            'email' => 'jan@example.com',
            'zzguests' => '',
        ])->assertOk();

        $submission = DB::table('lara_form_contactforms')->where('name', 'Jan Jansen')->sole();
        $this->assertSame(0, (int) $submission->zznewsletter);
        $this->assertSame(0, (int) $submission->zzguests);
    }

    public function test_a_ticked_toggle_and_a_number_are_stored(): void
    {
        Mail::fake();
        $this->addFormField('zznewsletter', 'toggle', fn (Blueprint $table, string $name) => $table->boolean($name)->default(0));
        $this->addFormField('zzguests', 'number', fn (Blueprint $table, string $name) => $table->integer($name)->default(0));

        $this->postJson('/nl/'.self::FORM_ROUTE, [
            'name' => 'Jan Jansen',
            'email' => 'jan@example.com',
            'zznewsletter' => 1,
            'zzguests' => '4',
        ])->assertOk();

        $submission = DB::table('lara_form_contactforms')->where('name', 'Jan Jansen')->sole();
        $this->assertSame(1, (int) $submission->zznewsletter);
        $this->assertSame(4, (int) $submission->zzguests);
    }

    /**
     * @return array<string, array{string, Closure, string}>
     */
    public static function invalidTypedValues(): array
    {
        return [
            'text in a number field' => ['number', static fn (Blueprint $table, string $name) => $table->integer($name)->default(0), 'abc'],
            'text in a date field' => ['date', static fn (Blueprint $table, string $name) => $table->date($name)->nullable(), 'foo'],
            'an impossible time' => ['time', static fn (Blueprint $table, string $name) => $table->time($name)->nullable(), '25:99'],
            'text in a toggle' => ['toggle', static fn (Blueprint $table, string $name) => $table->boolean($name)->default(0), 'maybe'],
        ];
    }

    #[DataProvider('invalidTypedValues')]
    public function test_a_value_of_the_wrong_type_is_rejected(string $fieldType, Closure $column, string $value): void
    {
        Mail::fake();
        $this->addFormField('zzfield', $fieldType, $column);
        $countBefore = Contactform::withTrashed()->count();

        $response = $this->postJson('/nl/'.self::FORM_ROUTE, [
            'name' => 'Jan Jansen',
            'email' => 'jan@example.com',
            'zzfield' => $value,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('zzfield');
        $this->assertSame($countBefore, Contactform::withTrashed()->count());
    }

    public function test_a_date_and_a_time_are_stored(): void
    {
        Mail::fake();
        $this->addFormField('zzdate', 'date', fn (Blueprint $table, string $name) => $table->date($name)->nullable());
        $this->addFormField('zztime', 'time', fn (Blueprint $table, string $name) => $table->time($name)->nullable());

        $this->postJson('/nl/'.self::FORM_ROUTE, [
            'name' => 'Jan Jansen',
            'email' => 'jan@example.com',
            'zzdate' => '2026-12-31',
            'zztime' => '14:30',
        ])->assertOk();

        $submission = DB::table('lara_form_contactforms')->where('name', 'Jan Jansen')->sole();
        $this->assertSame('2026-12-31', $submission->zzdate);
        $this->assertSame('14:30:00', $submission->zztime);
    }

    public function test_a_ticked_html_checkbox_is_stored_and_mailed_as_yes(): void
    {
        Mail::fake();
        $this->addFormField('zzterms', 'checkbox', fn (Blueprint $table, string $name) => $table->boolean($name)->default(0));

        $this->postJson('/nl/'.self::FORM_ROUTE, [
            'name' => 'Jan Jansen',
            'email' => 'jan@example.com',
            'zzterms' => 'on',
        ])->assertOk();

        $submission = DB::table('lara_form_contactforms')->where('name', 'Jan Jansen')->sole();
        $this->assertSame(1, (int) $submission->zzterms);
        Mail::assertQueued(
            MailConfirmation::class,
            fn (MailConfirmation $mail): bool => $mail->maildata->content->data->zzterms['colval'] === _q('lara-admin::default.value.yes'),
        );
    }

    public function test_a_zero_in_an_optional_text_field_is_kept(): void
    {
        Mail::fake();

        $this->postJson('/nl/'.self::FORM_ROUTE, [
            'name' => 'Jan Jansen',
            'email' => 'jan@example.com',
            'telephone' => '0',
        ])->assertOk();

        $this->assertSame('0', Contactform::where('name', 'Jan Jansen')->sole()->telephone);
    }

    public function test_a_comment_with_one_link_is_accepted_unchanged(): void
    {
        Mail::fake();

        $this->postJson('/nl/'.self::FORM_ROUTE, [
            'name' => 'Jan Jansen',
            'email' => 'jan@example.com',
            'comment' => 'Zie https://www.example.com voor details.',
        ])->assertOk();

        $submission = Contactform::where('name', 'Jan Jansen')->sole();
        $this->assertSame('Zie https://www.example.com voor details.', $submission->comment);
        Mail::assertQueued(MailConfirmation::class, 2);
    }

    public function test_an_array_for_a_text_field_is_rejected(): void
    {
        Mail::fake();
        $countBefore = Contactform::withTrashed()->count();

        $response = $this->postJson('/nl/'.self::FORM_ROUTE, [
            'name' => ['Jan', 'Jansen'],
            'email' => 'jan@example.com',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->assertSame($countBefore, Contactform::withTrashed()->count());
    }

    public function test_the_submission_stores_the_client_ip_and_ignores_a_submitted_one(): void
    {
        Mail::fake();

        $this->withServerVariables(['REMOTE_ADDR' => '10.1.2.3'])->postJson('/nl/'.self::FORM_ROUTE, [
            'name' => 'Jan Jansen',
            'email' => 'jan@example.com',
            '_ipaddress' => '6.6.6.6',
        ])->assertOk();

        $this->assertSame('10.1.2.3', Contactform::where('name', 'Jan Jansen')->sole()->ipaddress);
    }

    public function test_behind_cloudflare_the_visitor_ip_is_stored(): void
    {
        Mail::fake();

        $this->withServerVariables(['REMOTE_ADDR' => '173.245.48.10'])
            ->withHeader('X-Forwarded-For', '203.0.113.7')
            ->postJson('/nl/'.self::FORM_ROUTE, ['name' => 'Jan Jansen', 'email' => 'jan@example.com'])
            ->assertOk();

        $this->assertSame('203.0.113.7', Contactform::where('name', 'Jan Jansen')->sole()->ipaddress);
    }

    public function test_behind_cloudflare_an_ip_the_visitor_adds_to_the_forwarded_header_is_ignored(): void
    {
        Mail::fake();

        $this->withServerVariables(['REMOTE_ADDR' => '173.245.48.10'])
            ->withHeader('X-Forwarded-For', '6.6.6.6, 203.0.113.8')
            ->postJson('/nl/'.self::FORM_ROUTE, ['name' => 'Jan Jansen', 'email' => 'jan@example.com'])
            ->assertOk();

        $this->assertSame('203.0.113.8', Contactform::where('name', 'Jan Jansen')->sole()->ipaddress);
    }

    public function test_a_forwarded_ip_from_an_untrusted_address_is_ignored(): void
    {
        Mail::fake();

        $this->withServerVariables(['REMOTE_ADDR' => '10.1.2.6'])
            ->withHeader('X-Forwarded-For', '6.6.6.6')
            ->postJson('/nl/'.self::FORM_ROUTE, ['name' => 'Jan Jansen', 'email' => 'jan@example.com'])
            ->assertOk();

        $this->assertSame('10.1.2.6', Contactform::where('name', 'Jan Jansen')->sole()->ipaddress);
    }

    public function test_a_blacklist_entry_older_than_24_hours_no_longer_blocks(): void
    {
        Mail::fake();
        DB::table('lara_sys_blacklist')->insert(['ipaddress' => '10.1.2.7', 'created_at' => now()->subHours(25), 'updated_at' => now()->subHours(25)]);

        $this->withServerVariables(['REMOTE_ADDR' => '10.1.2.7'])->postJson('/nl/'.self::FORM_ROUTE, [
            'name' => 'Jan Jansen',
            'email' => 'jan@example.com',
        ])->assertOk();

        $this->assertNotSoftDeleted(Contactform::where('name', 'Jan Jansen')->sole());
        Mail::assertQueued(MailConfirmation::class, 2);
    }

    public function test_links_and_email_addresses_in_a_string_field_are_spam(): void
    {
        Mail::fake();

        $this->postJson('/nl/'.self::FORM_ROUTE, [
            'name' => 'Spammer',
            'email' => 'jan@example.com',
            'telephone' => 'www.spam.example offers@spam.example',
        ])->assertOk();

        $submission = Contactform::withTrashed()->where('name', 'Spammer')->sole();
        $this->assertSoftDeleted($submission);
        $this->assertSame('[SPAM] - www.spam.example offers@spam.example', $submission->telephone);
        Mail::assertNothingQueued();
    }

    public function test_a_spam_submission_blacklists_the_client_ip(): void
    {
        Mail::fake();

        $this->withServerVariables(['REMOTE_ADDR' => '10.1.2.4'])->postJson('/nl/'.self::FORM_ROUTE, [
            'name' => 'Spammer',
            'email' => 'jan@example.com',
            'comment' => 'Cheap stuff at https://spam.example, mail offers@spam.example now',
        ])->assertOk();

        $this->assertDatabaseHas('lara_sys_blacklist', ['ipaddress' => '10.1.2.4']);
    }

    public function test_a_submission_from_a_blacklisted_ip_is_stored_as_spam_without_mail(): void
    {
        Mail::fake();
        Blacklist::create(['ipaddress' => '10.1.2.5']);

        $this->withServerVariables(['REMOTE_ADDR' => '10.1.2.5'])->postJson('/nl/'.self::FORM_ROUTE, [
            'name' => 'Jan Jansen',
            'email' => 'jan@example.com',
            'comment' => 'Een gewone vraag.',
        ])->assertOk();

        $this->assertSoftDeleted(Contactform::withTrashed()->where('name', 'Jan Jansen')->sole());
        Mail::assertNothingQueued();
    }

    public function test_a_toggle_field_is_shown_as_yes_in_the_mail(): void
    {
        Mail::fake();
        EntityCustomField::create([
            'entity_id' => Entity::where('resource_slug', 'contactforms')->value('id'),
            'title' => 'zznewsletter',
            'field_name' => 'zznewsletter',
            'field_type' => 'toggle',
            'field_hook' => 'default',
        ]);

        $this->postJson('/nl/'.self::FORM_ROUTE, [
            'name' => 'Jan Jansen',
            'email' => 'jan@example.com',
            'zznewsletter' => 1,
        ])->assertOk();

        Mail::assertQueued(
            MailConfirmation::class,
            fn (MailConfirmation $mail): bool => $mail->maildata->content->data->zznewsletter['colval'] === _q('lara-admin::default.value.yes'),
        );
    }

    public function test_an_invalid_email_address_is_rejected(): void
    {
        Mail::fake();
        $countBefore = Contactform::withTrashed()->count();

        $response = $this->postJson('/nl/'.self::FORM_ROUTE, [
            'name' => 'Jan Jansen',
            'email' => 'jan@',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertSame($countBefore, Contactform::withTrashed()->count());
        Mail::assertNothingQueued();
    }

    public function test_a_submission_without_a_required_field_is_rejected(): void
    {
        Mail::fake();
        $countBefore = Contactform::withTrashed()->count();

        $response = $this->postJson('/nl/'.self::FORM_ROUTE, [
            'name' => 'Jan Jansen',
            'comment' => 'Zonder e-mailadres.',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertSame($countBefore, Contactform::withTrashed()->count());
        Mail::assertNothingQueued();
    }

    public function test_a_comment_with_links_and_email_addresses_is_stored_as_spam_without_mail(): void
    {
        Mail::fake();

        $this->postJson('/nl/'.self::FORM_ROUTE, [
            'name' => 'Spammer',
            'email' => 'jan@example.com',
            'comment' => 'Cheap stuff at https://www.spam.example, mail offers@spam.example now',
        ])->assertOk();

        $submission = Contactform::withTrashed()->where('name', 'Spammer')->sole();
        $this->assertSoftDeleted($submission);
        $this->assertSame('[SPAM] - Cheap stuff at https://www.spam.example, mail offers@spam.example now', $submission->comment);
        Mail::assertNothingQueued();
    }

    /**
     * Adds a custom field to the contact form, with its column as the builder
     * creates it (HasLaraBuilder::buildCustomField).
     */
    private function addFormField(string $fieldName, string $fieldType, callable $column): void
    {
        EntityCustomField::create([
            'entity_id' => Entity::where('resource_slug', 'contactforms')->value('id'),
            'title' => $fieldName,
            'field_name' => $fieldName,
            'field_type' => $fieldType,
            'field_hook' => 'default',
        ]);

        Schema::table('lara_form_contactforms', fn (Blueprint $table) => $column($table, $fieldName));
        $this->addedColumns[] = $fieldName;

        $this->forgetGuardableColumns();
    }

    /**
     * Laravel caches the columns of each model's table for mass assignment in
     * a static property that outlives the application. Cleared whenever a test
     * adds or drops a column, so neither this test nor the next sees a stale
     * column list.
     */
    private function forgetGuardableColumns(): void
    {
        (fn () => static::$guardableColumns = [])->bindTo(null, Model::class)();
    }

    private function makeContactMenuItem(): void
    {
        $entity = Entity::where('resource_slug', 'contactforms')->first();
        $menuId = MenuItem::langIs('nl')->value('menu_id');

        if (! $entity || ! $menuId) {
            $this->markTestSkipped('No contactforms entity or Dutch menu in the current database.');
        }

        $menuItem = MenuItem::create([
            'language' => 'nl',
            'menu_id' => $menuId,
            'title' => 'zz contact test',
            'type' => 'form',
            'route' => self::FORM_ROUTE,
            'entity_id' => $entity->id,
            'entity_view_id' => $entity->views()->where('method', 'form')->value('id'),
            'publish' => 1,
        ]);

        $menuItem->routename = MenuRouteName::forMenuItem($menuItem, $menuItem->entity, $menuItem->entityview);
        $menuItem->save();
    }
}

<?php

namespace App\Console\Commands\LaraV8\Support;

use App\Console\Commands\LaraV8\Support\Concerns\SyncsRows;
use Illuminate\Support\Facades\DB;
use Lara\Admin\Resources\Translations\Concerns\HasTranslations;
use Symfony\Component\Finder\Finder;

/**
 * Imports the v8 settings, languages, blacklist and translations.
 *
 * - Settings: the site's own values (company, general) win over the essential seed, matched
 *   on cgroup + key; system values (versions, sync timestamps) belong to v10 and are skipped;
 *   v10-only settings are never removed.
 * - Translations: the site's labels (v8 module lara-eve) become lara-app labels of the v10
 *   resource (column, entity → entity/navigation/model, formfield → column, …); v8 lara-front
 *   texts replace the v10 ones with the same key, and are added for keys the v10 code uses
 *   (lara-front::group.tag.key in laracms/) but the v10 seed lacks; v8 lara-admin/lara-common
 *   are the v8 admin's own texts and are skipped. v8 placeholders ("_key") never overwrite. Afterwards the
 *   translations are exported to lang/vendor, as the admin does.
 * - Header tags (heading levels per template) have no v10 counterpart and are reported.
 */
class SystemImporter
{
    use HasTranslations;
    use SyncsRows;

    /** v8 setting groups that are not imported: system (versions, sync timestamps) belongs to v10; opengraph is handled by filarank. */
    private const SKIPPED_SETTING_GROUPS = ['system', 'opengraph'];

    /** v8 lara-eve tag/key => v10 [tag, key] for the entity labels. */
    private const ENTITY_LABELS = [
        'entity_title' => [['entity', 'title'], ['navigation', 'label']],
        'entity_single' => [['model', 'label_single']],
        'entity_plural' => [['model', 'label_plural']],
    ];

    /** @var list<array{level: string, entity: string, message: string}> */
    private array $report = [];

    public function __construct(private readonly V8Source $source) {}

    /**
     * @return list<array{level: string, entity: string, message: string}>
     */
    public function run(bool $dryRun = false, bool $fresh = false): array
    {
        $this->report = [];

        $this->settings($dryRun);
        $this->sync('languages', 'lara_sys_languages', ['id'], $this->source->connection()->table('lara_sys_languages')->orderBy('id')->get()->map(fn (object $row): array => (array) $row), $dryRun, $fresh);
        $this->sync('blacklist', 'lara_sys_blacklist', ['id'], $this->source->connection()->table('lara_sys_blacklist')->orderBy('id')->get()->map(fn (object $row): array => (array) $row), $dryRun, $fresh);
        $this->headerTags();
        $this->translations($dryRun);

        if (! $dryRun) {
            $files = static::exportTranslationsToFile();
            $this->add('info', 'translations', "Exported the translations to lang/vendor ({$files} file(s)).");
        }

        return $this->report;
    }

    private function settings(bool $dryRun): void
    {
        $v8 = $this->source->connection()->table('lara_sys_settings')->orderBy('id')->get();
        $skipped = $v8->whereIn('cgroup', self::SKIPPED_SETTING_GROUPS)->count();

        if ($skipped > 0) {
            $this->add('info', 'settings', "{$skipped} v8 setting(s) of the groups ".implode(', ', self::SKIPPED_SETTING_GROUPS).' not imported: system values belong to v10, opengraph is handled by filarank.');
        }

        $rows = collect();

        foreach ($v8->whereNotIn('cgroup', self::SKIPPED_SETTING_GROUPS)->groupBy(fn (object $row): string => $row->cgroup.'#'.$row->key) as $settings) {
            // v8 can hold a key twice; the row with a value wins
            $setting = $settings->first(fn (object $row): bool => ! V8Source::isEmpty($row->value)) ?? $settings->first();

            $rows->push([
                'cgroup' => $setting->cgroup,
                'key' => $setting->key,
                'title' => $setting->title,
                'value' => V8Source::isEmpty($setting->value) ? null : $setting->value,
                'locked_by_admin' => (int) $setting->locked_by_admin,
                'position' => $setting->position,
                'created_at' => $setting->created_at,
                'updated_at' => $setting->updated_at,
            ]);
        }

        $v10Groups = DB::table('lara_sys_settings')->distinct()->pluck('cgroup')->all();

        foreach ($rows->pluck('cgroup')->unique()->diff($v10Groups) as $group) {
            $this->add('warning', 'settings', "v8 setting group '{$group}' does not exist in v10 (".$rows->where('cgroup', $group)->pluck('key')->implode(', ').'); imported, check whether v10 uses it.');
        }

        // never fresh: v10-only settings (e.g. company_2fa_app_name, system values) stay
        $this->sync('settings', 'lara_sys_settings', ['cgroup', 'key'], $rows, $dryRun, false, reportExtra: false);
    }

    private function headerTags(): void
    {
        $count = $this->source->connection()->table('lara_sys_headertags')->count();

        if ($count > 0) {
            $this->add('info', 'header tags', "{$count} v8 header tag setting(s) (heading levels per template) not imported; v10 sets the headings in the theme views.");
        }
    }

    private function translations(bool $dryRun): void
    {
        $v8 = $this->source->connection()->table('lara_sys_translations')->whereNull('deleted_at')->get();
        $contentKeys = $this->source->contentEntities()->pluck('entity_key')->all();
        $rows = [];
        $placeholders = 0;

        // the site's own labels: lara-eve → lara-app; column labels before form field labels
        foreach ($v8->where('module', 'lara-eve')->sortBy(fn (object $row): int => $row->tag === 'formfield' ? 1 : 0) as $row) {
            if (! in_array($row->cgroup, $contentKeys, true)) {
                continue;
            }

            if ($this->isPlaceholder($row->value)) {
                $placeholders++;

                continue;
            }

            foreach ($this->appLabelTargets($row) as [$tag, $key]) {
                $rows += [$this->translationKey($row->language, 'lara-app', V8Map::resourceSlug($row->cgroup), $tag, $key) => $row->value];
            }
        }

        // front texts: keys the v10 database knows, and keys the v10 code uses that its seed lacks
        $v10Front = DB::table('lara_sys_translations')->where('module', 'lara-front')->get()
            ->mapWithKeys(fn (object $row): array => [$this->translationKey($row->language, 'lara-front', $row->resource, $row->tag, $row->key) => true]);
        $usedFront = $this->frontKeysUsedInCode();
        $unknownFront = [];
        $addedFront = [];

        foreach ($v8->where('module', 'lara-front') as $row) {
            $key = $this->translationKey($row->language, 'lara-front', $row->cgroup, $row->tag, $row->key);
            $name = "{$row->cgroup}.{$row->tag}.{$row->key}";

            if (! isset($v10Front[$key]) && ! isset($usedFront[$name])) {
                $unknownFront[$name] = true;
            } elseif (! $this->isPlaceholder($row->value)) {
                $rows += [$key => $row->value];

                if (! isset($v10Front[$key])) {
                    $addedFront[$name] = true;
                }
            }
        }

        $admin = $v8->whereIn('module', ['lara-admin', 'lara-common'])->count();
        $this->add('info', 'translations', "{$admin} v8 admin translation(s) (lara-admin, lara-common) not imported; v10 has its own admin texts.");

        if ($addedFront !== []) {
            $this->add('info', 'translations', count($addedFront).' v8 front text(s) (lara-front) imported for keys the v10 code uses but the v10 seed lacks: '.implode(', ', array_keys($addedFront)).'.');
        }

        if ($unknownFront !== []) {
            ksort($unknownFront);
            $this->add('info', 'translations', count($unknownFront).' v8 front text(s) (lara-front) have no key in v10 and are not used by the v10 code; not imported: '.implode(', ', array_keys($unknownFront)).'.');
        }

        if ($placeholders > 0) {
            $this->add('info', 'translations', "{$placeholders} v8 placeholder label(s) (\"_key\") not imported; v10 fills its own.");
        }

        $translationRows = collect($rows)->map(function (string $value, string $key): array {
            [$language, $module, $resource, $tag, $translationKey] = explode('|', $key);

            return ['language' => $language, 'module' => $module, 'resource' => $resource, 'tag' => $tag, 'key' => $translationKey, 'value' => $value];
        })->values();

        // never fresh: the v10 translations of the admin and the theme stay
        $this->sync('translations', 'lara_sys_translations', ['language', 'module', 'resource', 'tag', 'key'], $translationRows, $dryRun, false, reportExtra: false);
    }

    /**
     * The lara-front keys ("group.tag.key") the v10 code uses: core, themes and app under laracms/.
     *
     * @return array<string, true>
     */
    private function frontKeysUsedInCode(): array
    {
        $keys = [];
        $finder = Finder::create()->files()->name('*.php')->in(base_path('laracms'))->followLinks()->ignoreUnreadableDirs();

        foreach ($finder as $file) {
            if (preg_match_all('/lara-front::([a-z0-9_]+\.[a-z0-9_]+\.[a-z0-9_]+)/i', $file->getContents(), $matches)) {
                $keys += array_fill_keys($matches[1], true);
            }
        }

        return $keys;
    }

    /**
     * The v10 lara-app [tag, key] pairs of a v8 lara-eve label.
     *
     * @return list<array{0: string, 1: string}>
     */
    private function appLabelTargets(object $row): array
    {
        if ($row->tag === 'entity') {
            return self::ENTITY_LABELS[$row->key] ?? [];
        }

        if (in_array($row->tag, ['column', 'formfield'], true)) {
            return [['column', V8Map::contentColumn($row->cgroup, $row->key, $this->hasGeolocation($row->cgroup)) ?? $row->key]];
        }

        return [[$row->tag, $row->key]];
    }

    private function hasGeolocation(string $entityKey): bool
    {
        static $cache = [];

        return $cache[$entityKey] ??= $this->source->connection()->table('lara_ent_entity_custom_columns as c')
            ->join('lara_ent_entities as e', 'e.id', '=', 'c.entity_id')
            ->where('e.entity_key', $entityKey)->where('c.fieldtype', 'geolocation')->exists();
    }

    private function isPlaceholder(?string $value): bool
    {
        return V8Source::isEmpty($value) || str_starts_with((string) $value, '_');
    }

    private function translationKey(string $language, string $module, string $resource, string $tag, string $key): string
    {
        return implode('|', [$language, $module, $resource, $tag, $key]);
    }

    private function add(string $level, string $entity, string $message): void
    {
        $this->report[] = ['level' => $level, 'entity' => $entity, 'message' => $message];
    }
}

<?php

namespace App\Console\Commands\LaraV8\Support;

use Filament\Notifications\NotificationMailbox;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Lara\Admin\Concerns\HasLaraBuilder;
use Lara\Admin\Resources\Entities\RelationManagers\Concerns\HasRelations;
use Lara\Common\Entities\EntityFieldName;
use Lara\Common\Models\Entity;
use Lara\Common\Models\EntityCustomField;
use Lara\Common\Models\EntityRelation;
use Lara\Common\Models\EntityView;

/**
 * Builds the v10 content types of a v8 site from its v8 configuration, through the same
 * builder the admin uses (HasLaraBuilder, HasRelations): code, table, custom fields,
 * relations and front views, as a webmaster creating them by hand would get.
 *
 * Only the site's own content types: the core ones (V8Map::CORE_ENTITIES) keep their v10
 * configuration. Rerunnable: what exists and matches is left alone; a difference is
 * reported, and only applied with $update.
 */
class EntityImporter
{
    use HasLaraBuilder;
    use HasRelations;

    /**
     * Entity attributes the builder derives from label_single; never compared or overwritten.
     */
    private const BUILDER_ATTRIBUTES = ['resource_slug', 'label_single', 'model_class', 'controller', 'resource', 'policy'];

    /** @var list<array{level: string, entity: string, message: string}> */
    private array $report = [];

    private RecordingNotificationMailbox $mailbox;

    public function __construct(private readonly V8Source $source)
    {
        $this->mailbox = new RecordingNotificationMailbox;
        app()->instance(NotificationMailbox::class, $this->mailbox);
    }

    /**
     * @param  list<string>  $only  v8 entity keys; empty for all site content types
     * @return list<array{level: string, entity: string, message: string}>
     */
    public function run(array $only = [], bool $dryRun = false, bool $update = false): array
    {
        $this->report = [];

        $v8Entities = $this->siteEntities($only);

        foreach ($v8Entities as $v8) {
            $entity = $this->importEntity($v8, $dryRun, $update);

            if ($entity !== null) {
                $this->importFields($v8, $entity, $dryRun, $update);
                $this->importViews($v8, $entity, $dryRun, $update);
                $this->ensureFrontScope($v8->entity_key, $entity, $dryRun);
            } elseif ($dryRun && isset(V8Map::FRONT_SCOPES[$v8->entity_key])) {
                $this->add('create', $v8->entity_key, 'Front scope scopeFront() (stock v8) would be added to the model.');
            }
        }

        // relations last: both sides must exist
        foreach ($v8Entities as $v8) {
            $this->importRelations($v8, $dryRun);
        }

        $this->importCoreOptions($only, $dryRun);
        $this->importCoreFields($only, $dryRun, $update);
        $this->importColumnFields($only, $dryRun, $update);
        $this->importPageTemplates($only, $dryRun);
        $this->reportSkipped($only);

        return $this->report;
    }

    /**
     * v8 page templates are the theme's single_{template} views; v10 also needs a page view row
     * per template: the admin page form looks the page's template up there (HasContentSection,
     * a 500 without it) and offers the views as the template choice. A template that a v8 page
     * (not trashed) uses and v10 lacks is added as a show view (user decision 2026-11-23,
     * doormode: wallpaper*, landing*; seerden: instagramfeed). template_extra_fields is 1 when a
     * page with that template has a v8 lead (imported into body2), so the admin shows body2.
     *
     * @param  list<string>  $only
     */
    private function importPageTemplates(array $only, bool $dryRun): void
    {
        if ($only !== [] && ! in_array('page', $only, true)) {
            return;
        }

        $pages = Entity::where('resource_slug', 'pages')->first();
        $table = $this->source->contentTable('page', 'page');

        if ($pages === null || $table === null || ! $this->source->hasTable($table)) {
            return;
        }

        $columns = $this->source->columns($table);

        // an older v8 pages table has no template column (inventiverepair): every page uses the default view
        if (! in_array('template', $columns, true)) {
            $this->add('info', 'page', "Page template column missing in v8 {$table}; every page keeps the default page view.");

            return;
        }

        $existing = EntityView::where('entity_id', $pages->id)->pluck('template')->filter()->all();
        // a page lead imported into its own field (SiteFields) needs no extra body fields
        $hasLead = in_array('lead', $columns, true) && SiteFields::columnTarget('page', 'lead') === null;

        $used = $this->source->connection()->table($table)
            ->whereNull('deleted_at')
            ->whereNotNull('template')->where('template', '!=', '')->where('template', '!=', 'NULL')
            ->get($hasLead ? ['template', 'lead'] : ['template'])
            ->groupBy('template');

        foreach ($used as $template => $rows) {
            $template = (string) $template;

            if (in_array($template, $existing, true)) {
                $this->add('same', 'page', "Page template '{$template}' exists.");

                continue;
            }

            $withLead = $hasLead && $rows->contains(fn (object $row): bool => trim(strip_tags((string) $row->lead)) !== '' && ! V8Source::isEmpty($row->lead));
            $extraFields = $withLead ? 1 : 0;
            $message = "Page template '{$template}' ({$rows->count()} page(s), extra body fields {$extraFields})";

            if ($dryRun) {
                $this->add('create', 'page', "{$message} would be added as a page view.");

                continue;
            }

            (new EntityView)->forceFill([
                'entity_id' => $pages->id,
                'title' => 'Page '.ucfirst($template),
                'method' => 'show',
                'filename' => 'show',
                'template' => $template,
                'template_extra_fields' => $extraFields,
                'is_single' => 1,
                'list_type' => '_single',
                'image_required' => 0,
                'infinite' => 0,
                'prevnext' => 0,
                'publish' => 1,
            ])->save();

            $this->add('create', 'page', "{$message} added as a page view.");
        }
    }

    /**
     * Skipped content types (v8-migration.skip_entities) are not built; one that v10 already
     * has (built before it was skipped) is left alone and reported.
     *
     * @param  list<string>  $only
     */
    private function reportSkipped(array $only): void
    {
        foreach ($this->source->skippedEntities() as $v8) {
            $key = $v8->entity_key;

            if ($only !== [] && ! in_array($key, $only, true)) {
                continue;
            }

            if (Entity::where('resource_slug', V8Map::resourceSlug($key))->exists()) {
                $this->add('warning', $key, 'Skipped (v8-migration.skip_entities), but v10 has the entity '.V8Map::resourceSlug($key).'; remove it in the admin if it is not wanted.');

                continue;
            }

            $this->add('info', $key, 'Skipped (v8-migration.skip_entities): not built.');
        }
    }

    /**
     * Core content types keep their v10 fields; values their live v8 records use but the
     * v10 options lack are added to those options (CoreFieldValues), through the builder.
     *
     * @param  list<string>  $only
     */
    private function importCoreOptions(array $only, bool $dryRun): void
    {
        foreach ((new CoreFieldValues($this->source))->fields() as $row) {
            $key = $row['entity_key'];

            if (($only !== [] && ! in_array($key, $only, true)) || $row['add'] === []) {
                continue;
            }

            $field = $row['field'];
            $label = "Option field '{$field->field_name}' of core entity '{$field->entity->resource_slug}'";
            $added = implode(', ', $row['add']);

            if ($dryRun) {
                $this->add('update', $key, "{$label}: would get the option(s) {$added}.");

                continue;
            }

            $previous = $field->getAttributes();
            $field->forceFill(['field_options' => [...(array) $field->field_options, ...$row['add']]])->save();

            if (static::buildCustomField($field, $previous)) {
                $this->add('update', $key, "{$label}: added the option(s) {$added} (used by live v8 records).");
            } else {
                $this->add('error', $key, "{$label}: the options could not be saved: ".implode(' | ', $this->mailbox->takeMessages()));
            }

            $this->addBuilderMessages($key);
        }
    }

    /**
     * The v8 site content types to import (not the core ones).
     *
     * @param  list<string>  $only
     * @return Collection<int, object>
     */
    public function siteEntities(array $only = []): Collection
    {
        return $this->source->contentEntities()
            ->reject(fn (object $entity): bool => V8Map::isCoreEntity($entity->entity_key))
            ->filter(fn (object $entity): bool => $only === [] || in_array($entity->entity_key, $only, true))
            ->values();
    }

    private function importEntity(object $v8, bool $dryRun, bool $update): ?Entity
    {
        $key = $v8->entity_key;
        $attributes = V8Map::entity(
            $v8,
            $this->v8Row('lara_ent_entity_columns', $v8->id),
            $this->v8Row('lara_ent_entity_panels', $v8->id),
            $this->v8Row('lara_ent_entity_object_relations', $v8->id),
        );

        foreach (['cgroup', 'nav_group'] as $required) {
            if ($attributes[$required] === null) {
                $this->add('error', $key, "No v10 {$required} for this content type; skipped.");

                return null;
            }
        }

        $entity = Entity::where('resource_slug', $attributes['resource_slug'])->first();

        if ($entity === null) {
            if ($dryRun) {
                $this->add('create', $key, "Entity '{$attributes['resource_slug']}' ({$attributes['cgroup']}) would be created.");

                // unsaved: lets the dry run plan the fields and views of the new entity
                return (new Entity)->forceFill($attributes);
            }

            $entity = new Entity;
            $entity->forceFill($attributes)->save();

            if (! static::buildEntity($entity)) {
                $this->add('error', $key, 'The builder could not create the entity: '.implode(' | ', $this->mailbox->takeMessages()));

                return null;
            }

            // createEntity() names the entity after its model ("Blogs"); the webmaster's title wins
            $entity->refresh()->forceFill(['title' => $attributes['title']])->save();

            $this->add('create', $key, "Entity '{$entity->resource_slug}' created ({$entity->model_class}).");
            $this->addBuilderMessages($key);
            $this->checkFormController($key, $entity);

            return $entity;
        }

        $this->checkFormController($key, $entity);

        $differences = $this->differences($entity, Arr::except($attributes, self::BUILDER_ATTRIBUTES));

        if ($differences === []) {
            $this->add('same', $key, "Entity '{$entity->resource_slug}' exists and matches.");
        } elseif ($update && ! $dryRun) {
            $entity->forceFill(Arr::only($attributes, array_keys($differences)))->save();
            $this->add('update', $key, "Entity '{$entity->resource_slug}' updated: ".$this->describe($differences));
        } else {
            $this->add('differs', $key, "Entity '{$entity->resource_slug}' differs from v8: ".$this->describe($differences));
        }

        return $entity;
    }

    private function importFields(object $v8, Entity $entity, bool $dryRun, bool $update): void
    {
        $key = $v8->entity_key;
        $v8Fields = $this->source->connection()->table('lara_ent_entity_custom_columns')
            ->where('entity_id', $v8->id)
            ->orderBy('position')
            ->get();
        $geoParts = V8Map::geolocationParts($v8Fields);
        $isForm = $entity->cgroup === 'form';
        $done = [];

        foreach ($v8Fields as $v8Field) {
            $name = $v8Field->fieldname;

            if (in_array($name, $done, true)) {
                $this->add('warning', $key, "Field '{$name}' is defined more than once in v8; imported once.");

                continue;
            }

            $done[] = $name;

            if (in_array($name, $geoParts, true)) {
                $this->add('info', $key, "Field '{$name}' becomes part of the geolocation field (column ".V8Map::GEO_FIELDS[$name].').');

                continue;
            }

            if (SiteFields::isSkipped($key, $name)) {
                $this->add('info', $key, "Field '{$name}' left out (".basename(SiteFields::file()).'): not built, its values are not imported.');

                if ($entity->exists && $entity->customfields()->where('field_name', $name)->exists()) {
                    $this->add('warning', $key, "Field '{$name}' is left out but exists in v10 (built before); remove it in the admin builder, or start over with prepare-target.");
                }

                continue;
            }

            if (($rejection = EntityFieldName::reject($name)) !== null) {
                $this->add('error', $key, "Field '{$name}' cannot be used in v10: {$rejection}");

                continue;
            }

            $attributes = $this->fieldAttributes($key, $v8Field, $isForm);

            if ($attributes !== null) {
                $this->importField($key, $entity, $attributes, $dryRun, $update);
            }
        }
    }

    /**
     * The v10 attributes of a v8 custom field, or null when a v8 value has no v10 counterpart
     * (reported); changes in behaviour are reported.
     *
     * @return array<string, mixed>|null
     */
    private function fieldAttributes(string $key, object $v8Field, bool $isForm): ?array
    {
        $name = $v8Field->fieldname;
        $attributes = V8Map::customField($v8Field, $isForm);

        foreach (['field_type' => $v8Field->fieldtype, 'field_hook' => $v8Field->fieldhook, 'rule_state' => $v8Field->fieldstate] as $attribute => $v8Value) {
            if ($attributes[$attribute] === null) {
                $this->add('error', $key, "Field '{$name}': the v8 value '{$v8Value}' has no v10 {$attribute}; skipped.");

                return null;
            }
        }

        [$attributes, $problem] = SiteFields::applyTo($key, $attributes);

        if ($attributes === null) {
            $this->add('error', $key, "Field '{$name}': {$problem}; skipped.");

            return null;
        }

        if (isset(SiteFields::get($key, $name)['type'])) {
            $this->add('info', $key, "Field '{$name}' ({$v8Field->fieldtype}) becomes {$attributes['field_type']} (".basename(SiteFields::file()).').');
        } elseif (($change = V8Map::fieldTypeChange($v8Field->fieldtype)) !== null) {
            $this->add('warning', $key, "Field '{$name}' ({$v8Field->fieldtype}): {$change}.");
        }

        if (V8Map::hasMultipleRuleValues($v8Field->condition_value)) {
            $this->add('warning', $key, "Field '{$name}' is enabled for several values ('{$v8Field->condition_value}'); v10 compares one value.");
        }

        return $attributes;
    }

    /**
     * The site's own v8 custom fields on core content types (CoreCustomFields): built like a site
     * field when live records use them, otherwise reported.
     *
     * @param  list<string>  $only
     */
    private function importCoreFields(array $only, bool $dryRun, bool $update): void
    {
        foreach ((new CoreCustomFields($this->source))->candidates() as $candidate) {
            $key = $candidate['entity_key'];
            $v8Field = $candidate['v8_field'];
            $slug = $candidate['entity']->resource_slug;

            if ($only !== [] && ! in_array($key, $only, true)) {
                continue;
            }

            if ($candidate['live'] === 0) {
                $this->add('info', $key, "v8 custom field '{$v8Field->fieldname}' of core entity '{$slug}' is not used by live records; not added (its values are not imported).");

                continue;
            }

            if (($rejection = EntityFieldName::reject($v8Field->fieldname)) !== null) {
                $this->add('error', $key, "Field '{$v8Field->fieldname}' cannot be used in v10: {$rejection}");

                continue;
            }

            $attributes = $this->fieldAttributes($key, $v8Field, false);

            if ($attributes !== null) {
                $this->add('info', $key, "v8 custom field '{$v8Field->fieldname}' of core entity '{$slug}' is used by {$candidate['live']} live record(s): added as a custom field.");
                $this->importField($key, $candidate['entity'], $attributes, $dryRun, $update);
            }
        }
    }

    /**
     * v8 content columns that become a v10 custom field (SiteFields `field`, v8-fields.php; gain:
     * the page lead → intro), on a core or a site content type.
     *
     * @param  list<string>  $only
     */
    private function importColumnFields(array $only, bool $dryRun, bool $update): void
    {
        foreach ($this->source->contentEntities() as $v8) {
            $key = $v8->entity_key;
            $settings = SiteFields::columnFields($key);

            if ($settings === [] || ($only !== [] && ! in_array($key, $only, true))) {
                continue;
            }

            $table = $this->source->contentTable($key, $v8->group_key);
            $columns = $table !== null && $this->source->hasTable($table) ? $this->source->columns($table) : [];
            $entity = Entity::where('resource_slug', V8Map::resourceSlug($key))->first();

            foreach ($settings as $column => $setting) {
                $name = (string) $setting['field'];

                if (! in_array($column, $columns, true)) {
                    $this->add('error', $key, "v8 column '{$column}' (".basename(SiteFields::file()).') does not exist in v8; field not built.');

                    continue;
                }

                if (($rejection = EntityFieldName::reject($name)) !== null) {
                    $this->add('error', $key, "Field '{$name}' cannot be used in v10: {$rejection}");

                    continue;
                }

                [$attributes, $problem] = SiteFields::columnFieldAttributes($setting);

                if ($attributes === null) {
                    $this->add('error', $key, "Field '{$name}': {$problem}; skipped.");

                    continue;
                }

                if ($entity === null) {
                    $this->add($dryRun ? 'create' : 'error', $key, "Field '{$name}' (v8 column {$column}) ".($dryRun ? 'would be created once the entity exists.' : 'not built: no v10 entity.'));

                    continue;
                }

                $this->add('info', $key, "v8 column '{$column}' becomes the custom field '{$name}' ({$attributes['field_type']}, ".basename(SiteFields::file()).').');
                $this->importField($key, $entity, $attributes, $dryRun, $update);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function importField(string $key, Entity $entity, array $attributes, bool $dryRun, bool $update): void
    {
        $name = $attributes['field_name'];
        $field = $entity->exists ? $entity->customfields()->where('field_name', $name)->first() : null;

        if ($field === null) {
            if ($dryRun) {
                $this->add('create', $key, "Field '{$name}' ({$attributes['field_type']}) would be created.");

                return;
            }

            $field = new EntityCustomField;
            $field->forceFill([...$attributes, 'entity_id' => $entity->id])->save();

            if (static::buildCustomField($field, null)) {
                $this->add('create', $key, "Field '{$name}' ({$attributes['field_type']}) created.");
            } else {
                $this->add('error', $key, "Field '{$name}' could not be created: ".implode(' | ', $this->mailbox->takeMessages()));
            }

            $this->addBuilderMessages($key);

            return;
        }

        $differences = $this->differences($field, $attributes);

        if ($differences === []) {
            $this->add('same', $key, "Field '{$name}' exists and matches.");
        } elseif ($update && ! $dryRun) {
            $previous = $field->getAttributes();
            $field->forceFill(Arr::only($attributes, array_keys($differences)))->save();

            if (static::buildCustomField($field, $previous)) {
                $this->add('update', $key, "Field '{$name}' updated: ".$this->describe($differences));
            } else {
                $this->add('error', $key, "Field '{$name}' could not be updated: ".implode(' | ', $this->mailbox->takeMessages()));
            }

            $this->addBuilderMessages($key);
        } else {
            $this->add('differs', $key, "Field '{$name}' differs from v8: ".$this->describe($differences));
        }
    }

    private function importViews(object $v8, Entity $entity, bool $dryRun, bool $update): void
    {
        $key = $v8->entity_key;
        $v8Views = $this->source->connection()->table('lara_ent_entity_views')->where('entity_id', $v8->id)->orderBy('id')->get();
        $objectRelations = $this->v8Row('lara_ent_entity_object_relations', $v8->id);

        foreach ($v8Views as $v8View) {
            $attributes = V8Map::view($v8View, $entity->title, $key, $objectRelations);
            $view = ! $entity->exists ? null : EntityView::where('entity_id', $entity->id)
                ->where('method', $attributes['method'])
                ->where('filename', $attributes['filename'])
                ->first();
            $label = "View '{$attributes['method']}'";

            if ($view === null) {
                if (! $dryRun) {
                    (new EntityView)->forceFill([...$attributes, 'entity_id' => $entity->id])->save();
                }

                $this->add('create', $key, $label.($dryRun ? ' would be created.' : ' created.'));

                continue;
            }

            $differences = $this->differences($view, Arr::except($attributes, ['title']));

            if ($differences === []) {
                $this->add('same', $key, "{$label} exists and matches.");
            } elseif ($update && ! $dryRun) {
                $view->forceFill(Arr::only($attributes, array_keys($differences)))->save();
                $this->add('update', $key, "{$label} updated: ".$this->describe($differences));
            } else {
                $this->add('differs', $key, "{$label} differs from v8: ".$this->describe($differences));
            }
        }
    }

    /**
     * v8 keeps both sides of a relation; v10 stores the hasMany/hasOne side and
     * HasRelations::checkRelations() adds the belongsTo side and its foreign key column.
     */
    private function importRelations(object $v8, bool $dryRun): void
    {
        $key = $v8->entity_key;
        $entity = Entity::where('resource_slug', V8Map::resourceSlug($key))->first();
        $v8Keys = $this->source->allEntities()->pluck('entity_key', 'id');

        foreach ($this->source->connection()->table('lara_ent_entity_relations')->where('entity_id', $v8->id)->get() as $v8Relation) {
            $relatedKey = $v8Keys[$v8Relation->related_entity_id] ?? null;

            if ($this->source->isSkipped($relatedKey)) {
                $this->add('info', $key, "Relation {$v8Relation->type} {$relatedKey}: {$relatedKey} is skipped (v8-migration.skip_entities); not built.");

                continue;
            }

            $related = $relatedKey ? Entity::where('resource_slug', V8Map::resourceSlug($relatedKey))->first() : null;
            $label = "Relation {$v8Relation->type} {$relatedKey}";

            if ($v8Relation->type === 'belongsTo') {
                // created together with the hasMany side of the related entity
                continue;
            }

            if ($entity === null || $related === null) {
                $this->add($dryRun ? 'create' : 'error', $key, "{$label}: ".($dryRun ? 'would be created once both entities exist.' : 'an entity is missing; skipped.'));

                continue;
            }

            $relation = EntityRelation::where('entity_id', $entity->id)
                ->where('type', $v8Relation->type)
                ->where('related_entity_id', $related->id)
                ->first();

            if ($relation !== null) {
                $this->add('same', $key, "{$label} exists.");
                $this->ensureRelationMethods($key, $entity, $related, $v8Relation->type, $relation->foreign_key, $dryRun);

                continue;
            }

            if ($dryRun) {
                $this->add('create', $key, "{$label} (foreign key {$v8Relation->foreign_key}) would be created.");

                continue;
            }

            (new EntityRelation)->forceFill([
                'entity_id' => $entity->id,
                'type' => $v8Relation->type,
                'related_entity_id' => $related->id,
                'foreign_key' => $v8Relation->foreign_key,
                'is_filter' => (int) $v8Relation->is_filter,
            ])->save();

            // the methods first, so checkRelations() finds them in the models
            $this->ensureRelationMethods($key, $entity, $related, $v8Relation->type, $v8Relation->foreign_key, $dryRun);

            static::checkRelations($entity->refresh());
            static::checkRelations($related->refresh());

            // the belongsTo side gets the filter setting of its v8 counterpart
            $v8BelongsTo = $this->source->connection()->table('lara_ent_entity_relations')
                ->where('entity_id', $v8Relation->related_entity_id)
                ->where('related_entity_id', $v8->id)
                ->where('type', 'belongsTo')
                ->first();

            if ($v8BelongsTo !== null) {
                EntityRelation::where('entity_id', $related->id)
                    ->where('related_entity_id', $entity->id)
                    ->where('type', 'belongsTo')
                    ->update(['is_filter' => (int) $v8BelongsTo->is_filter]);
            }

            $this->add('create', $key, "{$label} created, with belongsTo {$key} on {$relatedKey} (foreign key {$v8Relation->foreign_key}).");

            foreach ($this->mailbox->takeMessages() as $message) {
                // a model class loaded before its method was added still reports it as missing
                if (! str_starts_with($message, 'Relation not found in model')) {
                    $this->add('todo', $key, $message);
                }
            }
        }
    }

    /**
     * The model methods a relation needs, which the builder leaves to a developer; they are
     * added to the generated site models when missing (user decision 2026-11-16, option A):
     * hasMany/hasOne → {related resource_slug|label_single}() on the model,
     * belongsTo → {label_single}() on the related model, both on the foreign key.
     */
    private function ensureRelationMethods(string $key, Entity $entity, Entity $related, string $type, string $foreignKey, bool $dryRun): void
    {
        $methods = [
            [$entity->model_class, $type === 'hasMany' ? $related->resource_slug : $related->label_single, $type, $related->model_class],
            [$related->model_class, $entity->label_single, 'belongsTo', $entity->model_class],
        ];

        foreach ($methods as [$modelClass, $method, $relationType, $relatedClass]) {
            $file = RelationMethodWriter::modelFile($modelClass, base_path('laracms/app'));
            $signature = "{$modelClass}::{$method}() ({$relationType})";

            if ($file === null) {
                if (! method_exists($modelClass, $method)) {
                    $this->add('todo', $key, "Add the relation method {$signature}; it is not a generated site model.");
                }

                continue;
            }

            if (RelationMethodWriter::hasMethod($file, $method)) {
                $this->add('same', $key, "Relation method {$signature} exists.");
            } elseif ($dryRun) {
                $this->add('update', $key, "Relation method {$signature} would be added.");
            } elseif (RelationMethodWriter::add($file, $method, $relationType, $relatedClass, $foreignKey)) {
                $this->add('update', $key, "Relation method {$signature} added.");
            } else {
                $this->add('error', $key, "Relation method {$signature} could not be added to {$file}.");
            }
        }
    }

    /**
     * The stock v8 front scope of the content type (V8Map::FRONT_SCOPES), added to the generated
     * site model when missing (user decision 2026-11-23, doormode): without it v10 lists records
     * the v8 front hid (doormode, seerden: past events). Not added when the v10 entity lacks the
     * field the condition needs.
     */
    private function ensureFrontScope(string $key, Entity $entity, bool $dryRun): void
    {
        if (! isset(V8Map::FRONT_SCOPES[$key])) {
            return;
        }

        [$field, $condition] = V8Map::FRONT_SCOPES[$key];
        $file = RelationMethodWriter::modelFile($entity->model_class, base_path('laracms/app'));

        if ($file === null) {
            return;
        }

        if (! $entity->customfields()->where('field_name', $field)->exists()) {
            $this->add('warning', $key, "Front scope scopeFront() (stock v8) not added: the entity has no field '{$field}'.");
        } elseif (RelationMethodWriter::hasMethod($file, 'scopeFront')) {
            $this->add('same', $key, "Front scope {$entity->model_class}::scopeFront() exists.");
        } elseif ($dryRun) {
            $this->add('update', $key, "Front scope {$entity->model_class}::scopeFront() (stock v8: {$condition}) would be added.");
        } elseif (RelationMethodWriter::addFrontScope($file, $key, $condition)) {
            $this->add('update', $key, "Front scope {$entity->model_class}::scopeFront() (stock v8: {$condition}) added.");
        } else {
            $this->add('error', $key, "Front scope scopeFront() could not be added to {$file}.");
        }
    }

    /**
     * The front router serves a form through Lara\App\Http\Controllers\Front\Form\{Controller}
     * (extending AppFormController), which the builder does not generate.
     */
    private function checkFormController(string $key, Entity $entity): void
    {
        if ($entity->cgroup !== 'form') {
            return;
        }

        $path = base_path("laracms/app/Http/Controllers/Front/Form/{$entity->controller}.php");

        if (! is_file($path)) {
            $this->add('todo', $key, "Form front controller missing: laracms/app/Http/Controllers/Front/Form/{$entity->controller}.php (extends AppFormController, with make()). The builder does not generate it, so the form has no front route yet.");
        }
    }

    private function v8Row(string $table, int $entityId): ?object
    {
        return $this->source->connection()->table($table)->where('entity_id', $entityId)->first();
    }

    /**
     * The attributes whose v10 value differs from the wanted value, as [attribute => [current, wanted]].
     *
     * @param  array<string, mixed>  $wanted
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    private function differences(object $model, array $wanted): array
    {
        $differences = [];

        foreach ($wanted as $attribute => $value) {
            $current = $model->{$attribute};

            if ($this->normalize($current) !== $this->normalize($value)) {
                $differences[$attribute] = [$current, $value];
            }
        }

        return $differences;
    }

    private function normalize(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode(array_values($value));
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    /**
     * @param  array<string, array{0: mixed, 1: mixed}>  $differences
     */
    private function describe(array $differences): string
    {
        return collect($differences)
            ->map(fn (array $values, string $attribute): string => "{$attribute} ".$this->normalize($values[0]).' → '.$this->normalize($values[1]))
            ->implode(', ');
    }

    /**
     * The builder's own notifications: confirmations become 'info' (shown with -v),
     * warnings and failures stay visible as 'builder'.
     */
    private function addBuilderMessages(string $key): void
    {
        foreach ($this->mailbox->takeNotifications() as $notification) {
            $this->add($notification['status'] === 'success' ? 'info' : 'builder', $key, $notification['message']);
        }
    }

    private function add(string $level, string $entity, string $message): void
    {
        $this->report[] = ['level' => $level, 'entity' => $entity, 'message' => $message];
    }
}

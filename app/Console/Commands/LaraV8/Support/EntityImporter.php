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
            }
        }

        // relations last: both sides must exist
        foreach ($v8Entities as $v8) {
            $this->importRelations($v8, $dryRun);
        }

        $this->importCoreOptions($only, $dryRun);

        return $this->report;
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

            if (($rejection = EntityFieldName::reject($name)) !== null) {
                $this->add('error', $key, "Field '{$name}' cannot be used in v10: {$rejection}");

                continue;
            }

            $attributes = V8Map::customField($v8Field, $isForm);

            foreach (['field_type' => $v8Field->fieldtype, 'field_hook' => $v8Field->fieldhook, 'rule_state' => $v8Field->fieldstate] as $attribute => $v8Value) {
                if ($attributes[$attribute] === null) {
                    $this->add('error', $key, "Field '{$name}': the v8 value '{$v8Value}' has no v10 {$attribute}; skipped.");

                    continue 2;
                }
            }

            if (($change = V8Map::fieldTypeChange($v8Field->fieldtype)) !== null) {
                $this->add('warning', $key, "Field '{$name}' ({$v8Field->fieldtype}): {$change}.");
            }

            if (V8Map::hasMultipleRuleValues($v8Field->condition_value)) {
                $this->add('warning', $key, "Field '{$name}' is enabled for several values ('{$v8Field->condition_value}'); v10 compares one value.");
            }

            $this->importField($key, $entity, $attributes, $dryRun, $update);
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

        foreach ($v8Views as $v8View) {
            $attributes = V8Map::view($v8View, $entity->title);
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
        $v8Keys = $this->source->entities()->pluck('entity_key', 'id');

        foreach ($this->source->connection()->table('lara_ent_entity_relations')->where('entity_id', $v8->id)->get() as $v8Relation) {
            $relatedKey = $v8Keys[$v8Relation->related_entity_id] ?? null;
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
                $this->checkRelationMethods($key, $entity, $related, $v8Relation->type);

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

            static::checkRelations($entity->refresh());
            // also on the related side, which reports the missing belongsTo method of its model
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
                $this->add('todo', $key, $message);
            }
        }
    }

    /**
     * The model methods a relation needs (the builder does not generate them):
     * hasMany → {related resource_slug}() on the model, belongsTo → {label_single}() on the related model.
     */
    private function checkRelationMethods(string $key, Entity $entity, Entity $related, string $type): void
    {
        $methods = [
            [$entity->model_class, $type === 'hasMany' ? $related->resource_slug : $related->label_single, $type],
            [$related->model_class, $entity->label_single, 'belongsTo'],
        ];

        foreach ($methods as [$modelClass, $method, $relationType]) {
            if (! method_exists($modelClass, $method)) {
                $this->add('todo', $key, "Add the {$relationType} method {$modelClass}::{$method}().");
            }
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

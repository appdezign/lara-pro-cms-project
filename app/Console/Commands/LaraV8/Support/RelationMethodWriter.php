<?php

namespace App\Console\Commands\LaraV8\Support;

/**
 * Adds a relation method to a generated site model (laracms/app/Models), the one step of a
 * relation the admin builder leaves to a developer (HasRelations only warns about it).
 *
 * Standard Eloquent only: hasMany/hasOne/belongsTo on the related model and the foreign key.
 * A method that already exists is never touched; core models are never written.
 */
final class RelationMethodWriter
{
    private const RELATION_TYPES = ['hasMany', 'hasOne', 'belongsTo'];

    /**
     * The file of a generated site model, or null for any other class.
     */
    public static function modelFile(string $modelClass, string $appPath): ?string
    {
        if (! str_starts_with($modelClass, 'Lara\\App\\Models\\')) {
            return null;
        }

        return rtrim($appPath, '/').'/Models/'.substr($modelClass, strlen('Lara\\App\\Models\\')).'.php';
    }

    public static function hasMethod(string $file, string $method): bool
    {
        return is_file($file) && preg_match('/function\s+'.preg_quote($method, '/').'\s*\(/', (string) file_get_contents($file)) === 1;
    }

    /**
     * Insert the method before the closing brace of the class.
     *
     * @return bool false when the file is missing, the type unknown or the method already exists
     */
    public static function add(string $file, string $method, string $type, string $relatedClass, string $foreignKey): bool
    {
        if (! in_array($type, self::RELATION_TYPES, true) || ! is_file($file) || self::hasMethod($file, $method)) {
            return false;
        }

        $returnType = '\\Illuminate\\Database\\Eloquent\\Relations\\'.ucfirst($type);
        $methodCode = <<<PHP

    /**
     * Added by lara:v8:import-entities for the {$type} relation of the entity configuration.
     */
    public function {$method}(): {$returnType}
    {
        return \$this->{$type}(\\{$relatedClass}::class, '{$foreignKey}');
    }

PHP;

        return self::insert($file, $methodCode);
    }

    /**
     * Add v8's stock front scope (V8Map::FRONT_SCOPES): scopeFront() limits the front lists and
     * entity widgets, in v8 and in v10 (FrontListBuilder calls it when the model has it).
     *
     * @return bool false when the file is missing or the model already has a scopeFront()
     */
    public static function addFrontScope(string $file, string $entityKey, string $condition): bool
    {
        if (! is_file($file) || self::hasMethod($file, 'scopeFront')) {
            return false;
        }

        $methodCode = <<<PHP

    /**
     * Added by lara:v8:import-entities: the stock v8 {$entityKey} model's front scope
     * (the front lists and widgets only show these records).
     */
    public function scopeFront(\\Illuminate\\Database\\Eloquent\\Builder \$query): \\Illuminate\\Database\\Eloquent\\Builder
    {
        return \$query->{$condition};
    }

PHP;

        return self::insert($file, $methodCode);
    }

    /**
     * Insert method code before the closing brace of the class.
     */
    private static function insert(string $file, string $methodCode): bool
    {
        $code = (string) file_get_contents($file);
        $position = strrpos($code, '}');

        if ($position === false) {
            return false;
        }

        $before = rtrim(substr($code, 0, $position));

        file_put_contents($file, $before."\n".$methodCode.substr($code, $position));

        return true;
    }
}

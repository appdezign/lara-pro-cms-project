<?php

namespace Tests\Feature\LaraV8;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Throwable;

/**
 * Guards the source of the v8 → v10 migration: the lara_v8 connection must reach
 * a v8 database and must not be able to write to it, so an import bug can never
 * damage the source snapshot.
 *
 * Skipped where no v8 source is configured (any v10 site that is not being migrated).
 */
class V8SourceConnectionTest extends TestCase
{
    private function v8(): Connection
    {
        try {
            $connection = DB::connection(config('v8-migration.connection'));
            $connection->getPdo();
        } catch (Throwable $e) {
            $this->markTestSkipped('No v8 source database configured: '.$e->getMessage());
        }

        return $connection;
    }

    public function test_the_source_is_a_different_database_than_the_site(): void
    {
        $this->assertNotSame(
            DB::connection()->getDatabaseName(),
            $this->v8()->getDatabaseName(),
        );
    }

    public function test_the_source_is_a_v8_database(): void
    {
        $v8 = $this->v8();

        $this->assertTrue($v8->getSchemaBuilder()->hasTable('lara_ent_entities'));
        $this->assertGreaterThan(0, $v8->table('lara_ent_entities')->count());
    }

    public function test_the_source_connection_cannot_write(): void
    {
        $this->expectException(QueryException::class);

        $this->v8()->table('lara_ent_entities')->where('id', 0)->update(['title' => 'write test']);
    }

    public function test_the_media_path_exists(): void
    {
        $this->v8();

        $path = (string) config('v8-migration.media_path');

        $this->assertNotSame('', $path, 'LARA_V8_MEDIA_PATH is not set.');
        $this->assertDirectoryExists($path);
    }
}

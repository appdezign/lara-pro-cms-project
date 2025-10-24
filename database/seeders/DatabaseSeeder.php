<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
	use WithoutModelEvents;

	/**
	 * Seed the application's database.
	 */
	public function run(): void
	{
        $this->call(LaraAuthModelHasPermissionsTableSeeder::class);
        $this->call(LaraAuthModelHasRolesTableSeeder::class);
        $this->call(LaraAuthPasswordResetTokensTableSeeder::class);
        $this->call(LaraAuthPermissionsTableSeeder::class);
        $this->call(LaraAuthRoleHasPermissionsTableSeeder::class);
        $this->call(LaraAuthRolesTableSeeder::class);
        $this->call(LaraAuthUsersTableSeeder::class);
        $this->call(LaraBlocksCtasTableSeeder::class);
        $this->call(LaraBlocksSlidersTableSeeder::class);
        $this->call(LaraBlocksWidgetsTableSeeder::class);
        $this->call(LaraContentBlogsTableSeeder::class);
        $this->call(LaraContentCitiesTableSeeder::class);
        $this->call(LaraContentDocsTableSeeder::class);
        $this->call(LaraContentEventsTableSeeder::class);
        $this->call(LaraContentGalleriesTableSeeder::class);
        $this->call(LaraContentLocationsTableSeeder::class);
        $this->call(LaraContentPagesTableSeeder::class);
        $this->call(LaraContentPortfoliosTableSeeder::class);
        $this->call(LaraContentProductsTableSeeder::class);
        $this->call(LaraContentServicesTableSeeder::class);
        $this->call(LaraContentTeamsTableSeeder::class);
        $this->call(LaraContentTestimonialsTableSeeder::class);
        $this->call(LaraContentVideosTableSeeder::class);
        $this->call(LaraFormClassicformsTableSeeder::class);
        $this->call(LaraFormContactformsTableSeeder::class);
        $this->call(LaraMenuMenuItemsTableSeeder::class);
        $this->call(LaraMenuMenusTableSeeder::class);
        $this->call(LaraObjectFilesTableSeeder::class);
        $this->call(LaraObjectImagesTableSeeder::class);
        $this->call(LaraObjectLayoutTableSeeder::class);
        $this->call(LaraObjectOpengraphTableSeeder::class);
        $this->call(LaraObjectPageablesTableSeeder::class);
        $this->call(LaraObjectRelatedTableSeeder::class);
        $this->call(LaraObjectSeoTableSeeder::class);
        $this->call(LaraObjectSyncTableSeeder::class);
        $this->call(LaraObjectTaggablesTableSeeder::class);
        $this->call(LaraObjectTagsTableSeeder::class);
        $this->call(LaraObjectTaxonomiesTableSeeder::class);
        $this->call(LaraObjectVideofilesTableSeeder::class);
        $this->call(LaraObjectVideosTableSeeder::class);
        $this->call(LaraResourceEntitiesTableSeeder::class);
        $this->call(LaraResourceEntityCustomFieldsTableSeeder::class);
        $this->call(LaraResourceEntityRelationsTableSeeder::class);
        $this->call(LaraResourceEntityViewsTableSeeder::class);
        $this->call(LaraSysBlacklistTableSeeder::class);
        $this->call(LaraSysLanguagesTableSeeder::class);
        $this->call(LaraSysSettingsTableSeeder::class);
        $this->call(LaraSysTranslationsTableSeeder::class);
    }
}
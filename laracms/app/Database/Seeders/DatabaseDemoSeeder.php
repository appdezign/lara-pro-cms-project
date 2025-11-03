<?php

namespace Lara\App\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseDemoSeeder extends Seeder
{
	use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

	    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

		#iseed_start


        $this->call(DemoLaraAuthModelHasPermissionsTableSeeder::class);
        $this->call(DemoLaraAuthModelHasRolesTableSeeder::class);
        $this->call(DemoLaraAuthPasswordResetTokensTableSeeder::class);
        $this->call(DemoLaraAuthPermissionsTableSeeder::class);
        $this->call(DemoLaraAuthRoleHasPermissionsTableSeeder::class);
        $this->call(DemoLaraAuthRolesTableSeeder::class);
        $this->call(DemoLaraAuthUsersTableSeeder::class);
        $this->call(DemoLaraBlocksCtasTableSeeder::class);
        $this->call(DemoLaraBlocksSlidersTableSeeder::class);
        $this->call(DemoLaraBlocksWidgetsTableSeeder::class);
        $this->call(DemoLaraContentBlogsTableSeeder::class);
        $this->call(DemoLaraContentCitiesTableSeeder::class);
        $this->call(DemoLaraContentDocsTableSeeder::class);
        $this->call(DemoLaraContentEventsTableSeeder::class);
        $this->call(DemoLaraContentGalleriesTableSeeder::class);
        $this->call(DemoLaraContentLocationsTableSeeder::class);
        $this->call(DemoLaraContentPagesTableSeeder::class);
        $this->call(DemoLaraContentPortfoliosTableSeeder::class);
        $this->call(DemoLaraContentProductsTableSeeder::class);
        $this->call(DemoLaraContentServicesTableSeeder::class);
        $this->call(DemoLaraContentTeamsTableSeeder::class);
        $this->call(DemoLaraContentTestimonialsTableSeeder::class);
        $this->call(DemoLaraContentVideosTableSeeder::class);
        $this->call(DemoLaraFormClassicformsTableSeeder::class);
        $this->call(DemoLaraFormContactformsTableSeeder::class);
        $this->call(DemoLaraMenuMenuItemsTableSeeder::class);
        $this->call(DemoLaraMenuMenusTableSeeder::class);
        $this->call(DemoLaraObjectFilesTableSeeder::class);
        $this->call(DemoLaraObjectImagesTableSeeder::class);
        $this->call(DemoLaraObjectLayoutTableSeeder::class);
        $this->call(DemoLaraObjectOpengraphTableSeeder::class);
        $this->call(DemoLaraObjectPageablesTableSeeder::class);
        $this->call(DemoLaraObjectRelatedTableSeeder::class);
        $this->call(DemoLaraObjectSeoTableSeeder::class);
        $this->call(DemoLaraObjectSyncTableSeeder::class);
        $this->call(DemoLaraObjectTaggablesTableSeeder::class);
        $this->call(DemoLaraObjectTagsTableSeeder::class);
        $this->call(DemoLaraObjectTaxonomiesTableSeeder::class);
        $this->call(DemoLaraObjectVideofilesTableSeeder::class);
        $this->call(DemoLaraObjectVideosTableSeeder::class);
        $this->call(DemoLaraResourceEntitiesTableSeeder::class);
        $this->call(DemoLaraResourceEntityCustomFieldsTableSeeder::class);
        $this->call(DemoLaraResourceEntityRelationsTableSeeder::class);
        $this->call(DemoLaraResourceEntityViewsTableSeeder::class);
        $this->call(DemoLaraSysBlacklistTableSeeder::class);
        $this->call(DemoLaraSysLanguagesTableSeeder::class);
        $this->call(DemoLaraSysSettingsTableSeeder::class);
        $this->call(DemoLaraSysTranslationsTableSeeder::class);
		#iseed_end

	    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    }
}

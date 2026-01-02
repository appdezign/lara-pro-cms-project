<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Lara\App\Database\Seeders\DemoLaraAuthModelHasPermissionsTableSeeder;
use Lara\App\Database\Seeders\DemoLaraAuthModelHasRolesTableSeeder;
use Lara\App\Database\Seeders\DemoLaraAuthPasswordResetTokensTableSeeder;
use Lara\App\Database\Seeders\DemoLaraAuthPermissionsTableSeeder;
use Lara\App\Database\Seeders\DemoLaraAuthRoleHasPermissionsTableSeeder;
use Lara\App\Database\Seeders\DemoLaraAuthRolesTableSeeder;
use Lara\App\Database\Seeders\DemoLaraAuthUsersTableSeeder;
use Lara\App\Database\Seeders\DemoLaraBlocksCtasTableSeeder;
use Lara\App\Database\Seeders\DemoLaraBlocksSlidersTableSeeder;
use Lara\App\Database\Seeders\DemoLaraBlocksWidgetsTableSeeder;
use Lara\App\Database\Seeders\DemoLaraContentBlogsTableSeeder;
use Lara\App\Database\Seeders\DemoLaraContentCitiesTableSeeder;
use Lara\App\Database\Seeders\DemoLaraContentDocsTableSeeder;
use Lara\App\Database\Seeders\DemoLaraContentEventsTableSeeder;
use Lara\App\Database\Seeders\DemoLaraContentGalleriesTableSeeder;
use Lara\App\Database\Seeders\DemoLaraContentLocationsTableSeeder;
use Lara\App\Database\Seeders\DemoLaraContentPagesTableSeeder;
use Lara\App\Database\Seeders\DemoLaraContentPortfoliosTableSeeder;
use Lara\App\Database\Seeders\DemoLaraContentProductsTableSeeder;
use Lara\App\Database\Seeders\DemoLaraContentServicesTableSeeder;
use Lara\App\Database\Seeders\DemoLaraContentTeamsTableSeeder;
use Lara\App\Database\Seeders\DemoLaraContentTestimonialsTableSeeder;
use Lara\App\Database\Seeders\DemoLaraContentVideosTableSeeder;
use Lara\App\Database\Seeders\DemoLaraFormClassicformsTableSeeder;
use Lara\App\Database\Seeders\DemoLaraFormContactformsTableSeeder;
use Lara\App\Database\Seeders\DemoLaraMenuMenuItemsTableSeeder;
use Lara\App\Database\Seeders\DemoLaraMenuMenusTableSeeder;
use Lara\App\Database\Seeders\DemoLaraObjectFilesTableSeeder;
use Lara\App\Database\Seeders\DemoLaraObjectImagesTableSeeder;
use Lara\App\Database\Seeders\DemoLaraObjectLayoutTableSeeder;
use Lara\App\Database\Seeders\DemoLaraObjectOpengraphTableSeeder;
use Lara\App\Database\Seeders\DemoLaraObjectPageablesTableSeeder;
use Lara\App\Database\Seeders\DemoLaraObjectRelatedTableSeeder;
use Lara\App\Database\Seeders\DemoLaraObjectSeoTableSeeder;
use Lara\App\Database\Seeders\DemoLaraObjectSyncTableSeeder;
use Lara\App\Database\Seeders\DemoLaraObjectTaggablesTableSeeder;
use Lara\App\Database\Seeders\DemoLaraObjectTagsTableSeeder;
use Lara\App\Database\Seeders\DemoLaraObjectTaxonomiesTableSeeder;
use Lara\App\Database\Seeders\DemoLaraObjectVideofilesTableSeeder;
use Lara\App\Database\Seeders\DemoLaraObjectVideosTableSeeder;
use Lara\App\Database\Seeders\DemoLaraResourceEntitiesTableSeeder;
use Lara\App\Database\Seeders\DemoLaraResourceEntityCustomFieldsTableSeeder;
use Lara\App\Database\Seeders\DemoLaraResourceEntityRelationsTableSeeder;
use Lara\App\Database\Seeders\DemoLaraResourceEntityViewsTableSeeder;
use Lara\App\Database\Seeders\DemoLaraSysBlacklistTableSeeder;
use Lara\App\Database\Seeders\DemoLaraSysLanguagesTableSeeder;
use Lara\App\Database\Seeders\DemoLaraSysSettingsTableSeeder;
use Lara\App\Database\Seeders\DemoLaraSysTranslationsTableSeeder;
use Lara\Common\Database\Seeders\LaraLaraAuthModelHasPermissionsTableSeeder;
use Lara\Common\Database\Seeders\LaraLaraAuthModelHasRolesTableSeeder;
use Lara\Common\Database\Seeders\LaraLaraAuthPasswordResetTokensTableSeeder;
use Lara\Common\Database\Seeders\LaraLaraAuthPermissionsTableSeeder;
use Lara\Common\Database\Seeders\LaraLaraAuthRoleHasPermissionsTableSeeder;
use Lara\Common\Database\Seeders\LaraLaraAuthRolesTableSeeder;
use Lara\Common\Database\Seeders\LaraLaraAuthUsersTableSeeder;
use Lara\Common\Database\Seeders\LaraLaraContentPagesTableSeeder;
use Lara\Common\Database\Seeders\LaraLaraMenuMenuItemsTableSeeder;
use Lara\Common\Database\Seeders\LaraLaraMenuMenusTableSeeder;
use Lara\Common\Database\Seeders\LaraLaraObjectTaxonomiesTableSeeder;
use Lara\Common\Database\Seeders\LaraLaraResourceEntitiesTableSeeder;
use Lara\Common\Database\Seeders\LaraLaraResourceEntityCustomFieldsTableSeeder;
use Lara\Common\Database\Seeders\LaraLaraResourceEntityViewsTableSeeder;
use Lara\Common\Database\Seeders\LaraLaraSysLanguagesTableSeeder;
use Lara\Common\Database\Seeders\LaraLaraSysSettingsTableSeeder;
use Lara\Common\Database\Seeders\LaraLaraSysTranslationsTableSeeder;

class DatabaseSeeder extends Seeder
{
	use WithoutModelEvents;

	/**
	 * Seed the application's database.
	 */
	public function run(): void
	{
		DB::statement('SET FOREIGN_KEY_CHECKS=0;');

		#iseed_start
		$this->call(LaraLaraAuthModelHasPermissionsTableSeeder::class);
        $this->call(LaraLaraAuthModelHasRolesTableSeeder::class);
        $this->call(LaraLaraAuthPasswordResetTokensTableSeeder::class);
        $this->call(LaraLaraAuthPermissionsTableSeeder::class);
        $this->call(LaraLaraAuthRoleHasPermissionsTableSeeder::class);
        $this->call(LaraLaraAuthRolesTableSeeder::class);
        $this->call(LaraLaraAuthUsersTableSeeder::class);
        $this->call(LaraLaraContentPagesTableSeeder::class);
        $this->call(LaraLaraMenuMenuItemsTableSeeder::class);
        $this->call(LaraLaraMenuMenusTableSeeder::class);
        $this->call(LaraLaraObjectTaxonomiesTableSeeder::class);
        $this->call(LaraLaraResourceEntitiesTableSeeder::class);
        $this->call(LaraLaraResourceEntityCustomFieldsTableSeeder::class);
        $this->call(LaraLaraResourceEntityViewsTableSeeder::class);
        $this->call(LaraLaraSysLanguagesTableSeeder::class);
        $this->call(LaraLaraSysSettingsTableSeeder::class);
        $this->call(LaraLaraSysTranslationsTableSeeder::class);
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
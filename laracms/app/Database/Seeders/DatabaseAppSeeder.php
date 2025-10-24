<?php

namespace Lara\App\Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseAppSeeder extends Seeder
{
	use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

	    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

		// Content
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

		// Forms
        $this->call(LaraFormClassicformsTableSeeder::class);
        $this->call(LaraFormContactformsTableSeeder::class);

	    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    }
}

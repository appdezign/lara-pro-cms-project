<?php

use Illuminate\Support\Facades\Schedule;

use BezhanSalleh\GoogleAnalytics\Support\GAResponse;
use Facades\BezhanSalleh\GoogleAnalytics\Support\GADataLookups;

use Lara\App\Sitemap\GoogleSitemapGenerator;
use Lara\Admin\Media\SyncMedia;

$minutes = config('lara.scheduler_id');

Schedule::call(function () {
	(new GoogleSitemapGenerator)(['nl']);
})->name('google-sitemap')
	->dailyAt($minutes);

Schedule::call(function () {
	(new SyncMedia)();
})->name('sync-media')
	->hourlyAt($minutes);

Schedule::call(function () {
	GAResponse::sessionsByCountry('LSD');
	GAResponse::sessionsByDevice('LSD');
	GAResponse::common(GADataLookups::pageViews(), 'LSD');
	GAResponse::common(GADataLookups::visitors(), 'LSD');
	GAResponse::common(GADataLookups::sessions(), 'LSD');
	GAResponse::common(GADataLookups::sessionsDuration(), 'LSD');
	GAResponse::mostVisitedPages('TM');
	GAResponse::topReferrers('TM');
})->name('google-analytics')
	->cron($minutes . ' */4 * * *');



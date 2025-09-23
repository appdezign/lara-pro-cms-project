<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;

use BezhanSalleh\GoogleAnalytics\Support\GAResponse;
use Facades\BezhanSalleh\GoogleAnalytics\Support\GADataLookups;

Schedule::call(function () {
	GAResponse::sessionsByCountry('LSD');
	GAResponse::sessionsByDevice('LSD');
	GAResponse::common(GADataLookups::pageViews(), 'LSD');
	GAResponse::common(GADataLookups::visitors(), 'LSD');
	GAResponse::common(GADataLookups::sessions(), 'LSD');
	GAResponse::common(GADataLookups::sessionsDuration(), 'LSD');
	GAResponse::mostVisitedPages('TM');
	GAResponse::topReferrers('TM');
})->everyFourHours();

Schedule::call(function () {
	File::cleanDirectory(storage_path('imgcache'));
})->Hourly();

<?php

namespace Lara\App\Http\Traits;

use Lara\Common\Models\Setting;
use Lara\Common\Models\Translation;

trait EveUpdateTrait
{

	/**
	 * @return array|false|string
	 */
	private function checkForEveUpdates()
	{

		$builds = [
			'8.0.1',
			'8.5.0',
		];

		// current Eve version
		$databaseVersion = $this->getEveVersionFromSettings();

		$updates = array();

		if ($databaseVersion) {

			foreach ($builds as $build) {
				if (version_compare($build, $databaseVersion, '>')) {
					$updates[] = $build;
				}
			}

			if (!empty($updates)) {

				/* ~~~~~~~~~~~~ UPDATES ~~~~~~~~~~~~ */

				if (in_array('8.0.1', $updates)) {

					$this->myFirstUpdate();

					$this->setEveSetting('system', 'lara_eve_version', '8.0.1');

				}

				if (in_array('8.5.0', $updates)) {

					// version bump
					$this->setEveSetting('system', 'lara_eve_version', '8.5.0');

				}

				// Post-update actions
				$this->clearCache();

				return end($builds);

			} else {
				return $updates;
			}

		} else {
			return $updates;
		}

	}

	private function myFirstUpdate()
	{

		// update your app here

		return true;

	}

	private function getEveVersionFromSettings()
	{

		// current version
		$currentBuild = Setting::where('cgroup', 'system')->where('key', 'lara_eve_version')->first();

		if ($currentBuild) {
			return $currentBuild->value;
		} else {
			return null;
		}

	}

	private function setEveSetting($cgroup, $key, $value)
	{

		$modelClass = \Lara\Common\Models\Setting::class;

		// get record
		$object = $modelClass::where('cgroup', $cgroup)
			->where('key', $key)
			->first();

		if ($object) {

			$object->value = $value;
			$object->save();

		} else {

			$modelClass::create([
				'title'  => $key,
				'cgroup' => $cgroup,
				'key'    => $key,
				'value'  => $value,
			]);

		}

	}

	private function checkTranslationItem($language, $module, $cgroup, $tag, $key, $value, $force = false)
	{

		$trans = Translation::where('language', $language)
			->where('module', $module)
			->where('cgroup', $cgroup)
			->where('tag', $tag)
			->where('key', $key)
			->first();

		$change = false;

		if ($trans) {

			// check value
			if ($trans->value != $value) {
				if (substr($trans->value, 0, 1) == '_' || $force) {
					$trans->value = $value;
					$trans->save();
					$change = true;
				}
			}

		} else {

			Translation::create([
				'language' => $language,
				'module'   => $module,
				'cgroup'   => $cgroup,
				'tag'      => $tag,
				'key'      => $key,
				'value'    => $value,
			]);

			$change = true;

		}

		return $change;

	}

}

<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

try {
	require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
	include_file('core', 'authentification', 'php');

	if (!isConnect('admin')) {
		throw new Exception(__('401 - Accès non autorisé', __FILE__));
	}

	ajax::init();

	if (init('action') == 'sendDevices') {
		ash::sendDevices();
		ajax::success();
	}

	if (init('action') == 'saveDevices') {
		$devices = json_decode(init('devices'), true);
		foreach ($devices as $device_json) {
			$device = new ash_devices();
			utils::a2o($device, $device_json);
			$device->save();
			$enableList[$device->getId()] = true;
		}
		$dbList = ash_devices::all();
		foreach ($dbList as $dbObject) {
			if (!isset($enableList[$dbObject->getId()])) {
				$dbObject->remove();
			}
		}
		ajax::success();
	}

	if (init('action') == 'allDevices') {
		ajax::success(utils::o2a(ash_devices::all()));
	}

	if (init('action') == 'sendConfig') {
		ash::sendJeedomConfig();
		ajax::success();
	}

	throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
	/*     * *********Catch exeption*************** */
} catch (Exception $e) {
	ajax::error(displayExeption($e), $e->getCode());
}

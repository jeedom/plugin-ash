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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function ash_install() {
	$sql = file_get_contents(dirname(__FILE__) . '/install.sql');
	DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);
	if (config::byKey('ashs::masterkey', 'ash') == '') {
		config::save('ashs::masterkey', config::genKey(30), 'ash');
	}
	if (config::byKey('ashs::clientId', 'ash') == '') {
		config::save('ashs::clientId', config::genKey(10), 'ash');
	}
	if (config::byKey('ashs::clientSecret', 'ash') == '') {
		config::save('ashs::clientSecret', config::genKey(30), 'ash');
	}
	if (config::byKey('ashs::token', 'ash') == '') {
		config::save('ashs::token', config::genKey(30), 'ash');
	}
	jeedom::getApiKey('ash');
	try {
		ash::sendJeedomConfig();
	} catch (\Exception $e) {
		
	}
	
}

function ash_update() {
	foreach(ash_devices::all() as $device){
		if($device->getType() == 'TEMPERATURE_SENSOR'){
			$device->setType('SENSORS');
			$device->save();
		}
	}
}

function ash_remove() {
	
}

?>

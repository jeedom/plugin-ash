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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class ash_temperature {

	/*     * *************************Attributs****************************** */

	private static $_SENSOR = array('THERMOSTAT_TEMPERATURE', 'WEATHER_TEMPERATURE', 'TEMPERATURE');

	/*     * ***********************Methode static*************************** */

	public static function buildDevice($_device) {
		$eqLogic = $_device->getLink();
		if (!is_object($eqLogic)) {
			return array();
		}
		if ($eqLogic->getIsEnable() == 0) {
			return array();
		}
		$return = array();
		$return['endpointId'] = $eqLogic->getId();
		$return['friendlyName'] = str_replace(array('#', '][', '[', ']'), array('', ' ', '', ''), $eqLogic->getHumanName());
		$return['description'] = $eqLogic->getHumanName();
		$return['manufacturerName'] = 'Jeedom';
		$return['cookie'] = array('key1' => '');
		$return['displayCategories'] = array($_device->getType());
		$return['capabilities'] = array();

		foreach ($eqLogic->getCmd() as $cmd) {
			if (in_array($cmd->getGeneric_type(), self::$_SENSOR)) {
				$return['capabilities']['Alexa.TemperatureSensor'] = array(
					'type' => 'AlexaInterface',
					'interface' => 'Alexa.TemperatureSensor',
					'version' => 3,
					'properties' => array(
						'supported' => array(
							array('name' => 'temperature'),
						),
					),
					'proactivelyReported' => true,
					'retrievable' => true,
				);
				$return['cookie']['cmd_get_temperature'] = $cmd->getId();
			}
		}
		if (count($return['capabilities']) == 0) {
			return array();
		}
		$return['capabilities']['AlexaInterface'] = array(
			"type" => "AlexaInterface",
			"interface" => "Alexa",
			"version" => "3",
		);
		return $return;
	}

	public static function exec($_device, $_directive) {
		$return = array('status' => 'ERROR');
		$eqLogic = $_device->getLink();
		if (!is_object($eqLogic)) {
			throw new Exception('NO_SUCH_ENDPOINT');
		}
		if ($eqLogic->getIsEnable() == 0) {
			throw new Exception('ENDPOINT_UNREACHABLE');
		}
		return self::getState($_device, $_directive);
	}

	public static function getState($_device, $_directive) {
		$return = array();
		$cmd = null;
		if (isset($_directive['endpoint']['cookie']['cmd_get_temperature'])) {
			$cmd = cmd::byId($_directive['endpoint']['cookie']['cmd_get_temperature']);
		}
		if (!is_object($cmd)) {
			return $return;
		}
		$value = $cmd->execCmd();

		$return[] = array(
			'namespace' => 'Alexa.TemperatureSensor',
			'name' => 'temperature',
			'value' => array('value' => $value, 'scale' => 'CELSIUS'),
			'timeOfSample' => date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
			'uncertaintyInMilliseconds' => 0,
		);

		return array('properties' => $return);
	}

	/*     * *********************Méthodes d'instance************************* */

	/*     * **********************Getteur Setteur*************************** */

}
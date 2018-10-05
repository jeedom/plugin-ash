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

class ash_thermostat {

	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	public static function buildDevice($_device) {
		$eqLogic = $_device->getLink();
		if (!is_object($eqLogic)) {
			return 'deviceNotFound';
		}
		if ($eqLogic->getIsEnable() == 0) {
			return 'deviceNotFound';
		}
		$return = array();
		$return['endpointId'] = $eqLogic->getId();
		$return['friendlyName'] = $_device->getPseudo();
		$return['description'] = $eqLogic->getHumanName();
		$return['manufacturerName'] = 'Jeedom';
		$return['cookie'] = array('key1' => '');
		$return['displayCategories'] = array($_device->getType());
		$return['capabilities'] = array();

		foreach ($eqLogic->getCmd() as $cmd) {
			if (in_array($cmd->getGeneric_type(), array('THERMOSTAT_SETPOINT'))) {
				$return['cookie']['cmd_get_thermostat'] = $cmd->getId();
			}
			if (in_array($cmd->getGeneric_type(), array('THERMOSTAT_SET_SETPOINT'))) {
				if (!isset($return['capabilities']['Alexa.ThermostatController'])) {
					$return['capabilities']['Alexa.ThermostatController'] = array(
						'type' => 'AlexaInterface',
						'interface' => 'Alexa.ThermostatController',
						'version' => 3,
						'properties' => array(
							'supported' => array(),
						),
						'proactivelyReported' => true,
						'retrievable' => true,
					);
				}
				$return['capabilities']['Alexa.ThermostatController']['properties']['supported'][] = array('name' => 'targetSetpoint');
				$return['cookie']['cmd_set_thermostat'] = $cmd->getId();
			}
			if (in_array($cmd->getGeneric_type(), array('THERMOSTAT_MODE'))) {
				if (!isset($return['capabilities']['Alexa.ThermostatController'])) {
					$return['capabilities']['Alexa.ThermostatController'] = array(
						'type' => 'AlexaInterface',
						'interface' => 'Alexa.ThermostatController',
						'version' => 3,
						'properties' => array(
							'supported' => array(),
						),
						'proactivelyReported' => true,
						'retrievable' => true,
					);
				}
				$return['capabilities']['Alexa.ThermostatController']['properties']['supported'][] = array('name' => 'thermostatMode');
				$return['cookie']['cmd_get_mode'] = $cmd->getId();
			}
			if (in_array($cmd->getGeneric_type(), array('THERMOSTAT_TEMPERATURE'))) {
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
		$return['attributes'] = array('availableThermostatModes' => 'on,off,heat,cool', 'thermostatTemperatureUnit' => 'C');
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
		switch ($_directive['header']['name']) {
			case 'SetTargetTemperature':
				if (isset($_directive['endpoint']['cookie']['cmd_set_thermostat'])) {
					$cmd = cmd::byId($_directive['endpoint']['cookie']['cmd_set_thermostat']);
				}
				if (!is_object($cmd)) {
					break;
				}
				$cmd->execCmd(array('slider' => $_directive['payload']['targetSetpoint']['value']));
				break;
		}
		return self::getState($_device, $_directive);
	}

	public static function getState($_device, $_directive) {
		$return = array();
		$cmd = null;
		if (isset($_directive['endpoint']['cookie']['cmd_get_temperature'])) {
			$cmd = cmd::byId($_directive['endpoint']['cookie']['cmd_get_temperature']);
			if (is_object($cmd)) {
				$value = $cmd->execCmd();
				/*$return[] = array(
					'namespace' => 'Alexa.TemperatureSensor',
					'name' => 'temperature',
					'value' => array('value' => $value, 'scale' => 'CELSIUS'),
					'timeOfSample' => date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
					'uncertaintyInMilliseconds' => 0,
				);*/
			}
		}
		if (isset($_directive['endpoint']['cookie']['cmd_get_thermostat'])) {
			$cmd = cmd::byId($_directive['endpoint']['cookie']['cmd_get_thermostat']);
			if (is_object($cmd)) {
				$value = $cmd->execCmd();
				$return[] = array(
					'namespace' => 'Alexa.ThermostatController',
					'name' => 'targetSetpoint',
					'value' => array('value' => $value, 'scale' => 'CELSIUS'),
					'timeOfSample' => date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
					'uncertaintyInMilliseconds' => 0,
				);
			}
		}
		if (isset($_directive['endpoint']['cookie']['cmd_get_mode'])) {
			$cmd = cmd::byId($_directive['endpoint']['cookie']['cmd_get_mode']);
			if (is_object($cmd)) {
				$value = $cmd->execCmd();
				$return[] = array(
					'namespace' => 'Alexa.ThermostatController',
					'name' => 'thermostatMode',
					'value' => array('value' => $value),
					'timeOfSample' => date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
					'uncertaintyInMilliseconds' => 0,
				);
			}
		}
		return array('properties' => $return);
	}

	/*     * *********************Méthodes d'instance************************* */

	/*     * **********************Getteur Setteur*************************** */

}

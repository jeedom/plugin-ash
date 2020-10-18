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
		$return['cookie'] = array('none' => 'empty');
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
						'version' => '3',
						'properties' => array(
							'supported' => array(),
							'proactivelyReported' => false,
							'retrievable' => true,
						),
						'configuration' => array(
							'supportsScheduling'=> false,
							'supportedModes'=> array('HEAT','COOL','AUTO','OFF')
						)
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
						'version' => '3',
						'properties' => array(
							'supported' => array(),
							'proactivelyReported' => false,
							'retrievable' => true,
						),
						'configuration' => array(
							'supportsScheduling'=> false,
							'supportedModes'=> array('HEAT','COOL','AUTO','OFF')
						)
					);
				}
				$return['capabilities']['Alexa.ThermostatController']['properties']['supported'][] = array('name' => 'thermostatMode');
				$return['cookie']['cmd_get_mode'] = $cmd->getId();
			}
			if (in_array($cmd->getGeneric_type(), array('THERMOSTAT_TEMPERATURE'))) {
				$return['capabilities']['Alexa.TemperatureSensor'] = array(
					'type' => 'AlexaInterface',
					'interface' => 'Alexa.TemperatureSensor',
					'version' => '3',
					'properties' => array(
						'supported' => array(
							array('name' => 'temperature'),
						),
						'proactivelyReported' => false,
						'retrievable' => true,
					),
				);
				$return['cookie']['cmd_get_temperature'] = $cmd->getId();
			}
		}
		if (count($return['capabilities']) == 0) {
			return array('missingGenericType' => array(
				__('Thermostat',__FILE__) => array('THERMOSTAT_SET_SETPOINT'),
				__('Consigne',__FILE__) => array('THERMOSTAT_SETPOINT'),
				__('Etat themostat ',__FILE__) => array('THERMOSTAT_TEMPERATURE'),
				__('Mode',__FILE__) => array('THERMOSTAT_MODE')
			));
		}
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
			case 'targetSetpointDelta':
			if (isset($_directive['endpoint']['cookie']['cmd_set_thermostat'])) {
				$cmd_set = cmd::byId($_directive['endpoint']['cookie']['cmd_set_thermostat']);
			}
			if (!is_object($cmd_set)) {
				break;
			}
			if (isset($_directive['endpoint']['cookie']['cmd_get_thermostat'])) {
				$cmd_get = cmd::byId($_directive['endpoint']['cookie']['cmd_get_thermostat']);
			}
			if (!is_object($cmd_get)) {
				break;
			}
			$cmd->execCmd(array('slider' => $cmd_get->execCmd() + $_directive['payload']['targetSetpoint']['value']));
			break;
			case 'AdjustTargetTemperature':
			if (isset($_directive['endpoint']['cookie']['cmd_set_thermostat'])) {
				$cmd_set = cmd::byId($_directive['endpoint']['cookie']['cmd_set_thermostat']);
			}
			if (!is_object($cmd_set)) {
				break;
			}
			if (isset($_directive['endpoint']['cookie']['cmd_get_thermostat'])) {
				$cmd_get = cmd::byId($_directive['endpoint']['cookie']['cmd_get_thermostat']);
			}
			if (!is_object($cmd_get)) {
				break;
			}
			$cmd_set->execCmd(array('slider' => $cmd_get->execCmd() + $_directive['payload']['targetSetpointDelta']['value']));
			break;
			case 'SetThermostatMode':
			if (isset($_directive['payload']['thermostatMode']['value'])) {
				$requested_mode = $_directive['payload']['thermostatMode']['value'];
			}
			if ($requested_mode == '') {
				break;
			}
			if (isset($_directive['endpoint']['cookie']['cmd_get_mode'])) {
				$cmd_get_mode = cmd::byId($_directive['endpoint']['cookie']['cmd_get_mode']);
			}
			if (!is_object($cmd_get_mode)) {
				break;
			}
			$eqlogic = $cmd_get_mode->getEqLogic();
			if (!is_object($eqlogic)) {
				break;
			}
			$cmd_mode_array = cmd::byGenericType('THERMOSTAT_SET_MODE',$eqlogic->getId());
			if (count($cmd_mode_array) == 0) {
				break;
			}
			foreach ($cmd_mode_array as $cmd_mode){
				
				if(strtoupper($cmd_mode->getName()) == $requested_mode){
					$cmd_mode->execute();
					break;
				}
			}
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
				$return[] = array(
					'namespace' => 'Alexa.TemperatureSensor',
					'name' => 'temperature',
					'value' => array('value' => $value, 'scale' => 'CELSIUS'),
					'timeOfSample' => date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
					'uncertaintyInMilliseconds' => 0,
				);
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
				$value = strtolower($cmd->execCmd());
				$cValue = 'AUTO';
				if(strpos($value,'chauffage') !== false){
					$cValue = 'HEAT';
				}else if(strpos($value,'clim') !== false){
					$cValue = 'COOL';
				}else if(strpos($value,'stop') !== false || strpos($value,'off')  !== false){
					$cValue = 'OFF';
				}else if(strpos($value,'eco') !== false){
					$cValue = 'ECO';
				}
				$return[] = array(
					'namespace' => 'Alexa.ThermostatController',
					'name' => 'thermostatMode',
					'value' => array('value' => $cValue),
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

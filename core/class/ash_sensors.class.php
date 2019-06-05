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
class ash_sensors {
	/*     * *************************Attributs****************************** */
	private static $_CONTACT = array('LOCK_STATE', 'BARRIER_STATE', 'GARAGE_STATE', 'OPENING','OPENING_WINDOW');
	private static $_MOTION = array('PRESENCE');
	private static $_TEMPERATURE = array('TEMPERATURE');
	
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
		$return['friendlyName'] = $_device->getPseudo();
		$return['description'] = $eqLogic->getHumanName();
		$return['manufacturerName'] = 'Jeedom';
		$return['cookie'] = array('key1' => '');
		$return['displayCategories'] = array($_device->getType());
		$return['capabilities'] = array();
		foreach ($eqLogic->getCmd() as $cmd) {
			if (in_array($cmd->getGeneric_type(), self::$_CONTACT)) {
				$return['capabilities']['Alexa.ContactSensor'] = array(
					'type' => 'AlexaInterface',
					'interface' => 'Alexa.ContactSensor',
					'version' => 3,
					'properties' => array(
						'supported' => array(
							array('name' => 'detectionState'),
						),
						'proactivelyReported' => false,
						'retrievable' => true,
					),
				);
				$return['displayCategories'][] = 'CONTACT_SENSOR';
				$return['cookie']['cmd_contact_state'] = $cmd->getId();
			}
			if (in_array($cmd->getGeneric_type(), self::$_MOTION)) {
				$return['capabilities']['Alexa.MotionSensor'] = array(
					'type' => 'AlexaInterface',
					'interface' => 'Alexa.MotionSensor',
					'version' => 3,
					'properties' => array(
						'supported' => array(
							array('name' => 'detectionState'),
						),
						'proactivelyReported' => false,
						'retrievable' => true,
					),
				);
				$return['displayCategories'][] = 'MOTION_SENSOR';
				$return['cookie']['cmd_motion_state'] = $cmd->getId();
			}
			if (in_array($cmd->getGeneric_type(), self::$_TEMPERATURE)) {
				$return['capabilities']['Alexa.TemperatureSensor'] = array(
					'type' => 'AlexaInterface',
					'interface' => 'Alexa.TemperatureSensor',
					'version' => 3,
					'properties' => array(
						'supported' => array(
							array('name' => 'temperature'),
						),
						'proactivelyReported' => false,
						'retrievable' => true,
					),
				);
				$return['displayCategories'][] = 'TEMPERATURE_SENSOR';
				$return['cookie']['cmd_temperature_state'] = $cmd->getId();
			}
		}
		if (count($return['traits']) == 0) {
			return array('missingGenericType' => array(
				__('Contacteur',__FILE__) => self::$_CONTACT,
				__('Mouvement',__FILE__) => self::$_MOTION,
				__('Température',__FILE__) => self::$_TEMPERATURE
			));
		}
		$return['capabilities']['AlexaInterface'] = array(
			"type" => "AlexaInterface",
			"interface" => "Alexa",
			"version" => "3",
		);
		return $return;
	}
	public static function exec($_device, $_directive) {
		return self::getState($_device, $_directive);
	}
	
	public static function getState($_device, $_directive) {
		$return = array();
		$cmd = null;
		if (isset($_directive['endpoint']['cookie']['cmd_contact_state'])) {
			$cmd = cmd::byId($_directive['endpoint']['cookie']['cmd_contact_state']);
			if (is_object($cmd)) {
				$value = $cmd->execCmd();
				if ($cmd->getDisplay('invertBinary') == 1) {
					$value = ($value) ? false : true;
				}
				$value = ($value == 0) ? 'DETECTED' : 'NOT_DETECTED';
				$return[] = array(
					'namespace' => 'Alexa.ContactSensor',
					'name' => 'detectionState',
					'value' => $value,
					'timeOfSample' => date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
					'uncertaintyInMilliseconds' => 0,
				);
			}
		}
		
		if (isset($_directive['endpoint']['cookie']['cmd_motion_state'])) {
			$cmd = cmd::byId($_directive['endpoint']['cookie']['cmd_motion_state']);
			if (is_object($cmd)) {
				$value = $cmd->execCmd();
				if ($cmd->getDisplay('invertBinary') == 1) {
					$value = ($value) ? false : true;
				}
				$value = ($value == 0) ? 'DETECTED' : 'NOT_DETECTED';
				$return[] = array(
					'namespace' => 'Alexa.MotionSensor',
					'name' => 'detectionState',
					'value' => $value,
					'timeOfSample' => date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
					'uncertaintyInMilliseconds' => 0,
				);
			}
		}
		
		if (isset($_directive['endpoint']['cookie']['cmd_temperature_state'])) {
			$cmd = cmd::byId($_directive['endpoint']['cookie']['cmd_temperature_state']);
			if (is_object($cmd)) {
				$return[] = array(
					'namespace' => 'Alexa.TemperatureSensor',
					'name' => 'temperature',
					'value' => array(
						'value' => $cmd->execCmd(),
						'scale' => 'CELSIUS',
					),
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

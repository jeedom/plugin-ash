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

class ash_outlet {

	/*     * *************************Attributs****************************** */

	private static $_ON = array('FLAP_BSO_UP', 'FLAP_SLIDER', 'FLAP_UP', 'ENERGY_ON', 'FLAP_SLIDER', 'HEATING_ON', 'LOCK_OPEN', 'SIREN_ON', 'GB_OPEN', 'GB_TOGGLE');
	private static $_OFF = array('FLAP_BSO_DOWN', 'FLAP_SLIDER', 'FLAP_DOWN', 'ENERGY_OFF', 'FLAP_SLIDER', 'HEATING_OFF', 'LOCK_CLOSE', 'SIREN_OFF', 'GB_CLOSE', 'GB_TOGGLE');
	private static $_STATE = array('ENERGY_STATE', 'FLAP_STATE', 'FLAP_BSO_STATE', 'HEATING_STATE', 'LOCK_STATE', 'SIREN_STATE', 'GARAGE_STATE', 'BARRIER_STATE', 'OPENING', 'OPENING_WINDOW');

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
			if (in_array($cmd->getGeneric_type(), self::$_ON)) {
				$return['capabilities']['Alexa.PowerController'] = array(
					'type' => 'AlexaInterface',
					'interface' => 'Alexa.PowerController',
					'version' => 3,
					'properties' => array(
						'supported' => array(
							array('name' => 'powerState'),
						),
					),
					'proactivelyReported' => false,
					'retrievable' => true,
				);
				$return['cookie']['cmd_set_on'] = $cmd->getId();
			}

			if (in_array($cmd->getGeneric_type(), self::$_OFF)) {
				$return['capabilities']['Alexa.PowerController'] = array(
					'type' => 'AlexaInterface',
					'interface' => 'Alexa.PowerController',
					'version' => 3,
					'properties' => array(
						'supported' => array(
							array('name' => 'powerState'),
						),
					),
					'proactivelyReported' => false,
					'retrievable' => true,
				);
				$return['cookie']['cmd_set_off'] = $cmd->getId();
			}
			if (in_array($cmd->getGeneric_type(), self::$_STATE)) {
				$return['cookie']['cmd_get_state'] = $cmd->getId();
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
		switch ($_directive['header']['name']) {
			case 'TurnOn':
				if (isset($_directive['endpoint']['cookie']['cmd_set_on'])) {
					$cmd = cmd::byId($_directive['endpoint']['cookie']['cmd_set_on']);
				} else if (isset($_directive['endpoint']['cookie']['cmd_set_slider'])) {
					$cmd = cmd::byId($_directive['endpoint']['cookie']['cmd_set_slider']);
				}
				if (!is_object($cmd)) {
					throw new Exception('ENDPOINT_UNREACHABLE');
				}
				if ($cmd->getSubtype() == 'other') {
					$cmd->execCmd();
				} else if ($cmd->getSubtype() == 'slider') {
					$value = (in_array($cmd->getGeneric_type(), array('FLAP_SLIDER'))) ? 0 : 100;
					$cmd->execCmd(array('slider' => $value));
				}
				break;
			case 'TurnOff':
				if (isset($_directive['endpoint']['cookie']['cmd_set_off'])) {
					$cmd = cmd::byId($_directive['endpoint']['cookie']['cmd_set_off']);
				} else if (isset($_directive['endpoint']['cookie']['cmd_set_slider'])) {
					$cmd = cmd::byId($_directive['endpoint']['cookie']['cmd_set_slider']);
				}
				if (!is_object($cmd)) {
					throw new Exception('ENDPOINT_UNREACHABLE');
				}
				if ($cmd->getSubtype() == 'other') {
					$cmd->execCmd();
				} else if ($cmd->getSubtype() == 'slider') {
					$value = (in_array($cmd->getGeneric_type(), array('FLAP_SLIDER'))) ? 100 : 0;
					$cmd->execCmd(array('slider' => $value));
				}
				break;
		}
		return self::getState($_device, $_directive);
	}

	public static function getState($_device, $_directive) {
		$return = array();
		$cmd = null;
		if (isset($_directive['endpoint']['cookie']['cmd_get_state'])) {
			$cmd = cmd::byId($_directive['endpoint']['cookie']['cmd_get_state']);
		}
		if (!is_object($cmd)) {
			return $return;
		}
		$value = $cmd->execCmd();
		if ($cmd->getSubtype() == 'numeric') {
			$return[] = array(
				'namespace' => 'Alexa.BrightnessController',
				'name' => 'brightness',
				'value' => $value,
				'timeOfSample' => date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
				'uncertaintyInMilliseconds' => 0,
			);
			$return[] = array(
				'namespace' => 'Alexa.PowerController',
				'name' => 'powerState',
				'value' => ($value) ? 'ON' : 'OFF',
				'timeOfSample' => date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
				'uncertaintyInMilliseconds' => 0,
			);
		} else if ($cmd->getSubtype() == 'binary') {
			if (in_array($cmd->getGeneric_type(), array('FLAP_SLIDER'))) {
				$value = (!$value);
			}
			$return[] = array(
				'namespace' => 'Alexa.PowerController',
				'name' => 'powerState',
				'value' => ($value) ? 'ON' : 'OFF',
				'timeOfSample' => date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
				'uncertaintyInMilliseconds' => 0,
			);
		}
		return array('properties' => $return);
	}

	/*     * *********************Méthodes d'instance************************* */

	/*     * **********************Getteur Setteur*************************** */

}

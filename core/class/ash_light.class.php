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

class ash_light {

	/*     * *************************Attributs****************************** */

	private static $_ON = array('ENERGY_ON', 'LIGHT_ON');
	private static $_OFF = array('ENERGY_OFF', 'LIGHT_OFF');
	private static $_STATE = array('ENERGY_STATE', 'LIGHT_STATE');

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
			if (in_array($cmd->getGeneric_type(), self::$_ON)) {
				if (!ash::findCapability($return['capabilities'], 'Alexa.PowerController')) {
					$return['capabilities'][] = array(
						'type' => 'AlexaInterface',
						'interface' => 'Alexa.PowerController',
						'version' => 3,
						'properties' => array(
							'supported' => array(
								array('name' => 'powerState'),
							),
						),
						'proactivelyReported' => true,
						'retrievable' => true,
					);
				}
				$return['cookie']['cmd_set_on'] = $cmd->getId();
			}

			if (in_array($cmd->getGeneric_type(), self::$_OFF)) {
				if (!ash::findCapability($return['capabilities'], 'Alexa.PowerController')) {
					$return['capabilities'][] = array(
						'type' => 'AlexaInterface',
						'interface' => 'Alexa.PowerController',
						'version' => 3,
						'properties' => array(
							'supported' => array(
								array('name' => 'powerState'),
							),
						),
						'proactivelyReported' => true,
						'retrievable' => true,
					);
				}
				$return['cookie']['cmd_set_off'] = $cmd->getId();
			}

			if (in_array($cmd->getGeneric_type(), array('LIGHT_SLIDER'))) {
				if (!ash::findCapability($return['capabilities'], 'Alexa.PowerController')) {
					$return['capabilities'][] = array(
						'type' => 'AlexaInterface',
						'interface' => 'Alexa.PowerController',
						'version' => 3,
						'properties' => array(
							'supported' => array(
								array('name' => 'powerState'),
							),
						),
						'proactivelyReported' => true,
						'retrievable' => true,
					);
				}
				if (!ash::findCapability($return['capabilities'], 'Alexa.BrightnessController')) {
					$return['capabilities'][] = array(
						'type' => 'AlexaInterface',
						'interface' => 'Alexa.BrightnessController',
						'version' => 3,
						'properties' => array(
							'supported' => array(
								array('name' => 'AdjustBrightness'),
							),
						),
						'proactivelyReported' => true,
						'retrievable' => true,
					);
				}
				$return['cookie']['cmd_set_slider'] = $cmd->getId();
			}
			if (in_array($cmd->getGeneric_type(), array('LIGHT_SET_COLOR'))) {
				continue;
				if (!ash::findCapability($return['capabilities'], 'Alexa.ColorController')) {
					$return['capabilities'][] = array(
						'type' => 'AlexaInterface',
						'interface' => 'Alexa.ColorController',
						'version' => 3,
						'properties' => array(
							'supported' => array(
								array('name' => 'color'),
							),
						),
						'proactivelyReported' => true,
						'retrievable' => true,
					);
				}
				$return['cookie']['cmd_set_color'] = $cmd->getId();
			}
			if (in_array($cmd->getGeneric_type(), self::$_STATE)) {
				$return['cookie']['cmd_get_state'] = $cmd->getId();
			}
		}
		if (count($return['capabilities']) == 0) {
			return array();
		}
		$return['capabilities'][] = array(
			"type" => "AlexaInterface",
			"interface" => "Alexa",
			"version" => "3",
		);
		return $return;
	}

	public static function query($_device, $_infos) {
		return self::getState($_device, $_infos);
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
					$cmd->execCmd(array('slider' => 100));
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
					$cmd->execCmd(array('slider' => 0));
				}
				break;
			case 'SetBrightness':
				if (isset($_directive['endpoint']['cookie']['cmd_set_slider'])) {
					$cmd = cmd::byId($_directive['endpoint']['cookie']['cmd_set_slider']);
				}
				if (is_object($cmd)) {
					$value = $cmd->getConfiguration('minValue', 0) + ($_directive['payload']['brightness'] / 100 * ($cmd->getConfiguration('maxValue', 100) - $cmd->getConfiguration('minValue', 0)));
					$cmd->execCmd(array('slider' => $value));
				}
				break;
			case 'SetColor':

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
				'timeOfSample' => date('Y-m-d\TH:i:s\Z'),
				'uncertaintyInMilliseconds' => 0,
			);
		} else if ($cmd->getSubtype() == 'binary') {
			$return[] = array(
				'namespace' => 'Alexa.PowerController',
				'name' => 'powerState',
				'value' => ($value) ? 'ON' : 'OFF',
				'timeOfSample' => date('Y-m-d\TH:i:s\Z'),
				'uncertaintyInMilliseconds' => 0,
			);
		} else if ($cmd->getSubtype() == 'string') {
			$return[] = array(
				'namespace' => 'Alexa.ColorController',
				'name' => 'color',
				'value' => $value,
				'timeOfSample' => date('Y-m-d\TH:i:s\Z'),
				'uncertaintyInMilliseconds' => 0,
			);
		}
		return array('properties' => $return);
	}

	/*     * *********************Méthodes d'instance************************* */

	/*     * **********************Getteur Setteur*************************** */

}
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
			return 'deviceNotFound';
		}
		if ($eqLogic->getIsEnable() == 0) {
			return 'deviceNotFound';
		}
		$return = array();
		$return['id'] = $eqLogic->getId();
		$return['type'] = $_device->getType();
		$return['name'] = array('name' => $eqLogic->getHumanName(), 'nicknames' => $_device->getPseudo());
		$return['traits'] = array();
		$return['customData'] = array();
		$return['willReportState'] = false;
		foreach ($eqLogic->getCmd() as $cmd) {
			if (in_array($cmd->getGeneric_type(), self::$_ON)) {
				if (!in_array('action.devices.traits.OnOff', $return['traits'])) {
					$return['traits'][] = 'action.devices.traits.OnOff';
				}
				$return['customData']['cmd_set_on'] = $cmd->getId();
			}
			if (in_array($cmd->getGeneric_type(), self::$_OFF)) {
				if (!in_array('action.devices.traits.OnOff', $return['traits'])) {
					$return['traits'][] = 'action.devices.traits.OnOff';
				}
				$return['customData']['cmd_set_off'] = $cmd->getId();
			}
			if (in_array($cmd->getGeneric_type(), array('LIGHT_SLIDER'))) {
				if (!in_array('action.devices.traits.OnOff', $return['traits'])) {
					$return['traits'][] = 'action.devices.traits.OnOff';
				}
				if (!in_array('action.devices.traits.Brightness', $return['traits'])) {
					$return['traits'][] = 'action.devices.traits.Brightness';
				}
				$return['customData']['cmd_set_slider'] = $cmd->getId();
			}
			if (in_array($cmd->getGeneric_type(), array('LIGHT_SET_COLOR'))) {
				if (!in_array('action.devices.traits.ColorSpectrum', $return['traits'])) {
					$return['traits'][] = 'action.devices.traits.ColorSpectrum';
				}
				$return['customData']['cmd_set_color'] = $cmd->getId();
				if (!isset($return['attributes'])) {
					$return['attributes'] = array();
				}
				$return['attributes']['colorModel'] = 'RGB';
			}
			if (in_array($cmd->getGeneric_type(), self::$_STATE)) {
				$return['willReportState'] = true;
				$return['customData']['cmd_get_state'] = $cmd->getId();
			}
		}
		if (count($return['traits']) == 0) {
			return array();
		}
		return $return;
	}

	public static function query($_device, $_infos) {
		return self::getState($_device, $_infos);
	}

	public static function exec($_device, $_executions, $_infos) {
		$return = array('status' => 'ERROR');
		$eqLogic = $_device->getLink();
		if (!is_object($eqLogic)) {
			return $return;
		}
		if ($eqLogic->getIsEnable() == 0) {
			return $return;
		}
		foreach ($_executions as $execution) {
			$cmd = null;
			try {
				switch ($execution['command']) {
					case 'action.devices.commands.OnOff':
						if ($execution['params']['on']) {
							if (isset($_infos['customData']['cmd_set_on'])) {
								$cmd = cmd::byId($_infos['customData']['cmd_set_on']);
							} else if (isset($_infos['customData']['cmd_set_slider'])) {
								$cmd = cmd::byId($_infos['customData']['cmd_set_slider']);
							}
							if (!is_object($cmd)) {
								break;
							}
							if ($cmd->getSubtype() == 'other') {
								$cmd->execCmd();
								$return = array('status' => 'SUCCESS');
							} else if ($cmd->getSubtype() == 'slider') {
								$cmd->execCmd(array('slider' => 100));
								$return = array('status' => 'SUCCESS');
							}
						} else {
							if (isset($_infos['customData']['cmd_set_off'])) {
								$cmd = cmd::byId($_infos['customData']['cmd_set_off']);
							} else if (isset($_infos['customData']['cmd_set_slider'])) {
								$cmd = cmd::byId($_infos['customData']['cmd_set_slider']);
							}
							if (!is_object($cmd)) {
								break;
							}
							if ($cmd->getSubtype() == 'other') {
								$cmd->execCmd();
								$return = array('status' => 'SUCCESS');
							} else if ($cmd->getSubtype() == 'slider') {
								$cmd->execCmd(array('slider' => 0));
								$return = array('status' => 'SUCCESS');
							}
						}
						break;
					case 'action.devices.commands.ColorAbsolute':
						if (isset($_infos['customData']['cmd_set_color'])) {
							$cmd = cmd::byId($_infos['customData']['cmd_set_color']);
						}
						if (is_object($cmd)) {
							$cmd->execCmd(array('color' => '#' . str_pad(dechex($execution['params']['color']['spectrumRGB']), 6, '0', STR_PAD_LEFT)));
							$return = array('status' => 'SUCCESS');
						}
						break;
					case 'action.devices.commands.BrightnessAbsolute':
						if (isset($_infos['customData']['cmd_set_slider'])) {
							$cmd = cmd::byId($_infos['customData']['cmd_set_slider']);
						}
						if (is_object($cmd)) {
							$value = $cmd->getConfiguration('minValue', 0) + ($execution['params']['brightness'] / 100 * ($cmd->getConfiguration('maxValue', 100) - $cmd->getConfiguration('minValue', 0)));
							$cmd->execCmd(array('slider' => $value));
							$return = array('status' => 'SUCCESS');
						}
						break;
				}
			} catch (Exception $e) {
				$return = array('status' => 'ERROR');
			}
		}
		$return['states'] = self::getState($_device, $_infos);
		return $return;
	}

	public static function getState($_device, $_infos) {
		$return = array();
		$cmd = null;
		if (isset($_infos['customData']['cmd_get_state'])) {
			$cmd = cmd::byId($_infos['customData']['cmd_get_state']);
		}
		if (!is_object($cmd)) {
			return $return;
		}
		$value = $cmd->execCmd();
		if ($cmd->getSubtype() == 'numeric') {
			$return['brightness'] = $value / $cmd->getConfiguration('maxValue', 100) * 100;
			$return['on'] = ($return['brightness'] > 0);
		} else if ($cmd->getSubtype() == 'binary') {
			$return['on'] = boolval($value);
		} else if ($cmd->getSubtype() == 'string') {
			$return['color'] = array(
				'spectrumRGB' => hexdec(str_replace('#', '', $value)),
			);
		}
		return $return;
	}

	/*     * *********************Méthodes d'instance************************* */

	/*     * **********************Getteur Setteur*************************** */

}
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
						'proactivelyReported' => false,
					        'retrievable' => true,
					),
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
						'proactivelyReported' => false,
					        'retrievable' => true,
					),
				);
				$return['cookie']['cmd_set_off'] = $cmd->getId();
			}

			if (in_array($cmd->getGeneric_type(), array('LIGHT_SLIDER'))) {
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
				$return['capabilities']['Alexa.BrightnessController'] = array(
					'type' => 'AlexaInterface',
					'interface' => 'Alexa.BrightnessController',
					'version' => 3,
					'properties' => array(
						'supported' => array(
							array('name' => 'AdjustBrightness'),
						),
					),
					'proactivelyReported' => false,
					'retrievable' => true,
				);
				$return['cookie']['cmd_set_slider'] = $cmd->getId();
			}
			if (in_array($cmd->getGeneric_type(), array('LIGHT_SET_COLOR'))) {
				$return['capabilities']['Alexa.ColorController'] = array(
					'type' => 'AlexaInterface',
					'interface' => 'Alexa.ColorController',
					'version' => 3,
					'properties' => array(
						'supported' => array(
							array('name' => 'color'),
						),
						'proactivelyReported' => false,
					        'retrievable' => true,
					),
				);
				$return['cookie']['cmd_set_color'] = $cmd->getId();
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
				if (isset($_directive['endpoint']['cookie']['cmd_set_color'])) {
					$cmd = cmd::byId($_directive['endpoint']['cookie']['cmd_set_color']);
				}
				if (is_object($cmd)) {
					$value = self::hslToHex(array($_directive['payload']['color']['hue'], $_directive['payload']['color']['saturation'], $_directive['payload']['color']['brightness']));
					$cmd->execCmd(array('color' => '#' . $value));
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
			$return[] = array(
				'namespace' => 'Alexa.PowerController',
				'name' => 'powerState',
				'value' => ($value) ? 'ON' : 'OFF',
				'timeOfSample' => date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
				'uncertaintyInMilliseconds' => 0,
			);
		} else if ($cmd->getSubtype() == 'string') {
			$return[] = array(
				'namespace' => 'Alexa.ColorController',
				'name' => 'color',
				'value' => self::hexToHsl(str_replace('#', '', $value)),
				'timeOfSample' => date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
				'uncertaintyInMilliseconds' => 0,
			);
		}
		return array('properties' => $return);
	}

	public static function hexToHsl($hex) {
		$hex = array($hex[0] . $hex[1], $hex[2] . $hex[3], $hex[4] . $hex[5]);
		$rgb = array_map(function ($part) {return hexdec($part) / 255;}, $hex);
		$max = max($rgb);
		$min = min($rgb);
		$l = ($max + $min) / 2;
		if ($max == $min) {
			$h = $s = 0;
		} else {
			$diff = $max - $min;
			$s = $l > 0.5 ? $diff / (2 - $max - $min) : $diff / ($max + $min);
			switch ($max) {
				case $rgb[0]:
					$h = ($rgb[1] - $rgb[2]) / $diff + ($rgb[1] < $rgb[2] ? 6 : 0);
					break;
				case $rgb[1]:
					$h = ($rgb[2] - $rgb[0]) / $diff + 2;
					break;
				case $rgb[2]:
					$h = ($rgb[0] - $rgb[1]) / $diff + 4;
					break;
			}
			$h /= 6;
		}
		return array('hue' => $h, 'saturation' => $s, 'brightness' => $l);
	}

	public static function hslToHex($hsl) {
		list($h, $s, $l) = $hsl;
		if ($s == 0) {
			$r = $g = $b = 1;
		} else {
			$q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
			$p = 2 * $l - $q;

			$r = self::hue2rgb($p, $q, $h + 1 / 3);
			$g = self::hue2rgb($p, $q, $h);
			$b = self::hue2rgb($p, $q, $h - 1 / 3);
		}
		return self::rgb2hex($r) . self::rgb2hex($g) . self::rgb2hex($b);
	}

	public static function rgb2hex($rgb) {
		return str_pad(dechex($rgb * 255), 2, '0', STR_PAD_LEFT);
	}

	public static function hue2rgb($p, $q, $t) {
		if ($t < 0) {
			$t += 1;
		}
		if ($t > 1) {
			$t -= 1;
		}
		if ($t < 1 / 6) {
			return $p + ($q - $p) * 6 * $t;
		}
		if ($t < 1 / 2) {
			return $q;
		}
		if ($t < 2 / 3) {
			return $p + ($q - $p) * (2 / 3 - $t) * 6;
		}
		return $p;
	}

	/*     * *********************Méthodes d'instance************************* */

	/*     * **********************Getteur Setteur*************************** */

}

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
	private static $_COLOR_STATE = array('LIGHT_COLOR');
	private static $_COLOR = array('LIGHT_SET_COLOR');
	private static $_BRIGHTNESS = array('LIGHT_SLIDER','LIGHT_SET_BRIGHTNESS');
	private static $_BRIGHTNESS_STATE = array('LIGHT_STATE','LIGHT_BRIGHTNESS');
	
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
		$return['cookie'] = array('none' => 'empty');
		$return['displayCategories'] = array($_device->getType());
		$return['capabilities'] = array();
		
		foreach ($eqLogic->getCmd() as $cmd) {
			if (in_array($cmd->getGeneric_type(), self::$_ON)) {
				$return['capabilities']['Alexa.PowerController'] = array(
					'type' => 'AlexaInterface',
					'interface' => 'Alexa.PowerController',
					'version' => '3',
					'properties' => array(
						'supported' => array(
							array('name' => 'powerState'),
						),
						'proactivelyReported' => false,
						'retrievable' => false,
					),
				);
				$return['cookie']['cmd_set_on'] = $cmd->getId();
			}
			
			if (in_array($cmd->getGeneric_type(), self::$_OFF)) {
				$return['capabilities']['Alexa.PowerController'] = array(
					'type' => 'AlexaInterface',
					'interface' => 'Alexa.PowerController',
					'version' => '3',
					'properties' => array(
						'supported' => array(
							array('name' => 'powerState'),
						),
						'proactivelyReported' => false,
						'retrievable' => false,
					),
				);
				$return['cookie']['cmd_set_off'] = $cmd->getId();
			}
			
			if (in_array($cmd->getGeneric_type(), self::$_BRIGHTNESS)) {
				$return['capabilities']['Alexa.PowerController'] = array(
					'type' => 'AlexaInterface',
					'interface' => 'Alexa.PowerController',
					'version' => '3',
					'properties' => array(
						'supported' => array(
							array('name' => 'powerState'),
						),
						'proactivelyReported' => false,
						'retrievable' => false,
					),
				);
				$return['capabilities']['Alexa.BrightnessController'] = array(
					'type' => 'AlexaInterface',
					'interface' => 'Alexa.BrightnessController',
					'version' => '3',
					'properties' => array(
						'supported' => array(
							array('name' => 'brightness'),
						),
						'proactivelyReported' => false,
						'retrievable' => false,
					),
				);
				$return['cookie']['cmd_set_slider'] = $cmd->getId();
			}
			if (in_array($cmd->getGeneric_type(), self::$_COLOR)) {
				$return['capabilities']['Alexa.ColorController'] = array(
					'type' => 'AlexaInterface',
					'interface' => 'Alexa.ColorController',
					'version' => '3',
					'properties' => array(
						'supported' => array(
							array('name' => 'color'),
						),
						'proactivelyReported' => false,
						'retrievable' => false,
					),
				);
				$return['cookie']['cmd_set_color'] = $cmd->getId();
			}
		}
		foreach ($eqLogic->getCmd() as $cmd) {
			if (in_array($cmd->getGeneric_type(), self::$_STATE)) {
				if(isset($return['capabilities']['Alexa.PowerController'])){
					$return['capabilities']['Alexa.PowerController']['properties']['retrievable'] = true;
				}
				if(isset($return['capabilities']['Alexa.BrightnessController'])){
					$return['capabilities']['Alexa.BrightnessController']['properties']['retrievable'] = true;
				}
				$return['cookie']['cmd_get_state'] = $cmd->getId();
			}
			if (in_array($cmd->getGeneric_type(), self::$_BRIGHTNESS_STATE) && $cmd->getSubType() == 'numeric') {
				if(isset($return['capabilities']['Alexa.BrightnessController'])){
					$return['capabilities']['Alexa.BrightnessController']['properties']['retrievable'] = true;
				}
				$return['cookie']['cmd_get_brightness_state'] = $cmd->getId();
			}
			if (in_array($cmd->getGeneric_type(), self::$_COLOR_STATE)) {
				if(isset($return['capabilities']['Alexa.ColorController'])){
					$return['capabilities']['Alexa.ColorController']['properties']['retrievable'] = true;
				}
				$return['cookie']['cmd_get_state_color'] = $cmd->getId();
			}
		}
		if (count($return['capabilities']) == 0) {
			return array('missingGenericType' => array(
				__('On',__FILE__) => self::$_ON,
				__('Off',__FILE__) => self::$_OFF,
				__('Etat',__FILE__) => self::$_STATE,
				__('Luminosité',__FILE__) => self::$_BRIGHTNESS,
				__('Etat luminosité',__FILE__) => self::$_BRIGHTNESS_STATE,
				__('Couleur',__FILE__) => self::$_COLOR,
				__('Etat couleur',__FILE__) => self::$_COLOR_STATE,
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
				if (is_object($cmd)) {
					$cmd->execCmd();
				}
			}
			if (isset($_directive['endpoint']['cookie']['cmd_set_slider'])) {
				$cmd = cmd::byId($_directive['endpoint']['cookie']['cmd_set_slider']);
				if (is_object($cmd)) {
					$cmd->execCmd(array('slider' => 100));
				}
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
				$value = self::hslToRgb($_directive['payload']['color']['hue'], $_directive['payload']['color']['saturation']*100, $_directive['payload']['color']['brightness']*100);
				$color = sprintf("#%02x%02x%02x", $value[0], $value[1], $value[2]);
				$cmd->execCmd(array('color' => $color));
			}
			break;
		}
		return self::getState($_device, $_directive);
	}
	
	public static function getState($_device, $_directive) {
		$return = array();
		$cmd = null;
		
		if (isset($_directive['endpoint']['cookie']['cmd_get_brightness_state'])) {
			$cmd = cmd::byId($_directive['endpoint']['cookie']['cmd_get_brightness_state']);
			if (is_object($cmd)) {
				$value = $cmd->execCmd();
				$value = ($value / $cmd->getConfiguration('maxValue',100) * 100) + $cmd->getConfiguration('minValue',0);
				if($value > 100){
					$value = 100;
				}
				if($value < 0){
					$value = 0;
				}
				$return['Alexa.BrightnessController'] = array(
					'namespace' => 'Alexa.BrightnessController',
					'name' => 'brightness',
					'value' => $value,
					'timeOfSample' => date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
					'uncertaintyInMilliseconds' => 0,
				);
				$return['Alexa.PowerController'] = array(
					'namespace' => 'Alexa.PowerController',
					'name' => 'powerState',
					'value' => ($value) ? 'ON' : 'OFF',
					'timeOfSample' => date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
					'uncertaintyInMilliseconds' => 0,
				);
			}
		}
		if (isset($_directive['endpoint']['cookie']['cmd_get_state'])) {
			$cmd = cmd::byId($_directive['endpoint']['cookie']['cmd_get_state']);
			if (is_object($cmd)) {
				$value = $cmd->execCmd();
				if ($cmd->getSubtype() == 'numeric') {
					$return['Alexa.PowerController'] = array(
						'namespace' => 'Alexa.PowerController',
						'name' => 'powerState',
						'value' => ($value) ? 'ON' : 'OFF',
						'timeOfSample' => date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
						'uncertaintyInMilliseconds' => 0,
					);
				} else if ($cmd->getSubtype() == 'binary') {
					$return['Alexa.PowerController'] = array(
						'namespace' => 'Alexa.PowerController',
						'name' => 'powerState',
						'value' => ($value) ? 'ON' : 'OFF',
						'timeOfSample' => date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
						'uncertaintyInMilliseconds' => 0,
					);
				}
			}
		}
		if (isset($_directive['endpoint']['cookie']['cmd_get_state_color'])) {
			$cmd = cmd::byId($_directive['endpoint']['cookie']['cmd_get_state_color']);
			if (is_object($cmd)) {
				$value = $cmd->execCmd();
				if ($cmd->getSubtype() == 'string') {
					list($r, $g, $b) = sscanf($value, "#%02x%02x%02x");
					$value = self::rgb_to_hsv($r, $g, $b);
					$return['Alexa.ColorController'] = array(
						'namespace' => 'Alexa.ColorController',
						'name' => 'color',
						'value' => array('hue' => $value[0],'saturation'=>$value[1]/100,'brightness'=>$value[2]/100),
						'timeOfSample' => date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
						'uncertaintyInMilliseconds' => 0,
					);
				}
			}
		}
		return array('properties' => array_values($return));
	}
	
	public static function rgb_to_hsv($R, $G, $B) {
		$R = ($R / 255);
		$G = ($G / 255);
		$B = ($B / 255);
		$maxRGB = max($R, $G, $B);
		$minRGB = min($R, $G, $B);
		$chroma = $maxRGB - $minRGB;
		$computedV = 100 * $maxRGB;
		if ($chroma == 0) {
			return array(0, 0, $computedV);
		}
		$computedS = 100 * ($chroma / $maxRGB);
		if ($R == $minRGB) {
			$h = 3 - (($G - $B) / $chroma);
		} elseif ($B == $minRGB) {
			$h = 1 - (($R - $G) / $chroma);
		} else {
			$h = 5 - (($B - $R) / $chroma);
		}
		$computedH = 60 * $h;
		return array($computedH, $computedS, $computedV);
	}
	
	public static function rgbToHsl( $r, $g, $b ) {
		$oldR = $r;
		$oldG = $g;
		$oldB = $b;
		$r /= 255;
		$g /= 255;
		$b /= 255;
		$max = max( $r, $g, $b );
		$min = min( $r, $g, $b );
		$h;
		$s;
		$l = ( $max + $min ) / 2;
		$d = $max - $min;
		if( $d == 0 ){
			$h = $s = 0; // achromatic
		} else {
			$s = $d / ( 1 - abs( 2 * $l - 1 ) );
			switch( $max ){
				case $r:
				$h = 60 * fmod( ( ( $g - $b ) / $d ), 6 );
				if ($b > $g) {
					$h += 360;
				}
				break;
				case $g:
				$h = 60 * ( ( $b - $r ) / $d + 2 );
				break;
				case $b:
				$h = 60 * ( ( $r - $g ) / $d + 4 );
				break;
			}
		}
		return array( round( $h, 2 ), round( $s, 2 ), round( $l, 2 ) );
	}
	public static function hslToRgb($iH, $iS, $iV){
		if ($iH < 0) {
			$iH = 0;
		}
		if ($iH > 360) {
			$iH = 360;
		}
		if ($iS < 0) {
			$iS = 0;
		}
		if ($iS > 100) {
			$iS = 100;
		}
		if ($iV < 0) {
			$iV = 0;
		}
		if ($iV > 100) {
			$iV = 100;
		}
		$dS = $iS / 100.0;
		$dV = $iV / 100.0;
		$dC = $dV * $dS;
		$dH = $iH / 60.0;
		if ($dH === null) {
			$dH = 0;
		}
		$dT = $dH;
		while ($dT >= 2.0) {
			$dT -= 2.0;
		}
		$dX = $dC * (1 - abs($dT - 1));
		if ($dH >= 0.0 && $dH < 1.0) {
			$dR = $dC;
			$dG = $dX;
			$dB = 0.0;
		} else if ($dH >= 1.0 && $dH < 2.0) {
			$dR = $dX;
			$dG = $dC;
			$dB = 0.0;
		} else if ($dH >= 2.0 && $dH < 3.0) {
			$dR = 0.0;
			$dG = $dC;
			$dB = $dX;
		} else if ($dH >= 3.0 && $dH < 4.0) {
			$dR = 0.0;
			$dG = $dX;
			$dB = $dC;
		} else if ($dH >= 4.0 && $dH < 5.0) {
			$dR = $dX;
			$dG = 0.0;
			$dB = $dC;
		} else if ($dH >= 5.0 && $dH < 6.0) {
			$dR = $dC;
			$dG = 0.0;
			$dB = $dX;
		} else {
			$dR = 0.0;
			$dG = 0.0;
			$dB = 0.0;
		}
		$dM = $dV - $dC;
		$dR += $dM;
		$dG += $dM;
		$dB += $dM;
		$dR *= 255;
		$dG *= 255;
		$dB *= 255;
		return array(round($dR), round($dG), round($dB));
	}
	
	/*     * *********************Méthodes d'instance************************* */
	
	/*     * **********************Getteur Setteur*************************** */
	
}

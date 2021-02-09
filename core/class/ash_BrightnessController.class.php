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

class ash_BrightnessController {
	
	/*     * *************************Attributs****************************** */
	
	
	private static $_BRIGHTNESS = array('LIGHT_SLIDER','LIGHT_SET_BRIGHTNESS');
	private static $_BRIGHTNESS_STATE = array('LIGHT_STATE','LIGHT_BRIGHTNESS');
	
	/*     * ***********************Methode static*************************** */
	
	public static function discover($_device,$_eqLogic) {
		foreach ($_eqLogic->getCmd() as $cmd) {
			if (in_array($cmd->getGeneric_type(), self::$_BRIGHTNESS)) {
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
				$return['cookie']['BrightnessController_setSlider'] = $cmd->getId();
			}
		}
		foreach ($_eqLogic->getCmd() as $cmd) {
			if (in_array($cmd->getGeneric_type(), self::$_BRIGHTNESS_STATE) && $cmd->getSubType() == 'numeric') {
				if(isset($return['capabilities']['Alexa.BrightnessController'])){
					$return['capabilities']['Alexa.BrightnessController']['properties']['retrievable'] = true;
				}
				$return['cookie']['BrightnessController_getState'] = $cmd->getId();
			}
		}
		return $return;
	}
	
	public static function needGenericType(){
		return array(
			__('Luminosité',__FILE__) => self::$_BRIGHTNESS,
			__('Etat luminosité',__FILE__) => self::$_BRIGHTNESS_STATE
		);
	}
	
	public static function exec($_device, $_directive) {
		switch ($_directive['header']['name']) {
			case 'TurnOn':
			if (isset($_directive['endpoint']['cookie']['BrightnessController_setSlider'])) {
				$cmd = cmd::byId($_directive['endpoint']['cookie']['BrightnessController_setSlider']);
				if (is_object($cmd)) {
					$cmd->execCmd(array('slider' => $cmd->getConfiguration('maxValue', 100)));
				}
			}
			break;
			case 'TurnOff':
			if (isset($_directive['endpoint']['cookie']['BrightnessController_setSlider'])) {
				$cmd = cmd::byId($_directive['endpoint']['cookie']['BrightnessController_setSlider']);
			}
			if (!is_object($cmd)) {
				throw new Exception('ENDPOINT_UNREACHABLE');
			}
			$cmd->execCmd(array('slider' => 0));
			break;
			case 'SetBrightness':
			if (isset($_directive['endpoint']['cookie']['BrightnessController_setSlider'])) {
				$cmd = cmd::byId($_directive['endpoint']['cookie']['BrightnessController_setSlider']);
			}
			if (is_object($cmd)) {
				$value = $cmd->getConfiguration('minValue', 0) + ($_directive['payload']['brightness'] / 100 * ($cmd->getConfiguration('maxValue', 100) - $cmd->getConfiguration('minValue', 0)));
				$cmd->execCmd(array('slider' => $value));
			}
			break;
		}
		return self::getState($_device, $_directive);
	}
	
	public static function getState($_device, $_directive) {
		$return = array();
		if (isset($_directive['endpoint']['cookie']['BrightnessController_getState'])) {
			$cmd = cmd::byId($_directive['endpoint']['cookie']['BrightnessController_getState']);
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
			}
		}
		return array('properties' => array_values($return));
	}
	/*     * *********************Méthodes d'instance************************* */
	
	/*     * **********************Getteur Setteur*************************** */
	
}

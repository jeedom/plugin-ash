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

class ash_ColorTemperatureController {
	
	/*     * *************************Attributs****************************** */
	
	private static $_COLOR_TEMP_STATE = array('LIGHT_COLOR_TEMP');
	private static $_COLOR_TEMP = array('LIGHT_SET_COLOR_TEMP');
	
	/*     * ***********************Methode static*************************** */
	
	public static function discover($_device,$_eqLogic) {
		$return = array();
		foreach ($_eqLogic->getCmd() as $cmd) {
			if (in_array($cmd->getGeneric_type(), self::$_COLOR_TEMP)) {
				$return['capabilities']['Alexa.ColorTemperatureController'] = array(
					'type' => 'AlexaInterface',
					'interface' => 'Alexa.ColorTemperatureController',
					'version' => '3',
					'properties' => array(
						'supported' => array(
							array('name' => 'colorTemperatureInKelvin'),
						),
						'proactivelyReported' => false,
						'retrievable' => false,
					),
				);
				$return['cookie']['ColorTemperatureController_setState'] = $cmd->getId();
			}
		}
		foreach ($_eqLogic->getCmd() as $cmd) {
			if (in_array($cmd->getGeneric_type(), self::$_COLOR_TEMP_STATE)) {
				if(isset($return['capabilities']['Alexa.ColorTemperatureController'])){
					$return['capabilities']['Alexa.ColorTemperatureController']['properties']['retrievable'] = true;
				}
				$return['cookie']['ColorTemperatureController_getState'] = $cmd->getId();
			}
		}
		return $return;
	}
	
	public static function needGenericType(){
		return array(
			__('Température couleur',__FILE__) => self::$_COLOR_TEMP,
			__('Etat température couleur',__FILE__) => self::$_COLOR_TEMP_STATE,
		);
	}
	
	public static function exec($_device, $_directive) {
		switch ($_directive['header']['name']) {
			case 'SetColorTemperature':
			if (isset($_directive['endpoint']['cookie']['ColorTemperatureController_setState'])) {
				$cmd = cmd::byId($_directive['endpoint']['cookie']['ColorTemperatureController_setState']);
			}
			if (is_object($cmd)) {
				$value = ((($_directive['payload']['colorTemperatureInKelvin'] - 2200)/7000)*$cmd->getConfiguration('maxValue',100))+$cmd->getConfiguration('minValue',0);
				if($_device->getOptions('ColorTemperatureController::invertSetColorTemp')){
					$value = 9900 - $value;
				}
				$cmd->execCmd(array('slider' => $value));
			}
			break;
		}
		return self::getState($_device, $_directive);
	}
	
	public static function getState($_device, $_directive) {
		$return = array();
		if (isset($_directive['endpoint']['cookie']['ColorTemperatureController_getState'])) {
			$cmd = cmd::byId($_directive['endpoint']['cookie']['ColorTemperatureController_getState']);
			if (is_object($cmd)) {
				$value = $cmd->execCmd();
				$value = ((($value-$cmd->getConfiguration('minValue',0))/$cmd->getConfiguration('maxValue',100))*7700)+2200;
				if($_device->getOptions('ColorTemperatureController::invertGetColorTemp')){
					$value = 9900 - $value;
				}
				$return['Alexa.ColorTemperatureController'] = array(
					'namespace' => 'Alexa.ColorTemperatureController',
					'name' => 'colorTemperatureInKelvin',
					'value' => $value,
					'timeOfSample' => date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
					'uncertaintyInMilliseconds' => 0,
				);
			}
		}
		
		return array('properties' => array_values($return));
	}
	
	public static function getHtmlConfiguration($_eqLogic){
		echo '<div class="form-group">';
		echo '<label class="col-sm-3 control-label">{{Inverser l\'action de température de couleur}}</label>';
		echo '<div class="col-sm-3">';
		echo '<input type="checkbox" class="deviceAttr" data-l1key="options" data-l2key="ColorTemperatureController::invertSetColorTemp"></input>';
		echo '</div>';
		echo '</div>';
		echo '<div class="form-group">';
		echo '<label class="col-sm-3 control-label">{{Inverser l\'état de température de couleur}}</label>';
		echo '<div class="col-sm-3">';
		echo '<input type="checkbox" class="deviceAttr" data-l1key="options" data-l2key="ColorTemperatureController::invertGetColorTemp"></input>';
		echo '</div>';
		echo '</div>';
		echo '</div>';
	}
	
	/*     * *********************Méthodes d'instance************************* */
	
	/*     * **********************Getteur Setteur*************************** */
	
}

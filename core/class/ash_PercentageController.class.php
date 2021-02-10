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
class ash_PercentageController {
	/*     * *************************Attributs****************************** */
	private static $_SLIDER = array('FLAP_SLIDER');
	private static $_STATE = array('FLAP_STATE', 'FLAP_BSO_STATE','GARAGE_STATE','BARRIER_STATE');
	/*     * ***********************Methode static*************************** */
	public static function discover($_device,$_eqLogic) {
		$return = array();
		$return['capabilities'] = array();
		foreach ($_eqLogic->getCmd() as $cmd) {
			if (in_array($cmd->getGeneric_type(), self::$_ON) || in_array($cmd->getGeneric_type(), self::$_OFF) || in_array($cmd->getGeneric_type(), self::$_SLIDER)) {
				$return['capabilities']['Alexa.PercentageController'] = array (
					'type' => 'AlexaInterface',
					'interface' => 'Alexa.PercentageController',
					'version' => '3',
					'properties' => array (
						'supported' => array (
							array ('name' => 'percentage'),
						),
						'proactivelyReported' => false,
						'retrievable' => false,
					)
				);
				if (in_array($cmd->getGeneric_type(), self::$_SLIDER)) {
					$return['cookie']['PercentageController_setSlider'] = $cmd->getId();
				}
			}
		}
		foreach ($_eqLogic->getCmd() as $cmd) {
			if (in_array($cmd->getGeneric_type(), self::$_STATE)) {
				if(isset($return['capabilities']['Alexa.PercentageController'])){
					$return['capabilities']['Alexa.PercentageController']['properties']['retrievable'] = true;
				}
				$return['cookie']['PercentageController_getState'] = $cmd->getId();
			}
		}
		return $return;
	}
	
	public static function needGenericType(){
		return array(
			__('Position',__FILE__) => self::$_SLIDER,
			__('Etat',__FILE__) => self::$_STATE
		);
	}
	
	public static function exec($_device, $_directive) {
		switch ($_directive['header']['name']) {
			case 'AdjustPercentage' :
			if (isset($_directive['endpoint']['cookie']['PercentageController_setSlider'])) {
				if (isset($_directive['endpoint']['cookie']['PercentageController_getState'])) {
					$cmdState = cmd::byId($_directive['endpoint']['cookie']['PercentageController_getState']);
				}
				if (!is_object($cmdState)) {
					throw new Exception('ENDPOINT_UNREACHABLE');
				}
				$value = $cmd->getConfiguration('minValue', 0) + ($_directive['payload']['percentageDelta'] / 100 * ($cmd->getConfiguration('maxValue', 100) - $cmd->getConfiguration('minValue', 0)));
				$cmd->execCmd(array('slider' => $cmdState->execCmd() + $value));
			}
			case 'SetPercentage':
			if (isset($_directive['endpoint']['cookie']['PercentageController_setSlider'])) {
				$cmd = cmd::byId($_directive['endpoint']['cookie']['PercentageController_setSlider']);
				if (!is_object($cmd)) {
					throw new Exception('ENDPOINT_UNREACHABLE');
				}
				$value = $cmd->getConfiguration('minValue', 0) + ($_directive['payload']['percentage'] / 100 * ($cmd->getConfiguration('maxValue', 100) - $cmd->getConfiguration('minValue', 0)));
				$cmd->execCmd(array('slider' => $value));
				break;
			}
			break;
		}
		return self::getState($_device, $_directive);
	}
	
	public static function getState($_device, $_directive) {
		$return = array();
		$cmd = null;
		if (isset($_directive['endpoint']['cookie']['PercentageController_getState'])) {
			$cmd = cmd::byId($_directive['endpoint']['cookie']['PercentageController_getState']);
		}
		if (!is_object($cmd)) {
			return $return;
		}
		$return[] = array(
			'namespace' => 'Alexa.PercentageController',
			'name' => 'percentage',
			'value' => $cmd->execCmd(),
			'timeOfSample' => date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
			'uncertaintyInMilliseconds' => 0,
		);
		return array('properties' => $return);
	}
	/*     * *********************Méthodes d'instance************************* */
	/*     * **********************Getteur Setteur*************************** */
}

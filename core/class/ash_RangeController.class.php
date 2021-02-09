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
class ash_RangeController {
	/*     * *************************Attributs****************************** */
	private static $_SLIDER = array('FLAP_SLIDER');
	private static $_STATE = array('FLAP_STATE', 'FLAP_BSO_STATE','GARAGE_STATE','BARRIER_STATE');
	private static $_ON = array('FLAP_BSO_UP', 'FLAP_UP','GB_OPEN');
	private static $_OFF = array('FLAP_BSO_DOWN', 'FLAP_DOWN','GB_CLOSE');
	/*     * ***********************Methode static*************************** */
	public static function discover($_device,$_eqLogic) {
		$return['capabilities'] = array();
		foreach ($_eqLogic->getCmd() as $cmd) {
			if (in_array($cmd->getGeneric_type(), self::$_ON) || in_array($cmd->getGeneric_type(), self::$_OFF) || in_array($cmd->getGeneric_type(), self::$_SLIDER)) {
				$return['capabilities']['Alexa.RangeController'] = array (
					'type' => 'AlexaInterface',
					'interface' => 'Alexa.RangeController',
					'instance' => 'Blind.Lift',
					'version' => '3',
					'properties' => array (
						'supported' => array (
							array ('name' => 'rangeValue'),
						),
						'proactivelyReported' => false,
						'retrievable' => false,
					),
					'capabilityResources' => array (
						'friendlyNames' => array (
							array (
								'@type' => 'asset',
								'value' => array ('assetId' => 'Alexa.Setting.Opening'),
							),
						),
					),
					'configuration' => array (
						'supportedRange' => array (
							'minimumValue' => 0,
							'maximumValue' => 100,
							'precision' => 1,
						),
						'unitOfMeasure' => 'Alexa.Unit.Percent',
					),
					'semantics' => array (
						'actionMappings' => array (
							array (
								'@type' => 'ActionsToDirective',
								'actions' => array ('Alexa.Actions.Close'),
								'directive' => array (
									'name' => 'SetRangeValue',
									'payload' => array ('rangeValue' => 0),
								),
							),
							array (
								'@type' => 'ActionsToDirective',
								'actions' => array ('Alexa.Actions.Open'),
								'directive' => array (
									'name' => 'SetRangeValue',
									'payload' => array ('rangeValue' => 100),
								),
							),
							array (
								'@type' => 'ActionsToDirective',
								'actions' => array ('Alexa.Actions.Lower'),
								'directive' => array (
									'name' => 'AdjustRangeValue',
									'payload' => array (
										'rangeValueDelta' => -10,
										'rangeValueDeltaDefault' => false,
									),
								),
							),
							array (
								'@type' => 'ActionsToDirective',
								'actions' => array ('Alexa.Actions.Raise'),
								'directive' => array (
									'name' => 'AdjustRangeValue',
									'payload' => array (
										'rangeValueDelta' => 10,
										'rangeValueDeltaDefault' => false,
									),
								),
							),
						),
						'stateMappings' => array (
							array (
								'@type' => 'StatesToValue',
								'states' => array ('Alexa.States.Closed'),
								'value' => 0,
							),
							array (
								'@type' => 'StatesToRange',
								'states' => array ('Alexa.States.Open'),
								'range' => array (
									'minimumValue' => 1,
									'maximumValue' => 100,
								),
							),
						),
					),
				);
				if (in_array($cmd->getGeneric_type(), self::$_ON)) {
					$return['cookie']['RangeController_setOn'] = $cmd->getId();
				}
				if (in_array($cmd->getGeneric_type(), self::$_OFF)) {
					$return['cookie']['RangeController_setOff'] = $cmd->getId();
				}
				if (in_array($cmd->getGeneric_type(), self::$_SLIDER)) {
					$return['cookie']['RangeController_setSlider'] = $cmd->getId();
				}
			}
		}
		foreach ($_eqLogic->getCmd() as $cmd) {
			if (in_array($cmd->getGeneric_type(), self::$_STATE)) {
				if(isset($return['capabilities']['Alexa.RangeController'])){
					$return['capabilities']['Alexa.RangeController']['properties']['retrievable'] = true;
				}
				$return['cookie']['RangeController_getState'] = $cmd->getId();
			}
		}
		if (count($return['capabilities']) == 0) {
			return array('missingGenericType' => array(
				__('Position',__FILE__) => self::$_SLIDER,
				__('On',__FILE__) => self::$_ON,
				__('Off',__FILE__) => self::$_OFF,
				__('Etat',__FILE__) => self::$_STATE
			));
		}
		return $return;
	}
	public static function exec($_device, $_directive) {
		switch ($_directive['header']['name']) {
			case 'AdjustRangeValue' :
			if (isset($_directive['endpoint']['cookie']['RangeController_setSlider'])) {
				if (isset($_directive['endpoint']['cookie']['RangeController_getState'])) {
					$cmdState = cmd::byId($_directive['endpoint']['cookie']['RangeController_getState']);
				}
				if (!is_object($cmdState)) {
					throw new Exception('ENDPOINT_UNREACHABLE');
				}
				$value = $cmd->getConfiguration('minValue', 0) + ($_directive['payload']['rangeValueDelta'] / 100 * ($cmd->getConfiguration('maxValue', 100) - $cmd->getConfiguration('minValue', 0)));
				$cmd->execCmd(array('slider' => $cmdState->execCmd() + $value));
			}
			case 'SetRangeValue':
			if (isset($_directive['endpoint']['cookie']['RangeController_setSlider'])) {
				$cmd = cmd::byId($_directive['endpoint']['cookie']['RangeController_setSlider']);
				if (!is_object($cmd)) {
					throw new Exception('ENDPOINT_UNREACHABLE');
				}
				if(isset($_directive['payload']['rangeValue'])){
					$value = $cmd->getConfiguration('minValue', 0) + ($_directive['payload']['rangeValue'] / 100 * ($cmd->getConfiguration('maxValue', 100) - $cmd->getConfiguration('minValue', 0)));
					if($_device->getOptions('shutter::invert',0) == 1){
						$value = 100 - $value;
					}
					$cmd->execCmd(array('slider' => $value));
				}
				break;
			}
			if($_directive['payload']['rangeValue'] > 50){
				if (isset($_directive['endpoint']['cookie']['RangeController_setOn'])) {
					$cmd = cmd::byId($_directive['endpoint']['cookie']['RangeController_setOn']);
				}
				if (!is_object($cmd)) {
					break;
				}
				$cmd->execCmd();
			}else{
				if (isset($_directive['endpoint']['cookie']['RangeController_setOff'])) {
					$cmd = cmd::byId($_directive['endpoint']['cookie']['RangeController_setOff']);
				}
				if (!is_object($cmd)) {
					break;
				}
				$cmd->execCmd();
			}
			break;
		}
		return self::getState($_device, $_directive);
	}
	
	public static function getState($_device, $_directive) {
		$return = array();
		$cmd = null;
		if (isset($_directive['endpoint']['cookie']['RangeController_getState'])) {
			$cmd = cmd::byId($_directive['endpoint']['cookie']['RangeController_getState']);
		}
		if (!is_object($cmd)) {
			return $return;
		}
		$return[] = array(
			'namespace' => 'Alexa.RangeController',
			'instance' => 'Blind.Lift',
			'name' => 'rangeValue',
			'value' => $cmd->execCmd(),
			'timeOfSample' => date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
			'uncertaintyInMilliseconds' => 0,
		);
		return array('properties' => $return);
	}
	/*     * *********************Méthodes d'instance************************* */
	/*     * **********************Getteur Setteur*************************** */
}

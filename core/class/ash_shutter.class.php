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
class ash_shutter {
	/*     * *************************Attributs****************************** */
	private static $_SLIDER = array('FLAP_SLIDER');
	private static $_STATE = array('FLAP_STATE', 'FLAP_BSO_STATE','GARAGE_STATE','BARRIER_STATE');
	private static $_ON = array('FLAP_BSO_UP', 'FLAP_UP','GB_OPEN');
	private static $_OFF = array('FLAP_BSO_DOWN', 'FLAP_DOWN','GB_CLOSE');
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
		$return['cookie'] = array();
		$return['displayCategories'] = array($_device->getType());
		$return['capabilities'] = array();
		foreach ($eqLogic->getCmd() as $cmd) {
			if (in_array($cmd->getGeneric_type(), self::$_SLIDER)) {
				$return['capabilities']['Alexa.PercentageController'] = array(
					'type' => 'AlexaInterface',
					'interface' => 'Alexa.PercentageController',
					'version' => '3',
					'properties' => array(
						'supported' => array(
							array('name' => 'percentage'),
						),
						'proactivelyReported' => false,
						'retrievable' => false,
					),
				);
				$return['cookie']['cmd_set_slider'] = $cmd->getId();
			}
			if (in_array($cmd->getGeneric_type(), self::$_ON)) {
				$return['capabilities']['Alexa.PercentageController'] = array(
					'type' => 'AlexaInterface',
					'interface' => 'Alexa.PercentageController',
					'version' => '3',
					'properties' => array(
						'supported' => array(
							array('name' => 'percentage'),
						),
						'proactivelyReported' => false,
						'retrievable' => false,
					),
				);
				$return['cookie']['cmd_set_on'] = $cmd->getId();
			}
			if (in_array($cmd->getGeneric_type(), self::$_OFF)) {
				$return['capabilities']['Alexa.PercentageController'] = array(
					'type' => 'AlexaInterface',
					'interface' => 'Alexa.PercentageController',
					'version' => '3',
					'properties' => array(
						'supported' => array(
							array('name' => 'percentage'),
						),
						'proactivelyReported' => false,
						'retrievable' => false,
					),
				);
				$return['cookie']['cmd_set_off'] = $cmd->getId();
			}
		}
		foreach ($eqLogic->getCmd() as $cmd) {
			if (in_array($cmd->getGeneric_type(), self::$_STATE)) {
				if(isset($return['capabilities']['Alexa.PercentageController'])){
					$return['capabilities']['Alexa.PercentageController']['properties']['retrievable'] = true;
				}
				$return['cookie']['cmd_get_state'] = $cmd->getId();
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
			case 'SetPercentage':
			if (isset($_directive['endpoint']['cookie']['cmd_set_slider'])) {
				$cmd = cmd::byId($_directive['endpoint']['cookie']['cmd_set_slider']);
				if (!is_object($cmd)) {
					throw new Exception('ENDPOINT_UNREACHABLE');
				}
				if(isset($_directive['payload']['percentage'])){
					$value = $cmd->getConfiguration('minValue', 0) + ($_directive['payload']['percentage'] / 100 * ($cmd->getConfiguration('maxValue', 100) - $cmd->getConfiguration('minValue', 0)));
					if($_device->getOptions('shutter::invert',0) == 1){
						$value = 100 - $value;
					}
					$cmd->execCmd(array('slider' => $value));
				}
				if(isset($_directive['payload']['percentageDelta'])){
					if (isset($_directive['endpoint']['cookie']['cmd_get_state'])) {
						$cmdState = cmd::byId($_directive['endpoint']['cookie']['cmd_get_state']);
					}
					if (!is_object($cmdState)) {
						throw new Exception('ENDPOINT_UNREACHABLE');
					}
					$value = $cmd->getConfiguration('minValue', 0) + ($_directive['payload']['percentageDelta'] / 100 * ($cmd->getConfiguration('maxValue', 100) - $cmd->getConfiguration('minValue', 0)));
					$cmd->execCmd(array('slider' => $cmdState->execCmd() + $value));
				}
				break;
			}
			if($_directive['payload']['percentage'] > 50){
				if (isset($_directive['endpoint']['cookie']['cmd_set_on'])) {
					$cmd = cmd::byId($_directive['endpoint']['cookie']['cmd_set_on']);
				}
				if (!is_object($cmd)) {
					break;
				}
				$cmd->execCmd();
			}else{
				if (isset($_directive['endpoint']['cookie']['cmd_set_off'])) {
					$cmd = cmd::byId($_directive['endpoint']['cookie']['cmd_set_off']);
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
		if (isset($_directive['endpoint']['cookie']['cmd_get_state'])) {
			$cmd = cmd::byId($_directive['endpoint']['cookie']['cmd_get_state']);
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

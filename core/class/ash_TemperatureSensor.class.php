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
class ash_TemperatureSensor {
	/*     * *************************Attributs****************************** */
	private static $_TEMPERATURE = array('TEMPERATURE');
	
	/*     * ***********************Methode static*************************** */
	public static function discover($_device,$_eqLogic) {
		$return['capabilities'] = array();
		foreach ($_eqLogic->getCmd() as $cmd) {
			if (in_array($cmd->getGeneric_type(), self::$_TEMPERATURE)) {
				$return['capabilities']['Alexa.TemperatureSensor'] = array(
					'type' => 'AlexaInterface',
					'interface' => 'Alexa.TemperatureSensor',
					'version' => '3',
					'properties' => array(
						'supported' => array(
							array('name' => 'temperature'),
						),
						'proactivelyReported' => false,
						'retrievable' => true,
					),
				);
				$return['cookie']['TemperatureSensor_getState'] = $cmd->getId();
			}
		}
		if (count($return['capabilities']) == 0) {
			return array('missingGenericType' => array(
				__('Température',__FILE__) => self::$_TEMPERATURE
			));
		}
		return $return;
	}
	public static function exec($_device, $_directive) {
		return self::getState($_device, $_directive);
	}
	
	public static function getState($_device, $_directive) {
		$return = array();
		if (isset($_directive['endpoint']['cookie']['TemperatureSensor_getState'])) {
			$cmd = cmd::byId($_directive['endpoint']['cookie']['TemperatureSensor_getState']);
			if (is_object($cmd)) {
				$return[] = array(
					'namespace' => 'Alexa.TemperatureSensor',
					'name' => 'temperature',
					'value' => array(
						'value' => $cmd->execCmd(),
						'scale' => 'CELSIUS',
					),
					'timeOfSample' => date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
					'uncertaintyInMilliseconds' => 0,
				);
			}
		}
		return array('properties' => $return);
	}
	/*     * *********************Méthodes d'instance************************* */
	/*     * **********************Getteur Setteur*************************** */
}

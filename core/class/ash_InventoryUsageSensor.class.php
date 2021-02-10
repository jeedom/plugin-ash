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
class ash_InventoryUsageSensor {
	/*     * *************************Attributs****************************** */
	private static $_USAGE_SENSOR = array('USAGE_SENSOR');
	
	/*     * ***********************Methode static*************************** */
	public static function discover($_device,$_eqLogic) {
		$return = array();
		$return['capabilities'] = array();
		foreach ($_eqLogic->getCmd() as $cmd) {
			if (in_array($cmd->getGeneric_type(), self::$_USAGE_SENSOR)) {
				$return['capabilities']['Alexa.InventoryUsageSensor'] = array(
					'type' => 'AlexaInterface',
					'interface' => 'Alexa.InventoryUsageSensor',
					'instance' => $cmd->getId(),
					'version' => '3',
					'properties' => array(
						'supported' => array(
							array('name' => 'level'),
						),
						'configuration' => array(
							'measurement' => array(
								'@type' => 'Count'
							),
						),
						'proactivelyReported' => false,
						'retrievable' => true,
					),
				);
				$return['cookie']['InventoryUsageSensor_getState'] = $cmd->getId();
			}
		}
		return $return;
	}
	
	public static function needGenericType(){
		return array(
			__('Consommation',__FILE__) => self::$_USAGE_SENSOR
		);
	}
	
	public static function exec($_device, $_directive) {
		return self::getState($_device, $_directive);
	}
	
	public static function getState($_device, $_directive) {
		$return = array();
		if (isset($_directive['endpoint']['cookie']['InventoryUsageSensor_getState'])) {
			$cmd = cmd::byId($_directive['endpoint']['cookie']['InventoryUsageSensor_getState']);
			if (is_object($cmd)) {
				$return[] = array(
					'namespace' => 'Alexa.InventoryUsageSensor',
					'instance' => $cmd->getId(),
					'name' => 'level',
					'value' => array(
						'value' => $cmd->execCmd(),
						'@type' => 'Count'
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

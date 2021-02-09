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
class ash_ContactSensor {
	/*     * *************************Attributs****************************** */
	private static $_CONTACT = array('LOCK_STATE', 'BARRIER_STATE', 'GARAGE_STATE', 'OPENING','OPENING_WINDOW');
	
	/*     * ***********************Methode static*************************** */
	public static function discover($_device,$_eqLogic) {
		foreach ($_eqLogic->getCmd() as $cmd) {
			if (in_array($cmd->getGeneric_type(), self::$_CONTACT)) {
				$return['capabilities']['Alexa.ContactSensor'] = array(
					'type' => 'AlexaInterface',
					'interface' => 'Alexa.ContactSensor',
					'version' => '3',
					'properties' => array(
						'supported' => array(
							array('name' => 'detectionState'),
						),
						'proactivelyReported' => false,
						'retrievable' => true,
					),
				);
				$return['cookie']['ContactSensor_getState'] = $cmd->getId();
			}
		}
		if (count($return['capabilities']) == 0) {
			return array('missingGenericType' => array(
				__('Contacteur',__FILE__) => self::$_CONTACT
			));
		}
		return $return;
	}
	public static function exec($_device, $_directive) {
		return self::getState($_device, $_directive);
	}
	
	public static function getState($_device, $_directive) {
		$return = array();
		if (isset($_directive['endpoint']['cookie']['ContactSensor_getState'])) {
			$cmd = cmd::byId($_directive['endpoint']['cookie']['ContactSensor_getState']);
			if (is_object($cmd)) {
				$value = $cmd->execCmd();
				if ($cmd->getDisplay('invertBinary') == 1) {
					$value = ($value) ? false : true;
				}
				$value = ($value == 0) ? 'DETECTED' : 'NOT_DETECTED';
				$return[] = array(
					'namespace' => 'Alexa.ContactSensor',
					'name' => 'detectionState',
					'value' => $value,
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

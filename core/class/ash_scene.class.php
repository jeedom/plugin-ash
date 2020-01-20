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

class ash_scene {

	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	public static function buildDevice($_device) {
		$return = array();
		$return['endpointId'] = 'scene::' . $_device->getId();
		$return['friendlyName'] = $_device->getOptions('name');
		$return['description'] = $_device->getOptions('name');
		$return['manufacturerName'] = 'Jeedom';
		$return['cookie'] = array();
		$return['displayCategories'] = array($_device->getType());
		$return['capabilities'] = array();
		$return['capabilities']['Alexa.SceneController'] = array(
			'type' => 'AlexaInterface',
			'interface' => 'Alexa.SceneController',
			'version' => '3',
			'proactivelyReported' => false,
			'supportsDeactivation' => (count($_device->getOptions('outAction')) > 0),
		);
		return $return;
	}

	public static function exec($_device, $_directive) {
		$responseHeader = $_directive['header'];
		$responseHeader['namespace'] = 'Alexa.SceneController';
		switch ($_directive['header']['name']) {
			case 'Activate':
				self::doAction($_device, 'inAction');
				$responseHeader['name'] = 'ActivationStarted';
				break;
			case 'Deactivate':
				self::doAction($_device, 'outAction');
				$responseHeader['name'] = 'DeactivationStarted';
				break;
		}
		if (isset($_directive['endpoint']['cookie'])) {
			unset($_directive['endpoint']['cookie']);
		}
		$return = array(
			'context' => array('toto' => 'plop'),
			'event' => array(
				'header' => $responseHeader,
				'endpoint' => $_directive['endpoint'],
				'payload' => array(
					'cause' => array(
						'type' => 'VOICE_INTERACTION',
					),
					'timestamp' => date('Y-m-d\TH:i:s\Z'),
				),
			),
		);
		return $return;
	}

	public function doAction($_device, $_action) {
		if (!is_array($_device->getOptions($_action))) {
			return;
		}
		foreach ($_device->getOptions($_action) as $action) {
			try {
				$options = array();
				if (isset($action['options'])) {
					$options = $action['options'];
				}
				scenarioExpression::createAndExec('action', $action['cmd'], $options);
			} catch (Exception $e) {
				log::add('gsh', 'error', __('Erreur lors de l\'éxecution de ', __FILE__) . $action['cmd'] . __('. Détails : ', __FILE__) . $e->getMessage());
			}
		}
	}

	/*     * *********************Méthodes d'instance************************* */

	/*     * **********************Getteur Setteur*************************** */

}

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

include_file('core', 'ash_scene', 'class', 'ash');
include_file('core', 'ash_PowerController', 'class', 'ash');
include_file('core', 'ash_BrightnessController', 'class', 'ash');
include_file('core', 'ash_ColorController', 'class', 'ash');
include_file('core', 'ash_TemperatureSensor', 'class', 'ash');
include_file('core', 'ash_MotionSensor', 'class', 'ash');
include_file('core', 'ash_ContactSensor', 'class', 'ash');
include_file('core', 'ash_ModeController', 'class', 'ash');
include_file('core', 'ash_RangeController', 'class', 'ash');
include_file('core', 'ash_ThermostatController', 'class', 'ash');
include_file('core', 'ash_ColorTemperatureController', 'class', 'ash');
include_file('core', 'ash_InventoryLevelSensor', 'class', 'ash');
include_file('core', 'ash_InventoryUsageSensor', 'class', 'ash');
include_file('core', 'ash_PercentageController', 'class', 'ash');
include_file('core', 'ash_PowerLevelController', 'class', 'ash');
include_file('core', 'ash_ToggleController', 'class', 'ash');

class ash extends eqLogic {
	
	/*     * *************************Attributs****************************** */
	
	public static function getSupportedType(){
		return array(
			'THERMOSTAT' => array('name' => __('Thermostat',__FILE__) ,'skills' =>array('TemperatureSensor','ThermostatController')),
			'LIGHT' => array('name' => __('Lumière',__FILE__) ,'skills' =>array('PowerController','BrightnessController','ColorController','ColorTemperatureController')),
			'SWITCH' => array('name' => __('Switch',__FILE__) ,'skills' =>array('PowerController')),
			'SMARTPLUG' => array('name' => __('Prise',__FILE__) ,'skills' =>array('PowerController')),
			'OTHER' => array('name' => __('Mode',__FILE__) ,'skills' =>array('ModeController')),
			'TEMPERATURE_SENSOR' => array('name' => __('Capteur de température',__FILE__) ,'skills' =>array('TemperatureSensor')),
			'MOTION_SENSOR' => array('name' => __('Detecteur de mouvement',__FILE__) ,'skills' =>array('MotionSensor')),
			'CONTACT_SENSOR' => array('name' => __('Detecteur d\'ouverture',__FILE__) ,'skills' =>array('ContactSensor')),
			'SCENE_TRIGGER' => array('name' => __('Scene',__FILE__) ,'class' =>'ash_scene'),
			'INTERIOR_BLIND' => array('name' => __('Rideaux',__FILE__) ,'skills' =>array('RangeController')),
			'AIR_PURIFIER' => array('name' => __('Purificateur d\'air',__FILE__) ,'skills' =>array('PowerController')),
			'CHRISTMAS_TREE' => array('name' => __('Arbre de Noel',__FILE__) ,'skills' =>array('PowerController')),
			'COFFEE_MAKER' => array('name' => __('Machine a cafée',__FILE__) ,'skills' =>array('PowerController')),
			'OVEN' => array('name' => __('Four',__FILE__) ,'skills' =>array('PowerController','TemperatureSensor')),
			'SLOW_COOKER' => array('name' => __('Mijoteuse',__FILE__) ,'skills' =>array('PowerController','TemperatureSensor')),
			'SECURITY_SYSTEM' => array('name' => __('Alarme',__FILE__) ,'skills' =>array('PowerController','ModeController')),
			'SMARTLOCK' => array('name' => __('Serrure',__FILE__) ,'skills' =>array('PowerController')),
			'LAPTOP' => array('name' => __('PC portable',__FILE__) ,'skills' =>array('PowerController')),
			'COMPUTER' => array('name' => __('Ordinateur',__FILE__) ,'skills' =>array('PowerController')),
			'DOOR' => array('name' => __('Porte',__FILE__) ,'skills' =>array('ContactSensor')),
			'EXTERIOR_BLIND' => array('name' => __('Volet',__FILE__) ,'skills' =>array('PowerController')),
			'FAN' => array('name' => __('Ventilateur',__FILE__) ,'skills' =>array('PowerController')),
			'GARAGE_DOOR' => array('name' => __('Porte de garage',__FILE__) ,'skills' =>array('ContactSensor')),
			'MICROWAVE' => array('name' => __('Micro-onde',__FILE__) ,'skills' =>array('PowerController')),
			'NETWORK_HARDWARE' => array('name' => __('Equipement réseaux',__FILE__) ,'skills' =>array('PowerController')),
			'PRINTER' => array('name' => __('Imprimante',__FILE__) ,'skills' =>array('PowerController')),
			'ROUTER' => array('name' => __('Routeur',__FILE__) ,'skills' =>array('PowerController')),
			'SCREEN' => array('name' => __('Ecran',__FILE__) ,'skills' =>array('PowerController')),
			'TV' => array('name' => __('TV',__FILE__) ,'skills' =>array('PowerController')),
			'VEHICLE' => array('name' => __('Vehicule',__FILE__) ,'skills' =>array('PowerController')),
		);
	}
	
	/*     * ***********************Methode static*************************** */
	
	public static function postConfig_enableApikeyRotate($_value){
		$cron = cron::byClassAndFunction('ash', 'rotateApiKey');
		if($_value == 1){
			if(!is_object($cron)){
				$cron = new cron();
			}
			$cron->setClass('ash');
			$cron->setFunction('rotateApiKey');
			$cron->setLastRun(date('Y-m-d H:i:s'));
			$cron->setSchedule(rand(0,59).' '.rand(0,23).' * * *');
			$cron->save();
		}else{
			if(is_object($cron)){
				$cron->remove();
			}
		}
	}
	
	public static function rotateApiKey($_option = array()){
		config::save('api', config::genKey(), 'ash');
		self::sendJeedomConfig();
	}
	
	public static function sendJeedomConfig() {
		$market = repo_market::getJsonRpc();
		if (!$market->sendRequest('ash::configAsh', array('ash::apikey' => jeedom::getApiKey('ash'), 'ash::url' => network::getNetworkAccess('external')))) {
			throw new Exception($market->getError(), $market->getErrorCode());
		}
	}
	
	public static function voiceAssistantInfo() {
		$market = repo_market::getJsonRpc();
		if (!$market->sendRequest('voiceAssistant::info')) {
			throw new Exception($market->getError(), $market->getErrorCode());
		}
		return $market->getResult();
	}
	
	public static function sync($_group='') {
		$return = array();
		$devices = ash_devices::all(true);
		$names = array();
		foreach ($devices as $device) {
			if($device->getOptions('group') != '' && $device->getOptions('group') != $_group){
				continue;
			}
			$info = $device->buildDevice();
			if(isset($info['friendlyName']) && trim($info['friendlyName']) != ''){
				if(isset($names[$info['friendlyName']])){
					log::add('ash','error',__('Deux équipements et/ou scène avec le meme nom : ',__FILE__).json_encode($info));
					$device->setOptions('configState', 'NOK');
					$device->save();
					continue;
				}
				$names[$info['friendlyName']] = $info['friendlyName'];
			}
			if (!is_array($info) || count($info) == 0 || isset($info['missingGenericType'])) {
				$device->setOptions('configState', 'NOK');
				if(isset($info['missingGenericType'])){
					$device->setOptions('missingGenericType',$info['missingGenericType']);
				}
				$device->save();
				continue;
			}
			$info['capabilities'] = array_values($info['capabilities']);
			$return[] = $info;
			$device->setOptions('configState', 'OK');
			$device->setOptions('missingGenericType','');
			$device->save();
		}
		return array('endpoints' => $return);
	}
	
	public static function exec($_data) {
		$directive = $_data['data']['directive'];
		$responseHeader = $directive['header'];
		$responseHeader['namespace'] = 'Alexa';
		if($responseHeader['name'] == 'ReportState'){
			$responseHeader['name'] = 'StateReport';
		}else{
			$responseHeader['name'] = 'Response';
		}
		$return = array(
			'context' => '',
			'event' => array(
				'header' => $responseHeader,
				'endpoint' => $directive['endpoint'],
				'payload' => new stdClass(),
			),
		);
		if (strpos($directive['endpoint']['endpointId'], 'scene::') !== false) {
			$device = ash_devices::byId(str_replace('scene::', '', $directive['endpoint']['endpointId']));
		} else {
			$device = ash_devices::byLinkTypeLinkId('eqLogic', $directive['endpoint']['endpointId']);
		}
		if (!is_object($device)) {
			return self::buildErrorResponse($_data, 'NO_SUCH_ENDPOINT');
		} else if ($device->getEnable() == 0) {
			return self::buildErrorResponse($_data, 'ENDPOINT_UNREACHABLE');
		} else {
			try {
				$result = $device->exec($directive);
				
				if (isset($result['event'])) {
					$return = $result;
				} else {
					$return['context'] = $result;
				}
				
			} catch (Exception $e) {
				return self::buildErrorResponse($_data, $e->getMessage());
			}
		}
		if(isset($return['event']['endpoint']['cookie'])){
			unset($return['event']['endpoint']['cookie']);
		}
		return $return;
	}
	
	public static function buildErrorResponse($_data, $_name, $_payload = array()) {
		$responseHeader = $_data['data']['directive']['header'];
		$responseHeader['name'] = $_name;
		$response = array(
			'event' => array(
				'header' => $responseHeader,
			),
			'payload' => array($_payload),
		);
		return $response;
	}
	
	/*     * *********************Méthodes d'instance************************* */
	
	
	/*     * **********************Getteur Setteur*************************** */
}

class ashCmd extends cmd {
	/*     * *************************Attributs****************************** */
	
	/*     * ***********************Methode static*************************** */
	
	/*     * *********************Methode d'instance************************* */
	
	public function execute($_options = array()) {
		
	}
	
	/*     * **********************Getteur Setteur*************************** */
}

class ash_devices {
	/*     * *************************Attributs****************************** */
	
	private $id;
	private $enable;
	private $link_type;
	private $link_id;
	private $type;
	private $options;
	private $_link = null;
	private $_cmds = null;
	
	/*     * ***********************Methode static*************************** */
	
	public static function all($_onlyEnable = false) {
		$sql = 'SELECT ' . DB::buildField(__CLASS__) . '
		FROM ash_devices';
		if ($_onlyEnable) {
			$sql .= ' WHERE enable=1';
		}
		return DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
	}
	
	public static function byId($_id) {
		$values = array(
			'id' => $_id,
		);
		$sql = 'SELECT ' . DB::buildField(__CLASS__) . '
		FROM ash_devices
		WHERE id=:id';
		return DB::Prepare($sql, $values, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__);
	}
	
	public static function byLinkTypeLinkId($_link_type, $_link_id) {
		$values = array(
			'link_type' => $_link_type,
			'link_id' => $_link_id,
		);
		$sql = 'SELECT ' . DB::buildField(__CLASS__) . '
		FROM ash_devices
		WHERE link_type=:link_type
		AND link_id=:link_id';
		return DB::Prepare($sql, $values, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__);
	}
	
	/*     * *********************Methode d'instance************************* */
	
	public function preSave() {
		if ($this->getEnable() == 0) {
			$this->setOptions('configState', '');
		}
	}
	
	public function save() {
		return DB::save($this);
	}
	
	public function remove() {
		DB::remove($this);
	}
	
	public function getLink() {
		if ($this->_link != null) {
			return $this->_link;
		}
		if ($this->getLink_type() == 'eqLogic') {
			$this->_link = eqLogic::byId($this->getLink_id());
		}
		return $this->_link;
	}
	
	public function buildDevice() {
		$supportedType = ash::getSupportedType();
		if (!isset($supportedType[$this->getType()])) {
			return array();
		}
		if(isset($supportedType[$this->getType()]['class'])){
			$class = $supportedType[$this->getType()]['class'];
			if (!class_exists($class)) {
				return array();
			}
			if ($this->getLink_type() == 'eqLogic') {
				$eqLogic = $this->getLink();
				if(!is_object($eqLogic) || $eqLogic->getIsEnable() == 0){
					return array();
				}
			}
			return $class::buildDevice($this);
		}
		if(isset($supportedType[$this->getType()]['skills'])){
			$eqLogic = $this->getLink();
			if (!is_object($eqLogic)) {
				return array();
			}
			$return = array();
			$return['endpointId'] = $eqLogic->getId();
			$return['friendlyName'] = $this->getPseudo();
			$return['description'] = $eqLogic->getHumanName();
			$return['manufacturerName'] = 'Jeedom';
			$return['cookie'] = array('none' => 'empty');
			$return['displayCategories'] = array($this->getType());
			$return['capabilities'] = array();
			
			foreach ($supportedType[$this->getType()]['skills'] as $skill) {
				$class = 'ash_'.$skill;
				if (!class_exists($class)) {
					continue;
				}
				$infos = $class::discover($this,$eqLogic);
				if(!isset($infos['capabilities']) || count($infos['capabilities']) == 0){
					continue;
				}
				$return = array_merge_recursive($return,$infos);
			}
			if(count($return['capabilities']) == 0){
				return array();
			}
			$return['capabilities']['AlexaInterface'] = array(
				"type" => "AlexaInterface",
				"interface" => "Alexa",
				"version" => "3",
			);
			return $return;
		}
	}
	
	public function exec($_directive) {
		$supportedType = ash::getSupportedType();
		if (!isset($supportedType[$this->getType()])) {
			return;
		}
		if(isset($supportedType[$this->getType()]['class'])){
			$class = $supportedType[$this->getType()]['class'];
			if (!class_exists($class)) {
				return array();
			}
			$result = $class::exec($this, $_execution, $_infos);
			return $result;
		}
		if(isset($supportedType[$this->getType()]['skills'])){
			$return = array();
			foreach ($supportedType[$this->getType()]['skills'] as $skill) {
				$class = 'ash_'.$skill;
				if (!class_exists($class)) {
					continue;
				}
				$return = array_merge_recursive($return,$class::exec($this, $_directive));
			}
			return $return;
		}
	}
	
	public function getPseudo() {
		if ($this->getOptions('pseudo') != '') {
			return $this->getOptions('pseudo');
		}
		$return = '';
		$eqLogic = $this->getLink();
		$object = $eqLogic->getObject();
		if(is_object($object)){
			$return .= $object->getName().' ';
		}
		$return .= $eqLogic->getName();
		return $return;
	}
	
	/*     * **********************Getteur Setteur*************************** */
	public function getId() {
		return $this->id;
	}
	
	public function setId($id) {
		$this->id = $id;
	}
	
	public function getEnable() {
		return $this->enable;
	}
	
	public function setEnable($enable) {
		$this->enable = $enable;
	}
	
	public function getlink_type() {
		return $this->link_type;
	}
	
	public function setLink_type($link_type) {
		$this->link_type = $link_type;
	}
	
	public function getLink_id() {
		return $this->link_id;
	}
	
	public function setLink_id($link_id) {
		$this->link_id = $link_id;
	}
	
	public function getType() {
		return $this->type;
	}
	
	public function setType($type) {
		$this->type = $type;
	}
	
	public function getOptions($_key = '', $_default = '') {
		return utils::getJsonAttr($this->options, $_key, $_default);
	}
	
	public function setOptions($_key, $_value) {
		$this->options = utils::setJsonAttr($this->options, $_key, $_value);
	}
}

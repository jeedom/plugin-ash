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
include_file('core', 'ash_light', 'class', 'ash');
include_file('core', 'ash_outlet', 'class', 'ash');
include_file('core', 'ash_thermostat', 'class', 'ash');
include_file('core', 'ash_scene', 'class', 'ash');
include_file('core', 'ash_shutter', 'class', 'ash');
include_file('core', 'ash_sensors', 'class', 'ash');

class ash extends eqLogic {
	
	/*     * *************************Attributs****************************** */
	
	public static $_supportedType = array(
		'LIGHT' => array('class' => 'ash_light', 'name' => 'Lumière'),
		'SWITCH' => array('class' => 'ash_outlet', 'name' => 'Switch'),
		'SMARTPLUG' => array('class' => 'ash_outlet', 'name' => 'Prise'),
		'THERMOSTAT' => array('class' => 'ash_thermostat', 'name' => 'Thermostat'),
		'SCENE_TRIGGER' => array('class' => 'ash_scene', 'name' => 'Scene'),
		'SHUTTER' => array('class' => 'ash_shutter', 'name' => 'Volet'),
		'SENSORS' => array('class' => 'ash_sensors', 'name' => 'Capteur (mouvement, contact et température)'),
	);
	
	/*     * ***********************Methode static*************************** */
	
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
	
	public static function generateConfiguration() {
		$return = array(
			"devPortSmartHome" => config::byKey('ashs::port', 'ash'),
			"smartHomeProviderClientId" => config::byKey('ashs::clientId', 'ash'),
			"smartHomeProvideClientSecret" => config::byKey('ashs::clientSecret', 'ash'),
			"masterkey" => config::byKey('ashs::masterkey', 'ash'),
			"jeedomTimeout" => config::byKey('ashs::timeout', 'ash'),
			"url" => config::byKey('ashs::url', 'ash'),
		);
		return $return;
	}
	
	public static function generateUserConf() {
		$return = array(
			"tokens" => array(
				config::byKey('ashs::token', 'ash') => array(
					"uid" => config::byKey('ashs::userid', 'ash'),
					"accessToken" => config::byKey('ashs::token', 'ash'),
					"refreshToken" => config::byKey('ashs::token', 'ash'),
					"userId" => config::byKey('ashs::userid', 'ash'),
				),
			),
			"users" => array(
				config::byKey('ashs::userid', 'ash') => array(
					"uid" => config::byKey('ashs::userid', 'ash'),
					"name" => config::byKey('ashs::username', 'ash'),
					"password" => sha1(config::byKey('ashs::password', 'ash')),
					"tokens" => array(config::byKey('ashs::token', 'ash')),
					"url" => network::getNetworkAccess(config::byKey('ashs::jeedomnetwork', 'ash', 'internal')),
					"apikey" => jeedom::getApiKey('ash'),
				),
			),
			"usernames" => array(
				config::byKey('ashs::username', 'ash') => config::byKey('ashs::userid', 'ash'),
			),
		);
		return $return;
	}
	
	public static function sendDevices() {
		if (config::byKey('mode', 'ash') == 'jeedom') {
			$request_http = new com_http('https://api-aa.jeedom.com/jeedom/sync');
			$request_http->setPost(http_build_query(array(
				'apikey' =>  jeedom::getApiKey('ash'),
				'url' =>  network::getNetworkAccess('external'),
				'hwkey' =>  jeedom::getHardwareKey(),
				'data' => json_encode(self::sync())
			)));
			$result = $request_http->exec(30);
			for($i=1;$i<10;$i++){
				$devices = self::sync($i);
				if(count($devices['endpoints']) == 0){
					continue;
				}
				$request_http = new com_http('https://api-aa.jeedom.com/jeedom/sync');
				$request_http->setPost(http_build_query(array(
					'apikey' =>  jeedom::getApiKey('ash').'-'.$i,
					'url' =>  network::getNetworkAccess('external'),
					'hwkey' =>  jeedom::getHardwareKey(),
					'data' => json_encode($devices)
				)));
				$result = $request_http->exec(30);
			}
		} else {
			$request_http = new com_http(trim(config::byKey('ashs::url', 'ash')) . '/jeedom/sync/devices');
			$post = array(
				'masterkey' => config::byKey('ashs::masterkey', 'ash'),
				'userId' => config::byKey('ashs::userid', 'ash'),
				'data' => json_encode(self::sync(), JSON_UNESCAPED_UNICODE),
			);
			$request_http->setPost(http_build_query($post));
			$result = $request_http->exec(60);
			if (!is_json($result)) {
				throw new Exception($result);
			}
			$result = json_decode($result, true);
			if (!isset($result['success']) || !$result['success']) {
				if (isset($result['message'])) {
					throw new Exception($result['message']);
				}
				throw new Exception(json_encode($result, true));
			}
		}
	}
	
	public static function sync($_group='') {
		$return = array();
		$devices = ash_devices::all(true);
		foreach ($devices as $device) {
			if($device->getOptions('group') != '' && $device->getOptions('group') != $_group){
				continue;
			}
			$info = $device->buildDevice();
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
		if (!isset(ash::$_supportedType[$this->getType()])) {
			return array();
		}
		$class = ash::$_supportedType[$this->getType()]['class'];
		if (!class_exists($class)) {
			return array();
		}
		return $class::buildDevice($this);
	}
	
	public function exec($_directive) {
		if (!isset(ash::$_supportedType[$this->getType()])) {
			return;
		}
		$class = ash::$_supportedType[$this->getType()]['class'];
		if (!class_exists($class)) {
			return array();
		}
		return $class::exec($this, $_directive);
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

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
include_file('core', 'ash_temperature', 'class', 'ash');
include_file('core', 'ash_thermostat', 'class', 'ash');

class ash extends eqLogic {

	/*     * *************************Attributs****************************** */

	public static $_supportedType = array(
		'LIGHT' => array('class' => 'ash_light', 'name' => 'Lumière'),
		'SWITCH' => array('class' => 'ash_outlet', 'name' => 'Switch (volet...)'),
		'SMARTPLUG' => array('class' => 'ash_outlet', 'name' => 'Prise'),
		'TEMPERATURE_SENSOR' => array('class' => 'ash_temperature', 'name' => 'Température'),
		'THERMOSTAT' => array('class' => 'ash_thermostat', 'name' => 'Thermostat'),
	);

	/*     * ***********************Methode static*************************** */

	public static function sendJeedomConfig() {
		$market = repo_market::getJsonRpc();
		if (!$market->sendRequest('ash::configash', array('ash::apikey' => jeedom::getApiKey('ash'), 'ash::url' => network::getNetworkAccess('external')))) {
			throw new Exception($market->getError(), $market->getErrorCode());
		}
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
			$market = repo_market::getJsonRpc();
			if (!$market->sendRequest('ash::sync', array('devices' => self::sync()))) {
				throw new Exception($market->getError(), $market->getErrorCode());
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

	public static function sync() {
		$return = array();
		$devices = ash_devices::all(true);
		foreach ($devices as $device) {
			$info = $device->buildDevice();
			if (!is_array($info) || count($info) == 0) {
				$device->setOptions('configState', 'NOK');
				$device->save();
				continue;
			}
			$info['capabilities'] = array_values($info['capabilities']);
			$return[] = $info;
			$device->setOptions('configState', 'OK');
			$device->save();
		}
		return array('endpoints' => $return);
	}

	public static function exec($_data) {
		$directive = $_data['data']['directive'];
		$responseHeader = $directive['header'];
		$responseHeader['namespace'] = 'Alexa';
		$responseHeader['name'] = 'Response';
		$return = array(
			'context' => '',
			'event' => array(
				'header' => $responseHeader,
				'endpoint' => $directive['endpoint'],
				'payload' => array(),
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
				$return['context'] = $device->exec($directive);
			} catch (Exception $e) {
				return self::buildErrorResponse($_data, $e->getMessage());
			}
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

	public function cronHourly() {
		system::kill('stream2chromecast.py');
		system::kill('avconv -i');
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
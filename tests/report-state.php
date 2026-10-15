<?php
// Standalone regression tests: php tests/report-state.php (no running Jeedom required).
$root = sys_get_temp_dir() . '/ash-test-' . bin2hex(random_bytes(6));
$pluginRoot = $root . '/plugins/ash';
mkdir($pluginRoot . '/core/class', 0700, true);
mkdir($root . '/core/php', 0700, true);
file_put_contents($root . '/core/php/core.inc.php', '<?php');
foreach (glob(__DIR__ . '/../core/class/*.php') as $file) copy($file, $pluginRoot . '/core/class/' . basename($file));

function check($condition, $message) {
	if (!$condition) throw new RuntimeException($message);
	echo 'PASS ' . $message . PHP_EOL;
}
function __($text, $file = '') { return $text; }
function sha512($text) { return hash('sha512', $text); }
function include_file($folder, $name, $type, $plugin = '') {
	global $pluginRoot;
	require_once $pluginRoot . '/core/class/' . $name . '.class.php';
}
class utils {
	public static function getJsonAttr($json, $key, $default = '') {
		$data = is_array($json) ? $json : json_decode($json === null ? '{}' : $json, true);
		return $key === '' ? $data : (isset($data[$key]) ? $data[$key] : $default);
	}
	public static function setJsonAttr($json, $key, $value) {
		$data = is_array($json) ? $json : json_decode($json === null ? '{}' : $json, true);
		$data[$key] = $value;
		return json_encode($data);
	}
	public static function attrChanged($changed, $old, $new) { return $changed || $old !== $new; }
}
class config {
	public static $values = array('ash' => array(), 'core' => array('market::username' => 'test', 'market::password' => 'test', 'service::cloud::url' => 'https://cloud.invalid'));
	public static function byKey($key, $plugin = 'core', $default = '') { return isset(self::$values[$plugin][$key]) ? self::$values[$plugin][$key] : $default; }
	public static function save($key, $value, $plugin = 'core') { self::$values[$plugin][$key] = $value; }
}
class jeedom {
	public static $apikey = 'local-api-key';
	public static function getApiKey($plugin) { return self::$apikey; }
	public static function evaluateExpression($value) { return (bool) $value; }
}
class system {
	public static $workers = 0;
	public static function php($command) { self::$workers++; }
}
class log {
	public static $entries = array();
	public static function add($plugin, $level, $message) { self::$entries[] = $message; }
}
class eqLogic {
	public static $equipment = array();
	public $id = 42;
	public $enabled = 1;
	public $timeout = 0;
	public $commands = array();
	public static function byId($id) { return isset(self::$equipment[$id]) ? self::$equipment[$id] : null; }
	public function getId() { return $this->id; }
	public function getIsEnable() { return $this->enabled; }
	public function getStatus($name, $default = 0) { return $this->timeout; }
	public function getCmd($type = null) { return array_filter($this->commands, function($cmd) use ($type) { return $type === null || $cmd->type === $type; }); }
	public function getName() { return 'Lampe'; }
	public function getHumanName() { return '[Salon][Lampe]'; }
	public function getObject() { return null; }
}
class cmd {
	public static $commands = array();
	public $id;
	public $generic;
	public $type;
	public $value;
	public $subtype;
	public $date = '2026-10-15 08:00:00';
	public $executions = 0;
	public function __construct($id = 0, $generic = '', $type = 'info', $value = 0, $subtype = 'binary') {
		$this->id = $id; $this->generic = $generic; $this->type = $type; $this->value = $value; $this->subtype = $subtype;
		self::$commands[$id] = $this;
	}
	public static function byId($id) { return isset(self::$commands[$id]) ? self::$commands[$id] : null; }
	public function getId() { return $this->id; }
	public function getGeneric_type() { return $this->generic; }
	public function getType() { return $this->type; }
	public function getSubType() { return $this->subtype; }
	public function getValueDate() { return $this->date; }
	public function getDisplay($name, $default = 0) { return $default; }
	public function getConfiguration($name, $default = 0) { return $default; }
	public function execCmd($options = array()) { $this->executions++; return $this->value; }
}
class DB {
	public static $devices = array();
	const FETCH_TYPE_ALL = 1;
	const FETCH_TYPE_ROW = 2;
	public static function buildField($class) { return '*'; }
	public static function save($device) { self::$devices[$device->getId()] = $device; return true; }
	public static function remove($device) { unset(self::$devices[$device->getId()]); }
	public static function Prepare($sql, $values, $fetch, $fetchClass = null, $class = null) {
		if (isset($values['link_id'])) {
			foreach (self::$devices as $device) if ($device->getLink_id() == $values['link_id']) return $device;
			return null;
		}
		if (isset($values['id'])) return isset(self::$devices[$values['id']]) ? self::$devices[$values['id']] : null;
		return array_values(self::$devices);
	}
}
if (!class_exists('PDO')) {
	class PDO { const FETCH_CLASS = 8; }
}
class listener {
	public static $listeners = array();
	public $events = array();
	public $options;
	public static function byClassAndFunction($class, $function, $options) { return isset(self::$listeners[json_encode($options)]) ? self::$listeners[json_encode($options)] : null; }
	public function setClass($class) {}
	public function setFunction($function) {}
	public function setOption($options) { $this->options = $options; }
	public function emptyEvent() { $this->events = array(); }
	public function addEvent($id) { $this->events[] = $id; }
	public function save() { self::$listeners[json_encode($this->options)] = $this; }
	public function remove() { unset(self::$listeners[json_encode($this->options)]); }
}
class com_http {
	public static $sent = array();
	public static $fail = false;
	public static $duringRequest = null;
	private $payload;
	public function __construct($url) {}
	public function setHeader($headers) {}
	public function setLogError($enabled) {}
	public function setPost($payload) { $this->payload = json_decode($payload, true); }
	public function exec($timeout, $retries) {
		self::$sent[] = $this->payload;
		if (self::$duringRequest) { $callback = self::$duringRequest; self::$duringRequest = null; $callback(); }
		if (self::$fail) throw new Exception('Secret-containing HTTP failure');
		return '{"state":"ok","accepted":1}';
	}
}

try {
	date_default_timezone_set('Europe/Paris');
	require $pluginRoot . '/core/class/ash.class.php';
	$eq = new eqLogic();
	$on = new cmd(1, 'LIGHT_ON', 'action', 0, 'other');
	$off = new cmd(2, 'LIGHT_OFF', 'action', 0, 'other');
	$state = new cmd(3, 'LIGHT_STATE', 'info', 0);
	$eq->commands = array($on, $off, $state);
	eqLogic::$equipment[42] = $eq;
	$device = new ash_devices();
	$device->setId(1); $device->setEnable(1); $device->setLink_type('eqLogic'); $device->setLink_id(42); $device->setType('LIGHT'); $device->save();
	check(count(listener::$listeners) === 0, 'reporting is opt-in');
	config::save('reportState::enable', 1, 'ash');
	ash::postConfig_reportState_enable(1);
	check(count(listener::$listeners) === 1 && current(listener::$listeners)->events === array(3), 'only mapped info commands get a listener');
	$endpoint = $device->buildDevice();
	check($endpoint['capabilities']['Alexa.PowerController']['properties']['proactivelyReported'] === true, 'discovery enables proactive reporting for a readable state');
	check($endpoint['endpointId'] === '42', 'endpoint identity stays stable as a string');
	$snapshot = ash_state::properties($device, $endpoint);
	check($snapshot[0]['timeOfSample'] === '2026-10-15T06:00:00Z', 'Jeedom local time is converted to UTC');
	check($snapshot[1]['namespace'] === 'Alexa.EndpointHealth', 'snapshots include endpoint health');
	check($endpoint['capabilities']['Alexa.EndpointHealth']['properties']['proactivelyReported'] === false, 'health does not promise an immediate signal Jeedom cannot provide');
	$invalid = new cmd(4, 'LIGHT_STATE', 'action', 1, 'other');
	try { ash_state::value($invalid, array()); check(false, 'action must not be readable'); } catch (UnexpectedValueException $expected) {}
	check($invalid->executions === 0, 'even a misconfigured state command cannot execute an action');
	$state->value = 'unknown';
	check($device->buildDevice()['capabilities']['Alexa.PowerController']['properties']['proactivelyReported'] === false, 'an unavailable state is not advertised as reportable');
	$state->value = 0;
	ash::reportState(array('eqLogic_id' => 42, 'event_id' => 3, 'value' => 1, 'datetime' => '2026-10-15 08:01:00'));
	ash::reportState(array('eqLogic_id' => 42, 'event_id' => 3, 'value' => 0, 'datetime' => '2026-10-15 08:01:01'));
	$files = glob(ash_reporting::directory() . '/report-*.json');
	check(count($files) === 2, 'rapid ON and OFF events are both retained despite a newer command cache');
	$first = json_decode(file_get_contents($files[0]), true);
	$second = json_decode(file_get_contents($files[1]), true);
	check($first['properties'][0]['value'] === 'ON' && $second['properties'][0]['value'] === 'OFF', 'event values are captured in FIFO order');
	check($first['properties'][0]['timeOfSample'] === '2026-10-15T06:01:00Z', 'event timestamps survive delayed processing');
	ash::reportState(array('eqLogic_id' => 42, 'event_id' => 3, 'value' => 0, 'datetime' => '2026-10-15 08:02:00'));
	check(count(glob(ash_reporting::directory() . '/report-*.json')) === 2, 'unchanged samples do not create reports');
	check($on->executions === 0 && $off->executions === 0, 'capturing state never executes an action');
	com_http::$fail = true;
	ash_reporting::flush();
	check(count(glob(ash_reporting::directory() . '/report-*.json')) === 2, 'failed cloud submission keeps the durable outbox');
	check(strpos(implode(' ', log::$entries), 'Secret-containing') === false, 'HTTP exception details are not logged');
	com_http::$fail = false;
	jeedom::$apikey = 'rotated-api-key';
	ash_reporting::flush();
	check(count(glob(ash_reporting::directory() . '/report-*.json')) === 0, 'acknowledged cloud submissions leave the local outbox');
	check(com_http::$sent[0]['reportId'] === com_http::$sent[1]['reportId'], 'network retries reuse the report identifier');
	check(com_http::$sent[1]['source'] === hash('sha256', 'rotated-api-key'), 'API rotation is respected for queued events');
	$before = $on->executions + $off->executions;
	$response = $device->exec(array('header' => array('name' => 'ReportState')));
	check($on->executions + $off->executions === $before && count($response['properties']) === 2, 'ReportState uses the shared read-only snapshot');
	$device->setEnable(0); $device->save();
	check(count(listener::$listeners) === 0, 'disabling a device removes its listener');
	$device->setEnable(1); $device->save();
	ash_reporting::reset(42);
	ash::reportState(array('eqLogic_id' => 42, 'event_id' => 3, 'value' => 1, 'datetime' => '2026-10-15 08:03:00'));
	config::save('reportState::enable', 0, 'ash'); ash::postConfig_reportState_enable(0);
	check(count(listener::$listeners) === 0 && count(glob(ash_reporting::directory() . '/report-*.json')) === 0, 'disabling reporting removes listeners and queued states');
} finally {
	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
	foreach ($iterator as $entry) $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
	rmdir($root);
}

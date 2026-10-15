<?php

/** Durable local outbox. Only the short file write runs in the command listener. */
class ash_reporting {

	private static $voiceDevice = null;

	public static function voiceDevice($id = null) {
		$previous = self::$voiceDevice;
		self::$voiceDevice = $id;
		return $previous;
	}

	public static function directory() {
		$directory = dirname(__FILE__) . '/../../data/report-state';
		if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
			throw new Exception('Impossible de créer la file des états Alexa');
		}
		if (!file_exists($directory . '/.htaccess')) {
			file_put_contents($directory . '/.htaccess', "Require all denied\nDeny from all\n");
		}
		return $directory;
	}

	private static function lock($name, $blocking = true) {
		$lock = fopen(self::directory() . '/' . $name . '.lock', 'c');
		if ($lock === false) throw new Exception('Impossible de verrouiller la file Alexa');
		if (!flock($lock, LOCK_EX | ($blocking ? 0 : LOCK_NB))) {
			fclose($lock);
			return false;
		}
		return $lock;
	}

	private static function write($path, $data) {
		$json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		if ($json === false) throw new Exception('Etat Alexa non sérialisable');
		$tmp = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';
		if (file_put_contents($tmp, $json) === false || !rename($tmp, $path)) {
			@unlink($tmp);
			throw new Exception('Impossible de conserver un état Alexa');
		}
		chmod($path, 0600);
	}

	public static function uuid() {
		$bytes = random_bytes(16);
		$bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
		$bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
	}

	public static function allowed() {
		return config::byKey('reportState::enable', 'ash', 0) == 1 &&
			!(config::byKey('ashs::disableRequestIf', 'ash') !== '' &&
			jeedom::evaluateExpression(config::byKey('ashs::disableRequestIf', 'ash')));
	}

	public static function capture($options) {
		if (!self::allowed()) return;
		$device = ash_devices::byLinkTypeLinkId('eqLogic', $options['eqLogic_id']);
		if (!is_object($device) || $device->getEnable() != 1) return;
		$endpoint = $device->buildDevice();
		if (empty($endpoint['capabilities']) || !count(ash_state::inputs($endpoint))) return;
		if (isset($options['event_id']) && !in_array((int) $options['event_id'], ash_state::inputs($endpoint), true)) return;
		$lock = self::lock('queue');
		try {
			$directory = self::directory();
			$stateFile = $directory . '/state-' . (int) $device->getLink_id() . '.json';
			$previous = file_exists($stateFile) ? json_decode(file_get_contents($stateFile), true) : array();
			$state = ash_state::delta(ash_state::properties($device, $endpoint, isset($options['event_id']) ? $options : null), is_array($previous) ? $previous : array());
			$reportable = array();
			foreach ($endpoint['capabilities'] as $capability) {
				if (empty($capability['properties']['proactivelyReported'])) continue;
				foreach ($capability['properties']['supported'] as $property) {
					$reportable[] = ash_state::key(array('namespace' => $capability['interface'],
						'instance' => isset($capability['instance']) ? $capability['instance'] : '', 'name' => $property['name']));
				}
			}
			$state['changed'] = array_values(array_intersect($state['changed'], $reportable));
			if (!count($state['changed'])) return;
			$files = glob($directory . '/report-*.json');
			if (count($files) >= 5000) throw new Exception('File des états Alexa pleine (5000 événements)');
			$sequenceFile = $directory . '/sequence.json';
			$sequence = max((int) floor(microtime(true) * 1000000),
				file_exists($sequenceFile) ? (int) json_decode(file_get_contents($sequenceFile), true) + 1 : 0);
			$payload = array('version' => 1, 'reportId' => self::uuid(),
				'source' => hash('sha256', jeedom::getApiKey('ash')), 'endpointId' => (string) $endpoint['endpointId'],
				'group' => (string) $device->getOptions('group'),
				'cause' => self::$voiceDevice === (string) $device->getLink_id() ? 'VOICE_INTERACTION' : 'PHYSICAL_INTERACTION',
				'properties' => $state['properties'], 'changed' => $state['changed']);
			self::write($sequenceFile, $sequence);
			self::write($directory . '/report-' . sprintf('%020d', $sequence) . '.json', $payload);
			$indexed = array();
			foreach ($state['properties'] as $property) $indexed[ash_state::key($property)] = $property;
			// The event is durable before advancing the deduplication cursor.
			self::write($stateFile, $indexed);
		} finally {
			flock($lock, LOCK_UN);
			fclose($lock);
		}
		self::startWorker();
	}

	public static function startWorker() {
		$script = dirname(__FILE__) . '/../php/ashReportState.php';
		system::php(escapeshellarg($script) . ' >> /dev/null 2>&1 &');
	}

	public static function flush() {
		if (!self::allowed()) return;
		$lock = self::lock('worker', false);
		if ($lock === false) return;
		$retryLater = false;
		try {
			$deadline = microtime(true) + 15;
			foreach (glob(self::directory() . '/report-*.json') as $file) {
				if (!self::allowed() || microtime(true) > $deadline) break;
				$payload = json_decode(file_get_contents($file), true);
				$device = is_array($payload) ? ash_devices::byLinkTypeLinkId('eqLogic', $payload['endpointId']) : null;
				if (!is_object($device) || $device->getEnable() != 1 || time() - filemtime($file) > 86400) {
					unlink($file);
					continue;
				}
				$eqLogic = $device->getLink();
				if (!is_object($eqLogic) || $eqLogic->getIsEnable() != 1 ||
					(string) $device->getOptions('group') !== $payload['group']) {
					unlink($file);
					continue;
				}
				// API rotation does not invalidate already queued state changes.
				$payload['source'] = hash('sha256', jeedom::getApiKey('ash'));
				try {
					$request = new com_http(rtrim(config::byKey('service::cloud::url'), '/') . '/service/amazonalexa/reportState');
					$request->setHeader(array('Content-Type: application/json',
						'Autorization: ' . sha512(strtolower(config::byKey('market::username')) . ':' . config::byKey('market::password'))));
					$request->setPost(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
					$request->setLogError(false);
					$response = json_decode($request->exec(15, 1), true);
					if (isset($response['retryable']) && $response['retryable'] === false) {
						log::add('ash', 'error', 'Etat Alexa rejeté par le serveur ; vérifier la configuration de l\'équipement.');
						unlink($file);
						continue;
					}
					if (!isset($response['state']) || $response['state'] !== 'ok') throw new Exception('Envoi différé');
					unlink($file);
				} catch (Exception $e) {
					// Requests can contain credentials: do not log exception messages or HTTP bodies.
					log::add('ash', 'warning', 'Remontée d\'état Alexa différée ; nouvel essai automatique.');
					$retryLater = true;
					break;
				}
			}
		} finally {
			flock($lock, LOCK_UN);
			fclose($lock);
		}
		// Events may have arrived while this worker held the lock. Do not wait for cron.
		if (!$retryLater && self::allowed() && count(glob(self::directory() . '/report-*.json'))) self::startWorker();
	}

	public static function reset($eqLogicId) {
		$lock = self::lock('queue');
		try {
			$path = self::directory() . '/state-' . (int) $eqLogicId . '.json';
			if (file_exists($path)) unlink($path);
		} finally {
			flock($lock, LOCK_UN);
			fclose($lock);
		}
	}

	public static function clear() {
		$lock = self::lock('queue');
		try {
			foreach (array_merge(glob(self::directory() . '/report-*.json'), glob(self::directory() . '/state-*.json')) as $file) unlink($file);
		} finally {
			flock($lock, LOCK_UN);
			fclose($lock);
		}
	}
}

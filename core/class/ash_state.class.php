<?php

/** Shared state conversion for ReportState responses and proactive events. */
class ash_state {

	public static function key($property) {
		return json_encode(array($property['namespace'], isset($property['instance']) ? (string) $property['instance'] : '', $property['name']), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	public static function value($cmd, $directive) {
		if ($cmd->getType() !== 'info') throw new UnexpectedValueException('Une action ne constitue pas un retour d\'état');
		if (isset($directive['_ashEvent']['event_id']) && (string) $directive['_ashEvent']['event_id'] === (string) $cmd->getId()) {
			$value = $directive['_ashEvent']['value'];
		} else {
			$value = $cmd->execCmd();
		}
		if ($value === null || $value === '' || in_array($value, array('unknown', 'unavailable'), true) ||
			($cmd->getSubType() === 'numeric' && !is_numeric($value)) ||
			($cmd->getSubType() === 'binary' && !in_array($value, array(0, 1, '0', '1', false, true, 0.0, 1.0), true))) {
			throw new UnexpectedValueException('Etat Jeedom indisponible');
		}
		return $value;
	}

	public static function sample($cmd, $directive) {
		$date = $cmd->getValueDate();
		if (isset($directive['_ashEvent']['event_id'], $directive['_ashEvent']['datetime']) &&
			(string) $directive['_ashEvent']['event_id'] === (string) $cmd->getId() && $directive['_ashEvent']['datetime'] !== '') {
			$date = $directive['_ashEvent']['datetime'];
		}
		try {
			$sample = new DateTime($date, new DateTimeZone(date_default_timezone_get()));
			$sample->setTimezone(new DateTimeZone('UTC'));
			return $sample->format('Y-m-d\TH:i:s\Z');
		} catch (Exception $e) {
			return gmdate('Y-m-d\TH:i:s\Z');
		}
	}

	public static function inputs($endpoint) {
		$ids = array();
		foreach (isset($endpoint['cookie']) ? $endpoint['cookie'] : array() as $key => $id) {
			if (strpos($key, '_get') !== false && is_numeric($id)) {
				$cmd = cmd::byId($id);
				if (is_object($cmd) && $cmd->getType() === 'info') $ids[(string) $id] = (int) $id;
			}
		}
		return array_values($ids);
	}

	public static function properties($device, $endpoint, $event = null) {
		$supported = ash::getSupportedType();
		$properties = array();
		$directive = array('header' => array('name' => 'ReportState'), 'endpoint' => $endpoint, '_ashEvent' => $event);
		foreach (isset($supported[$device->getType()]['skills']) ? $supported[$device->getType()]['skills'] : array() as $skill) {
			$class = 'ash_' . $skill;
			if (!method_exists($class, 'getState')) continue;
			try {
				$state = $class::getState($device, $directive);
			} catch (Throwable $e) {
				// An invalid/missing sample must not interrupt a Jeedom command event.
				continue;
			}
			foreach (isset($state['properties']) ? $state['properties'] : array() as $property) {
				foreach ($endpoint['capabilities'] as $capability) {
					if ($capability['interface'] !== $property['namespace'] || empty($capability['properties']['retrievable'])) continue;
					if (isset($capability['instance'])) $property['instance'] = (string) $capability['instance'];
					foreach ($capability['properties']['supported'] as $supportedProperty) {
						if ($supportedProperty['name'] !== $property['name']) continue;
						if (in_array($property['name'], array('mode', 'thermostatMode'))) $property['value'] = (string) $property['value'];
						if (in_array($property['name'], array('brightness', 'percentage', 'powerLevel', 'colorTemperatureInKelvin'))) $property['value'] = (int) round($property['value']);
						if ($property['name'] === 'rangeValue') $property['value'] = (float) $property['value'];
						if (isset($property['value']['value']) && is_numeric($property['value']['value'])) $property['value']['value'] = (float) $property['value']['value'];
						$properties[self::key($property)] = $property;
					}
				}
			}
		}
		$eqLogic = $device->getLink();
		if (is_object($eqLogic)) {
			$health = array('namespace' => 'Alexa.EndpointHealth', 'name' => 'connectivity',
				'value' => array('value' => $eqLogic->getStatus('timeout', 0) ? 'UNREACHABLE' : 'OK'),
				'timeOfSample' => gmdate('Y-m-d\TH:i:s\Z'), 'uncertaintyInMilliseconds' => 0);
			$properties[self::key($health)] = $health;
		}
		return array_values($properties);
	}

	public static function delta($properties, $previous) {
		$changed = array();
		foreach ($properties as &$property) {
			$key = self::key($property);
			if (!isset($previous[$key]) || $previous[$key]['value'] !== $property['value']) {
				$changed[] = $key;
			} else {
				// Repeated samples are not new changes; retain the actual change timestamp.
				$property['timeOfSample'] = $previous[$key]['timeOfSample'];
			}
		}
		unset($property);
		return array('properties' => $properties, 'changed' => $changed);
	}
}

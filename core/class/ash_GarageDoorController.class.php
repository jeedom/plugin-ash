<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class ash_GarageDoorController {

	private static $_OPEN = array('GB_OPEN', 'GARAGE_OPEN');
	private static $_CLOSE = array('GB_CLOSE', 'GARAGE_CLOSE');
	private static $_STATE = array('GARAGE_STATE', 'BARRIER_STATE');

	public static function discover($_device, $_eqLogic) {
		$return = array();
		$return['capabilities'] = array();

		$return['capabilities']['Alexa.GarageDoorController'] = array (
			'type' => 'AlexaInterface',
			'interface' => 'Alexa.ModeController',
			'instance' => 'GarageDoor.Position',
			'version' => '3',
			'properties' => array (
				'supported' => array(array('name' => 'mode')),
				'proactivelyReported' => false,
				'retrievable' => false,
			),
			'capabilityResources' => array (
				'friendlyNames' => array(
					array('@type' => 'asset', 'value' => array ('assetId' => 'Alexa.Setting.Opening'))
				),
			),
			'configuration' => array (
				'ordered' => false,
				'supportedModes' => array (
					array(
						'value' => 'Position.Up',
						'modeResources' => array('friendlyNames' => array(array('@type' => 'asset', 'value' => array('assetId' => 'Alexa.Value.Open'))))
					),
					array(
						'value' => 'Position.Down',
						'modeResources' => array('friendlyNames' => array(array('@type' => 'asset', 'value' => array('assetId' => 'Alexa.Value.Close'))))
					)
				)
			),
			'semantics' => array (
				'actionMappings' => array (
					array (
						'@type' => 'ActionsToDirective',
						'actions' => array ('Alexa.Actions.Open', 'Alexa.Actions.Raise'),
						'directive' => array ('name' => 'SetMode', 'payload' => array ('mode' => 'Position.Up'))
					),
					array (
						'@type' => 'ActionsToDirective',
						'actions' => array ('Alexa.Actions.Close', 'Alexa.Actions.Lower'),
						'directive' => array ('name' => 'SetMode', 'payload' => array ('mode' => 'Position.Down'))
					),
				),
				'stateMappings' => array (
					array ('@type' => 'StatesToValue', 'states' => array ('Alexa.States.Open'), 'value' => 'Position.Up'),
					array ('@type' => 'StatesToValue', 'states' => array ('Alexa.States.Closed'), 'value' => 'Position.Down'),
				),
			),
		);

		foreach ($_eqLogic->getCmd() as $cmd) {
			if (in_array($cmd->getGeneric_type(), self::$_OPEN)) {
				$return['cookie']['GarageDoorController_setOpen'] = $cmd->getId();
			}
			if (in_array($cmd->getGeneric_type(), self::$_CLOSE)) {
				$return['cookie']['GarageDoorController_setClose'] = $cmd->getId();
			}
			if (in_array($cmd->getGeneric_type(), self::$_STATE)) {
				$return['capabilities']['Alexa.GarageDoorController']['properties']['retrievable'] = true;
				$return['cookie']['GarageDoorController_getState'] = $cmd->getId();
			}
		}

		if (!isset($return['cookie']['GarageDoorController_setOpen']) || !isset($return['cookie']['GarageDoorController_setClose'])) {
			return array();
		}

		return $return;
	}

	public static function needGenericType(){
		return array(
			__('Ouvrir',__FILE__) => self::$_OPEN,
			__('Fermer',__FILE__) => self::$_CLOSE,
			__('Etat',__FILE__) => self::$_STATE
		);
	}

	public static function exec($_device, $_directive) {
		if ($_directive['header']['name'] == 'SetMode') {
			$mode = $_directive['payload']['mode'];
			if ($mode == 'Position.Up' && isset($_directive['endpoint']['cookie']['GarageDoorController_setOpen'])) {
				$cmd = cmd::byId($_directive['endpoint']['cookie']['GarageDoorController_setOpen']);
				if (is_object($cmd)) $cmd->execCmd();
			} else if ($mode == 'Position.Down' && isset($_directive['endpoint']['cookie']['GarageDoorController_setClose'])) {
				$cmd = cmd::byId($_directive['endpoint']['cookie']['GarageDoorController_setClose']);
				if (is_object($cmd)) $cmd->execCmd();
			}
		}
		return self::getState($_device, $_directive);
	}

	public static function getState($_device, $_directive) {
		$return = array();
		$cmd = null;
		if (isset($_directive['endpoint']['cookie']['GarageDoorController_getState'])) {
			$cmd = cmd::byId($_directive['endpoint']['cookie']['GarageDoorController_getState']);
		}
		if (!is_object($cmd)) return $return;

		$value = $cmd->execCmd();
		if ($cmd->getSubtype() == 'binary' && $cmd->getDisplay('invertBinary') == 1) {
			$value = ($value) ? 0 : 1;
		}

		$modeValue = ($value) ? 'Position.Up' : 'Position.Down';

		$return[] = array(
			'namespace' => 'Alexa.ModeController',
			'instance' => 'GarageDoor.Position',
			'name' => 'mode',
			'value' => $modeValue,
			'timeOfSample' => date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
			'uncertaintyInMilliseconds' => 0,
		);
		return array('properties' => $return);
	}
}

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

class ash_ToggleController {
  
  /*     * *************************Attributs****************************** */
  
  private static $_TOGGLE = array('LIGHT_TOGGLE','GB_TOGGLE');
  private static $_STATE = array('ENERGY_STATE', 'LIGHT_STATE','GARAGE_STATE');
  
  /*     * ***********************Methode static*************************** */
  
  public static function discover($_device,$_eqLogic) {
    $return = array();
    $return = array();
    foreach ($_eqLogic->getCmd() as $cmd) {
      if (in_array($cmd->getGeneric_type(), self::$_TOGGLE)) {
        $return['capabilities']['Alexa.ToggleController'] = array(
          'type' => 'AlexaInterface',
          'interface' => 'Alexa.ToggleController',
          'instance': $cmd->getId(),
          'version' => '3',
          'properties' => array(
            'supported' => array(
              array('name' => 'toggleState'),
            ),
            'proactivelyReported' => false,
            'retrievable' => false,
          ),
          'capabilityResources' => array(
            'friendlyNames' => array(
              array(
                '@type': 'text',
                'value' => array(
                  'text'=> $cmd->getName(),
                  'locale'=> config::byKey('langage')
                ),
              ),
            ),
          ),
        );
        $return['cookie']['ToggleController_setToggle'] = $cmd->getId();
      }
    }
    foreach ($_eqLogic->getCmd() as $cmd) {
      if (in_array($cmd->getGeneric_type(), self::$_STATE)) {
        if(isset($return['capabilities']['Alexa.ToggleController'])){
          $return['capabilities']['Alexa.ToggleController']['properties']['retrievable'] = true;
        }
        $return['cookie']['ToggleController_getState'] = $cmd->getId();
      }
    }
    return $return;
  }
  
  public static function needGenericType(){
    return array(
      __('Toggle',__FILE__) => self::$_TOGGLE,
      __('Etat',__FILE__) => self::$_STATE
    );
  }
  
  public static function exec($_device, $_directive) {
    switch ($_directive['header']['name']) {
      case 'TurnOn':
      if (isset($_directive['endpoint']['cookie']['ToggleController_setToggle'])) {
        $cmd = cmd::byId($_directive['endpoint']['cookie']['ToggleController_setToggle']);
        if (is_object($cmd)) {
          $cmd->execCmd();
        }
      }
      break;
      case 'TurnOff':
      if (isset($_directive['endpoint']['cookie']['ToggleController_setToggle'])) {
        $cmd = cmd::byId($_directive['endpoint']['cookie']['ToggleController_setToggle']);
        if (is_object($cmd)) {
          $cmd->execCmd();
        }
      }
      break;
    }
    return self::getState($_device, $_directive);
  }
  
  public static function getState($_device, $_directive) {
    $return = array();
    $cmd = null;
    if (isset($_directive['endpoint']['cookie']['ToggleController_getState'])) {
      $cmd = cmd::byId($_directive['endpoint']['cookie']['ToggleController_getState']);
      if (is_object($cmd)) {
        $value = $cmd->execCmd();
        if ($cmd->getSubtype() == 'numeric') {
          $return['Alexa.ToggleController'] = array(
            'namespace' => 'Alexa.ToggleController',
            'name' => 'toggleState',
            'value' => ($value) ? 'ON' : 'OFF',
            'timeOfSample' => date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
            'uncertaintyInMilliseconds' => 0,
          );
        } else if ($cmd->getSubtype() == 'binary') {
          $return['Alexa.ToggleController'] = array(
            'namespace' => 'Alexa.ToggleController',
            'name' => 'toggleState',
            'value' => ($value) ? 'ON' : 'OFF',
            'timeOfSample' => date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
            'uncertaintyInMilliseconds' => 0,
          );
        }
      }
    }
    return array('properties' => array_values($return));
  }
  /*     * *********************Méthodes d'instance************************* */
  
  /*     * **********************Getteur Setteur*************************** */
  
}

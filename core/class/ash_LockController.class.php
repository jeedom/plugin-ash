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

class ash_LockController {
  
  /*     * *************************Attributs****************************** */
  
  private static $_LOCK = array('LOCK_CLOSE');
  private static $_UNLOCK = array('LOCK_OPEN');
  private static $_STATE = array('LOCK_STATE');
  
  /*     * ***********************Methode static*************************** */
  
  public static function discover($_device,$_eqLogic) {
    $return = array();
    $return = array();
    foreach ($_eqLogic->getCmd() as $cmd) {
      if (in_array($cmd->getGeneric_type(), self::$_LOCK)) {
        $return['capabilities']['Alexa.LockController'] = array(
          'type' => 'AlexaInterface',
          'interface' => 'Alexa.LockController',
          'version' => '3',
          'properties' => array(
            'supported' => array(
              array('name' => 'lockState'),
            ),
            'proactivelyReported' => false,
            'retrievable' => false,
          ),
        );
        $return['cookie']['LockController_setLock'] = $cmd->getId();
      }
      
      if (in_array($cmd->getGeneric_type(), self::$_UNLOCK)) {
        $return['capabilities']['Alexa.LockController'] = array(
          'type' => 'AlexaInterface',
          'interface' => 'Alexa.LockController',
          'version' => '3',
          'properties' => array(
            'supported' => array(
              array('name' => 'lockState'),
            ),
            'proactivelyReported' => false,
            'retrievable' => false,
          ),
        );
        $return['cookie']['LockController_setUnlock'] = $cmd->getId();
      }
    }
    foreach ($_eqLogic->getCmd() as $cmd) {
      if (in_array($cmd->getGeneric_type(), self::$_STATE)) {
        if(isset($return['capabilities']['Alexa.LockController'])){
          $return['capabilities']['Alexa.LockController']['properties']['retrievable'] = true;
        }
        $return['cookie']['LockController_getState'] = $cmd->getId();
      }
    }
    return $return;
  }
  
  public static function needGenericType(){
    return array(
      __('Verouiller',__FILE__) => self::$_LOCK,
      __('Deverouiller',__FILE__) => self::$_UNLOCK,
      __('Etat',__FILE__) => self::$_STATE
    );
  }
  
  public static function exec($_device, $_directive) {
    switch ($_directive['header']['name']) {
      case 'Lock':
      if (isset($_directive['endpoint']['cookie']['LockController_setLock'])) {
        $cmd = cmd::byId($_directive['endpoint']['cookie']['LockController_setLock']);
        if (is_object($cmd)) {
          $cmd->execCmd();
        }
      }
      break;
      case 'Unlock':
      if (isset($_directive['endpoint']['cookie']['LockController_setUnlock'])) {
        $cmd = cmd::byId($_directive['endpoint']['cookie']['LockController_setUnlock']);
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
    if (isset($_directive['endpoint']['cookie']['LockController_getState'])) {
      $cmd = cmd::byId($_directive['endpoint']['cookie']['LockController_getState']);
      if (is_object($cmd)) {
        $value = $cmd->execCmd();
        if ($cmd->getSubtype() == 'numeric') {
          $return['Alexa.LockController'] = array(
            'namespace' => 'Alexa.LockController',
            'name' => 'lockState',
            'value' => ($value) ? 'LOCKED' : 'UNLOCKED',
            'timeOfSample' => date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
            'uncertaintyInMilliseconds' => 0,
          );
        } else if ($cmd->getSubtype() == 'binary') {
          $return['Alexa.LockController'] = array(
            'namespace' => 'Alexa.LockController',
            'name' => 'lockState',
            'value' => ($value) ? 'LOCKED' : 'UNLOCKED',
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

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

class ash_ModeController {
  
  /*     * *************************Attributs****************************** */
  
  private static $_MODE_STATE = array('MODE_STATE','THERMOSTAT_MODE','ALARM_MODE');
  private static $_MODE_SET_STATE = array('MODE_SET_STATE','THERMOSTAT_SET_MODE','ALARM_SET_MODE');
  
  /*     * ***********************Methode static*************************** */
  
  public static function discover($_device,$_eqLogic) {
    $return = array();
    $return['capabilities'] = array();
    $return['capabilities']['Alexa.ModeController'] = array(
      'type' => 'AlexaInterface',
      'interface' => 'Alexa.ModeController',
      'instance'=> 'mode',
      'version' => '3',
      'properties' => array(
        'supported' => array(
          array('name' => 'mode'),
        ),
        'proactivelyReported' => false,
        'retrievable' => false
      ),
      'capabilityResources'=> array(
        'friendlyNames' => array(
          array('@type'=> 'text',
          'value'=> array(
            'text'=> __('Mode',__FILE__),
            'locale'=> str_replace('_','-',config::byKey('language'))
          )
        )
      )
    ),
    'configuration'=> array(
      'ordered' => false,
      'supportedModes' => array()
    )
  );
  foreach ($_eqLogic->getCmd() as $cmd) {
    if (in_array($cmd->getGeneric_type(), self::$_MODE_SET_STATE)) {
      $return['capabilities']['Alexa.ModeController']['configuration']['supportedModes'][] = array(
        'value'=> $cmd->getId(),
        'modeResources'=> array(
          'friendlyNames'=> array(
            array('@type'=> 'text',
            'value'=> array(
              'text'=> $cmd->getName(),
              'locale'=> str_replace('_','-',config::byKey('language'))
            )
          )
        )
      ));
      $return['cookie']['ModeController_setOn'] = $cmd->getId();
    }
  }
  foreach ($_eqLogic->getCmd() as $cmd) {
    if (in_array($cmd->getGeneric_type(), self::$_MODE_STATE)) {
      if(isset($return['capabilities']['Alexa.ModeController'])){
        $return['capabilities']['Alexa.ModeController']['properties']['retrievable'] = true;
      }
      $return['cookie']['ModeController_getState'] = $cmd->getId();
    }
  }
  if (count($return['capabilities']['Alexa.ModeController']['configuration']['supportedModes']) == 0) {
    return array();
  }
  return $return;
}

public static function needGenericType(){
  return array(
    __('Etat mode',__FILE__) => self::$_MODE_STATE,
    __('Mode',__FILE__) => self::$_MODE_SET_STATE
  );
}

public static function exec($_device, $_directive) {
  switch ($_directive['header']['name']) {
    case 'SetMode':
    $cmd = cmd::byId($_directive['payload']['mode']);
    if(!is_object($cmd)){
      throw new Exception('ENDPOINT_UNREACHABLE');
    }
    $cmd->execCmd();
    break;
  }
  return self::getState($_device, $_directive);
}

public static function getState($_device, $_directive) {
  $return = array();
  $cmd = null;
  if (isset($_directive['endpoint']['cookie']['ModeController_getState'])) {
    $cmd = cmd::byId($_directive['endpoint']['cookie']['ModeController_getState']);
  }
  if (!is_object($cmd)) {
    return $return;
  }
  $value = $cmd->execCmd();
  foreach ($cmd->getEqLogic()->getCmd() as $cmdSet) {
    if (in_array($cmdSet->getGeneric_type(), self::$_MODE_SET_STATE)) {
      if($cmdSet->getName() == $value){
        $value = $cmdSet->getId();
        break;
      }
    }
  }
  
  $return[] = array(
    'namespace'=>'Alexa.ModeController',
    'instance'=>'mode',
    'name'=>'mode',
    'value'=>$value,
    'timeOfSample'=> date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
    'uncertaintyInMilliseconds'=> 0
  );
  return array('properties' => $return);
}

/*     * *********************Méthodes d'instance************************* */

/*     * **********************Getteur Setteur*************************** */

}

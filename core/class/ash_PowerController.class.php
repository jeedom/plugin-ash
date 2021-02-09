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

class ash_PowerController {
  
  /*     * *************************Attributs****************************** */
  
  private static $_ON = array('ENERGY_ON', 'LIGHT_ON');
  private static $_OFF = array('ENERGY_OFF', 'LIGHT_OFF');
  private static $_STATE = array('ENERGY_STATE', 'LIGHT_STATE');
  
  /*     * ***********************Methode static*************************** */
  
  public static function discover($_device,$_eqLogic) {
    $return = array();
    foreach ($_eqLogic->getCmd() as $cmd) {
      if (in_array($cmd->getGeneric_type(), self::$_ON)) {
        $return['capabilities']['Alexa.PowerController'] = array(
          'type' => 'AlexaInterface',
          'interface' => 'Alexa.PowerController',
          'version' => '3',
          'properties' => array(
            'supported' => array(
              array('name' => 'powerState'),
            ),
            'proactivelyReported' => false,
            'retrievable' => false,
          ),
        );
        $return['cookie']['PowerController_setOn'] = $cmd->getId();
      }
      
      if (in_array($cmd->getGeneric_type(), self::$_OFF)) {
        $return['capabilities']['Alexa.PowerController'] = array(
          'type' => 'AlexaInterface',
          'interface' => 'Alexa.PowerController',
          'version' => '3',
          'properties' => array(
            'supported' => array(
              array('name' => 'powerState'),
            ),
            'proactivelyReported' => false,
            'retrievable' => false,
          ),
        );
        $return['cookie']['PowerController_setOff'] = $cmd->getId();
      }
      
      if (in_array($cmd->getGeneric_type(), self::$_BRIGHTNESS)) {
        $return['capabilities']['Alexa.PowerController'] = array(
          'type' => 'AlexaInterface',
          'interface' => 'Alexa.PowerController',
          'version' => '3',
          'properties' => array(
            'supported' => array(
              array('name' => 'powerState'),
            ),
            'proactivelyReported' => false,
            'retrievable' => false,
          ),
        );
      }
    }
    foreach ($_eqLogic->getCmd() as $cmd) {
      if (in_array($cmd->getGeneric_type(), self::$_STATE)) {
        if(isset($return['capabilities']['Alexa.PowerController'])){
          $return['capabilities']['Alexa.PowerController']['properties']['retrievable'] = true;
        }
        $return['cookie']['PowerController_getState'] = $cmd->getId();
      }
      
      if (count($return['capabilities']) == 0) {
        return array('missingGenericType' => array(
          __('On',__FILE__) => self::$_ON,
          __('Off',__FILE__) => self::$_OFF,
          __('Etat',__FILE__) => self::$_STATE
        ));
      }
      $return['capabilities']['AlexaInterface'] = array(
        "type" => "AlexaInterface",
        "interface" => "Alexa",
        "version" => "3",
      );
      return $return;
    }
    
    public static function exec($_device, $_directive) {
      switch ($_directive['header']['name']) {
        case 'TurnOn':
        if (isset($_directive['endpoint']['cookie']['PowerController_setOn'])) {
          $cmd = cmd::byId($_directive['endpoint']['cookie']['PowerController_setOn']);
          if (is_object($cmd)) {
            $cmd->execCmd();
          }
        }
        if (isset($_directive['endpoint']['cookie']['cmd_set_slider'])) {
          $cmd = cmd::byId($_directive['endpoint']['cookie']['cmd_set_slider']);
          if (is_object($cmd)) {
            $cmd->execCmd(array('slider' => $cmd->getConfiguration('maxValue', 100)));
          }
        }
        break;
        case 'TurnOff':
        if (isset($_directive['endpoint']['cookie']['PowerController_setOff'])) {
          $cmd = cmd::byId($_directive['endpoint']['cookie']['PowerController_setOff']);
        } else if (isset($_directive['endpoint']['cookie']['cmd_set_slider'])) {
          $cmd = cmd::byId($_directive['endpoint']['cookie']['cmd_set_slider']);
        }
        if (!is_object($cmd)) {
          throw new Exception('ENDPOINT_UNREACHABLE');
        }
        if ($cmd->getSubtype() == 'other') {
          $cmd->execCmd();
        } else if ($cmd->getSubtype() == 'slider') {
          $cmd->execCmd(array('slider' => 0));
        }
        break;
      }
      return self::getState($_device, $_directive);
    }
    
    public static function getState($_device, $_directive) {
      $return = array();
      $cmd = null;
      if (isset($_directive['endpoint']['cookie']['PowerController_getState'])) {
        $cmd = cmd::byId($_directive['endpoint']['cookie']['PowerController_getState']);
        if (is_object($cmd)) {
          $value = $cmd->execCmd();
          if ($cmd->getSubtype() == 'numeric') {
            $return['Alexa.PowerController'] = array(
              'namespace' => 'Alexa.PowerController',
              'name' => 'powerState',
              'value' => ($value) ? 'ON' : 'OFF',
              'timeOfSample' => date('Y-m-d\TH:i:s\Z', strtotime($cmd->getValueDate())),
              'uncertaintyInMilliseconds' => 0,
            );
          } else if ($cmd->getSubtype() == 'binary') {
            $return['Alexa.PowerController'] = array(
              'namespace' => 'Alexa.PowerController',
              'name' => 'powerState',
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
  
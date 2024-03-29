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

if (!isConnect('admin')) {
  throw new Exception('401 Unauthorized');
}
$eqLogic = eqLogic::byId(init('eqLogic_id'));
if (!is_object($eqLogic)) {
  throw new Exception(__('Eqlogic ID non valide : ', __FILE__) . init('eqLogic_id'));
}
$device = ash_devices::byLinkTypeLinkId('eqLogic', $eqLogic->getId());
if (!is_object($device)) {
  throw new Exception(__('Device non trouvé', __FILE__));
}
if ($device->getType() == '') {
  throw new Exception(__('Aucun type configuré pour ce périphérique', __FILE__));
}

$supportedType = ash::getSupportedType();
echo '<div class="alert alert-info">{{Voici les types génériques utilisés pour le genre d\'équipement choisi. Attention il ne faut pas forcément les avoir tous sur l\'équipement (ou n’en avoir aucun)}}</div>';

foreach ($supportedType[$device->getType()]['skills'] as $skill) {
  $class = 'ash_'.$skill;
  if (!class_exists($class) || !method_exists($class,'needGenericType')) {
    continue;
  }
  $genericType = $class::needGenericType();
  foreach ($genericType as $key => $values) {
    echo '<legend>'.$skill.' : '.$key.'</legend>';
    echo '<ul>';
    foreach ($values as $value) {
      echo '<li>';
      if(isset($JEEDOM_INTERNAL_CONFIG['cmd']['generic_type'][$value])){
        echo $JEEDOM_INTERNAL_CONFIG['cmd']['generic_type'][$value]['name'];
      }else{
        echo $value;
      }
      echo '</li>';
    }
    echo '</ul>';
  }
}
?>
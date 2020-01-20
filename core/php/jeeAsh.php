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
require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";
if (init('apikey') != '') {
	$apikey = init('apikey');
	if(isset($apikey) && strpos($apikey,'-') !== false){
		$apikey = substr($apikey, 0, strpos($apikey, '-'));
	}
	if (!jeedom::apiAccess($apikey, 'ash')) {
		echo __('Vous n\'etes pas autorisé à effectuer cette action. Clef API invalide. Merci de corriger la clef API sur votre page profils du market et d\'attendre 24h avant de réessayer.', __FILE__);
		die();
	} else {
		echo __('Configuration OK', __FILE__);
		die();
	}
}
header('Content-type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$group = '';
if(isset($data['apikey']) && strpos($data['apikey'],'-') !== false){
	$group = explode('-',$data['apikey'])[1];
	$data['apikey'] = explode('-',$data['apikey'])[0];
}
if (!isset($data['apikey']) || !jeedom::apiAccess($data['apikey'], 'ash')) {
	echo json_encode(ash::buildErrorResponse($data, 'INTERNAL_ERROR'));
	die();
}
$plugin = plugin::byId('ash');
if (!$plugin->isActive()) {
	echo json_encode(ash::buildErrorResponse($data, 'INTERNAL_ERROR'));
	die();
}
log::add('ash', 'debug','Received : '. json_encode($data));
if ($data['action'] == 'exec') {
	$result = json_encode(ash::exec($data));
	log::add('ash', 'debug','Reply : '. $result);
	echo $result;
	die();
}else if ($data['action'] == 'sync') {
	$result = json_encode(ash::sync($group));
	log::add('ash', 'debug','Sync : '. $result);
	echo $result;
	die();
}else if ($data['action'] == 'interact') {
	$params = array('plugin' => 'ash', 'reply_cmd' => null);
	echo json_encode(interactQuery::tryToReply(trim($data['data']['message']), $params));
	die();
}
echo json_encode(ash::buildErrorResponse($data, 'INTERNAL_ERROR'));
die();

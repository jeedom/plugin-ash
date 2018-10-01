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
	if (!jeedom::apiAccess(init('apikey'), 'ash')) {
		echo __('Vous n\'etes pas autorisé à effectuer cette action. Clef API invalide. Merci de corriger la clef API sur votre page profils du market et d\'attendre 24h avant de réessayer.', __FILE__);
		die();
	} else {
		echo __('Configuration OK', __FILE__);
		die();
	}
}
header('Content-type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['apikey']) || !jeedom::apiAccess($data['apikey'], 'ash')) {
	echo json_encode(ash::buildErrorResponse($data, 'INTERNAL_ERROR'));
	die();
}
$plugin = plugin::byId('ash');
if (!$plugin->isActive()) {
	echo json_encode(ash::buildErrorResponse($data, 'INTERNAL_ERROR'));
	die();
}
log::add('ash', 'debug', json_encode($data));
if ($data['action'] == 'exec') {
	$result = json_encode(ash::exec($data));
	log::add('ash', 'debug', $result);
	echo $result;
	die();
}
if ($data['action'] == 'interact') {
	$params = array('plugin' => 'ash', 'reply_cmd' => null);
	echo json_encode(interactQuery::tryToReply(trim($data['data']['message']), $params));
	die();
}
echo json_encode(ash::buildErrorResponse($data, 'INTERNAL_ERROR'));
die();

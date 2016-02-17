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
header('Content-type: application/json');
require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";

if (init('apikey') != config::byKey('api') || config::byKey('api') == '') {
	connection::failed();
	echo 'Clef API non valide, vous n\'etes pas autorisé à effectuer cette action (jeeApi)';
	die();
}

$id = init('id');
$eqLogic = doorbird::byId($id);
if (!is_object($eqLogic)) {
	echo json_encode(array('text' => __('Id inconnu : ', __FILE__) . init('id')));
	die();
}

$sensor = init('sensor');
$cmd = doorbirdCmd::byEqLogicIdAndLogicalId($id,$sensor);
if (!is_object($cmd)) {
	echo json_encode(array('text' => __('Commande inconnue : ', __FILE__) . init('sensor')));
	die();
}

log::add('doorbird', 'debug', 'Event : ' . $event);

$value = 1;
if ($sensor == 'dooropen') {
	$value = 0;
}

$cmd->event($value);
$cmd->setConfiguration('value',$value);
$cmd->save();


return true;

?>

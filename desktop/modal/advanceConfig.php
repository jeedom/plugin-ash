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
sendVarToJs('device', utils::o2a($device));
global $JEEDOM_INTERNAL_CONFIG;
?>
<div id="div_alertAdvanceConfigure"></div>
<div id="div_advanceConfigForm">
	<a class="btn btn-success pull-right bt_advanceConfigSaveDevice"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
	<input type="text" class="deviceAttr form-control" data-l1key="id" style="display : none;" />
	<form class="form-horizontal">
		<fieldset>
			<legend>{{Général}}</legend>
			<div class="form-group">
				<label class="col-sm-3 control-label">{{Groupe objet (option nécéssitant un compte market spécifique)}}</label>
				<div class="col-sm-3">
					<input type="number" class="deviceAttr" data-l1key="options" data-l2key="group"></input>
				</div>
			</div>
		</fieldset>
	</form>
	
	<form class="form-horizontal">
		<fieldset>
			<legend>{{Commandes}}</legend>
			<table class="table table-condensed" id="table_advanceConfigAsh">
				<thead>
					<tr>
						<th>{{Nom}}</th>
						<th>{{Type}}</th>
						<th>{{Sous-type}}</th>
						<th>{{Type générique}}</th>
						<th>{{Action}}</th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ($eqLogic->getCmd() as $cmd) {
						echo '<tr>';
						echo '<td>'.$cmd->getHumanName().'</td>';
						echo '<td>'.$cmd->getType().'</td>';
						echo '<td>'.$cmd->getSubType().'</td>';
						if(isset($JEEDOM_INTERNAL_CONFIG['cmd']['generic_type'][$cmd->getGeneric_type()])){
							echo '<td>'.$JEEDOM_INTERNAL_CONFIG['cmd']['generic_type'][$cmd->getGeneric_type()]['name'].'</td>';
						}else{
							echo '<td>'.$cmd->getGeneric_type().'</td>';
						}
						echo '<td><a class="btn btn-default btn-xs pull-right cursor bt_cmdConfiguration" data-id="' . $cmd->getId() . '"><i class="fas fa-cogs"></i></a><td>';
						echo '</tr>';
					}
					?>
				</tbody>
			</table>
		</fieldset>
	</form>
	
	
	<form class="form-horizontal">
		<fieldset>
			<?php
			foreach ($supportedType[$device->getType()]['skills'] as $skill) {
				$class = 'ash_'.$skill;
				if (!class_exists($class) || !method_exists($class,'getHtmlConfiguration')) {
					continue;
				}
				echo '<legend>{{Configuration}} '.$skill.'</legend>';
				$class::getHtmlConfiguration($eqLogic);
			}
			?>
		</fieldset>
	</form>
</div>

<script>
initTooltips($("#div_advanceConfigForm"))
$('#div_advanceConfigForm').setValues(device, '.deviceAttr');
$('.bt_advanceConfigSaveDevice').on('click',function(){
	var device = $('#div_advanceConfigForm').getValues('.deviceAttr')[0];
	$.ajax({
		type: "POST",
		url: "plugins/ash/core/ajax/ash.ajax.php",
		data: {
			action: "saveDevice",
			device : json_encode(device),
		},
		dataType: 'json',
		error: function (request, status, error) {
			handleAjaxError(request, status, error);
		},
		success: function (data) {
			if (data.state != 'ok') {
				$('#div_alertAdvanceConfigure').showAlert({message: data.result, level: 'danger'});
				return;
			}
			$('#div_alertAdvanceConfigure').showAlert({message: '{{Sauvegarde réussi, pensez à relancer une synchronisation}}', level: 'success'});
		},
	});
});

$('#table_advanceConfigAsh .bt_cmdConfiguration').off('click').on('click', function() {
	$('#md_modal2').dialog({title: "{{Configuration de la commande}}"}).load('index.php?v=d&modal=cmd.configure&cmd_id=' + $(this).attr('data-id')).dialog('open')
})

</script>

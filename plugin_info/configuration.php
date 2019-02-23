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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
	include_file('desktop', '404', 'php');
	die();
}
?>
<form class="form-horizontal">
	<fieldset>
		<?php
		if(strpos(network::getNetworkAccess('external'),'https://') == -1){
			echo '<div class="alert alert-danger">{{Attention votre connexion externe ne semble pas etre en https, ce plugin nécessite ABSOLUMENT une connexion https. Si vous ne savez pas comment faire vous pouvez souscrire à un service pack power pour utiliser le service de DNS Jeedom}}</div>';
		}
		?>
		<div class="alert alert-info">
			{{Attention il faut attendre 24h suite à l'envoi de la configuration pour que ca soit pris en compte.}}
		</div>
		<div class="form-group ashmode jeedom">
			<label class="col-lg-3 control-label">{{Envoyer configuration au market}}</label>
			<div class="col-lg-2">
				<a class="btn btn-default" id="bt_sendConfigToMarket"><i class="fa fa-paper-plane" aria-hidden="true"></i> {{Envoyer}}</a>
			</div>
		</div>
	</fieldset>
</form>
<form class="form-horizontal">
	<fieldset>
		<legend>{{TTS}}</legend>
		<div class="form-group">
			<label class="col-lg-3 control-label">{{Nom d'utilisateur Amazon}}</label>
			<div class="col-lg-3">
				<input class="configKey form-control" data-l1key="amazon::login" />
			</div>
		</div>
		<div class="form-group">
			<label class="col-lg-3 control-label">{{Mot de passe Amazon}}</label>
			<div class="col-lg-3">
				<input class="configKey form-control" data-l1key="amazon::password" />
			</div>
		</div>
		<div class="form-group">
			<label class="col-lg-3 control-label">{{Langue}}</label>
			<div class="col-lg-3">
				<select class="configKey form-control" data-l1key="amazon::language" >
					<option value="fr">{{Français}}</option>
					<option value="de">{{Allemand}}</option>
				</select>
			</div>
		</div>
		<div class="form-group">
			<label class="col-lg-3 control-label">{{Nom des echos/dots (séparé par des ;}}</label>
			<div class="col-lg-9">
				<input class="configKey form-control" data-l1key="amazon::deviceList" />
			</div>
		</div>
	</fieldset>
</form>
<script type="text/javascript">
	function ash_postSaveConfiguration(){
		$.ajax({
			type: "POST",
			url: "plugins/ash/core/ajax/ash.ajax.php",
			data: {
				action: "createEqLogicFromDeviceList",
			},
			dataType: 'json',
			error: function (request, status, error) {
				handleAjaxError(request, status, error);
			},
			success: function (data) {
				if (data.state != 'ok') {
					$('#div_alert').showAlert({message: data.result, level: 'danger'});
					return;
				}
			}
		});
	}
	
	$('#bt_sendConfigToMarket').on('click', function () {
		$.ajax({
			type: "POST",
			url: "plugins/ash/core/ajax/ash.ajax.php",
			data: {
				action: "sendConfig",
			},
			dataType: 'json',
			error: function (request, status, error) {
				handleAjaxError(request, status, error);
			},
			success: function (data) {
				if (data.state != 'ok') {
					$('#div_alert').showAlert({message: data.result, level: 'danger'});
					return;
				}
				$('#div_alert').showAlert({message: '{{Configuration envoyée avec succès. Merci d\'attendre 24h pour que la demande soit prise en compte}}', level: 'success'});
			}
		});
	});
</script>

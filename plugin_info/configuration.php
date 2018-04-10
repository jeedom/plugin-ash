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
		<legend>{{Serveur Google smarthome}}</legend>
		<div class="form-group">
			<label class="col-lg-3 control-label">{{Mode}}</label>
			<div class="col-lg-2">
				<select class="form-control configKey" data-l1key="mode">
					<option value="jeedom">{{Service Jeedom Cloud}}</option>
					<option value="internal">{{Service Interne}}</option>
				</select>
			</div>
		</div>
		<div class="form-group ashmode jeedom">
			<label class="col-lg-3 control-label">{{Envoyer configuration au market}}</label>
			<div class="col-lg-2">
				<a class="btn btn-default" id="bt_sendConfigToMarket"><i class="fa fa-paper-plane" aria-hidden="true"></i> {{Envoyer}}</a>
			</div>
		</div>
		<div class="form-group ashmode internal">
			<label class="col-lg-3 control-label">{{DNS ou IP du serveur}}</label>
			<div class="col-lg-2">
				<input class="configKey form-control" data-l1key="ashs::ip" />
			</div>
		</div>
	</fieldset>
</form>
<div class='row ashmode internal'>
	<div class='col-md-6'>
		<form class="form-horizontal">
			<fieldset>
				<legend>{{Configuration général}}</legend>
				<div class="form-group">
					<label class="col-lg-6 control-label">{{Clef maitre}}</label>
					<div class="col-lg-6">
						<input class="configKey form-control" data-l1key="ashs::masterkey" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-6 control-label">{{Clef API Google}}</label>
					<div class="col-lg-6">
						<input class="configKey form-control" data-l1key="ashs::googleapikey" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-6 control-label">{{Cient ID}}</label>
					<div class="col-lg-6">
						<input class="configKey form-control" data-l1key="ashs::clientId" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-6 control-label">{{Cient Secret}}</label>
					<div class="col-lg-6">
						<input class="configKey form-control" data-l1key="ashs::clientSecret" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-6 control-label">{{Port}}</label>
					<div class="col-lg-6">
						<input class="configKey form-control" data-l1key="ashs::port" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-6 control-label">{{Timeout}}</label>
					<div class="col-lg-6">
						<input class="configKey form-control" data-l1key="ashs::timeout" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-6 control-label">{{URL}}</label>
					<div class="col-lg-6">
						<input class="configKey form-control" data-l1key="ashs::url" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-6 control-label">{{Configuration}}</label>
					<div class="col-lg-6">
						<a class="btn btn-success" id="bt_viewConf"><i class="fa fa-eye" aria-hidden="true"></i> {{Voir}}</a>
					</div>
				</div>
			</fieldset>
		</form>
	</div>
	<div class='col-md-6'>
		<form class="form-horizontal">
			<fieldset>
				<legend>{{Utilisateur}}</legend>
				<div class="form-group">
					<label class="col-lg-6 control-label">{{ID utilisateur}}</label>
					<div class="col-lg-6">
						<input class="configKey form-control" data-l1key="ashs::userid" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-6 control-label">{{Nom d'utilisateur}}</label>
					<div class="col-lg-6">
						<input class="configKey form-control" data-l1key="ashs::username" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-6 control-label">{{Mot de passe}}</label>
					<div class="col-lg-6">
						<input class="configKey form-control" data-l1key="ashs::password" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-6 control-label">{{Token}}</label>
					<div class="col-lg-6">
						<input class="configKey form-control" data-l1key="ashs::token" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-6 control-label">{{Mode d'accès Jeedom}}</label>
					<div class="col-lg-6">
						<select class="form-control configKey" data-l1key="ashs::jeedomnetwork">
							<option value="internal">{{Interne}}</option>
							<option value="external">{{Externe}}</option>
						</select>
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-6 control-label">{{Configuration}}</label>
					<div class="col-lg-6">
						<a class="btn btn-success" id="bt_viewUserConf"><i class="fa fa-eye" aria-hidden="true"></i> {{Voir}}</a>
					</div>
				</div>
			</fieldset>
		</form>
	</div>
</div>

<script type="text/javascript">
	$('.configKey[data-l1key=mode]').on('change',function(){
		$('.ashmode').hide();
		$('.ashmode.'+$(this).value()).show();
	});
	$('#bt_viewConf').on('click',function(){
		$('#md_modal2').dialog({title: "{{Configuration général}}"});
		$('#md_modal2').load('index.php?v=d&plugin=ash&modal=showConf').dialog('open');
	});

	$('#bt_viewUserConf').on('click',function(){
		$('#md_modal2').dialog({title: "{{Configuration utilisateur}}"});
		$('#md_modal2').load('index.php?v=d&plugin=ash&modal=showUserConf').dialog('open');
	});

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
            success: function (data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
            	$('#div_alert').showAlert({message: data.result, level: 'danger'});
            	return;
            }
            $('#div_alert').showAlert({message: '{{Configuration envoyée avec succès}}', level: 'success'});
        }
    });
	});
</script>
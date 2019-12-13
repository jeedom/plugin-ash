<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
?>
<br/>
<a class="btn btn-success pull-right" id="bt_saveConfiguration"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
<a class="btn btn-default pull-right" id="bt_displayDevice"><i class="fas fa-eye"></i> {{Voir la configuration}}</a>
<ul class="nav nav-tabs" role="tablist">
	<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
	<li role="presentation"><a href="#scenariotab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Scénario}}</a></li>
</ul>

<div class="tab-content" id="div_configuration" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
	<div role="tabpanel" class="tab-pane active" id="eqlogictab">
		<br/>
		<table class="table table-bordered tablesorter">
			<thead>
				<tr>
					<th>{{Equipement}}</th>
					<th>{{Plugin}}</th>
					<th data-sorter="false" data-filter="false">{{Options}}</th>
					<th>{{Status}}</th>
					<th ddata-sorter="select-text">{{Type}}</th>
					<th data-sorter="inputs">{{Pseudo}}</th>
					<th data-sorter="false" data-filter="false">{{Action}}</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach (eqLogic::all() as $eqLogic) {
					echo '<tr class="device" data-link_id="' . $eqLogic->getId() . '" data-link_type="eqLogic">';
					echo '<td>' . $eqLogic->getHumanName(true) . '</td>';
					echo '<td>' . $eqLogic->getEqType_name() . '</td>';
					echo '<td>';
					echo '<input style="display:none;" class="deviceAttr" data-l1key="id" />';
					echo '<input style="display:none;" class="deviceAttr" data-l1key="link_type" value="eqLogic" />';
					echo '<input style="display:none;" class="deviceAttr" data-l1key="link_id" value="' . $eqLogic->getId() . '" />';
					echo '<input type="checkbox" class="deviceAttr" data-l1key="enable" /> <label>{{Transmettre}}</label><br/>';
					echo '</td>';
					echo '<td>';
					echo '<span class="deviceAttr label" data-l1key="options" data-l2key="configState" style="font-size:1em;"></span>';
					echo '</td>';
					echo '<td>';
					echo '<select class="deviceAttr form-control input-sm" data-l1key="type">';
					echo '<option value="">{{Aucun}}</option>';
					foreach (ash::$_supportedType as $key => $value) {
						if ($key == 'SCENE_TRIGGER') {
							continue;
						}
						echo '<option value="' . $key . '">{{' . $value['name'] . '}}</option>';
					}
					echo '<select>';
					echo '</td>';
					echo '<td>';
					echo '<input class="deviceAttr form-control" data-l1key="options" data-l2key="pseudo" />';
					echo '</td>';
					echo '<td style="width:150px;">';
					echo ' <a class="btn btn-success btn-sm bt_advanceConfigureEqLogic" data-id="' . $eqLogic->getId() . '" ><i class="fas fa-cog"></i></a>';
					echo ' <a class="btn btn-default btn-sm bt_configureEqLogic" data-id="' . $eqLogic->getId() . '"><i class="fa fa-cogs"></i></a>';
					echo ' <a class="btn btn-default btn-sm" href="' . $eqLogic->getLinkToConfiguration() . '"  target="_blank"><i class="fa fa-external-link"></i></a>';
					echo '</td>';
					echo '</tr>';
				}
				?>
			</tbody>
		</table>
	</div>
	
	
	<div role="tabpanel" class="tab-pane" id="scenariotab">
		<a class="btn btn-success pull-right btn-xs" id="bt_addScene" style="margin-top:5px;"><i class="fa fa-plus"></i> {{Ajouter scène}}</a>
		<br/><br/>
		<div id="div_scenes"></div>
	</div>
</div>


<?php include_file('desktop', 'ash', 'js', 'ash');?>


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

actionOptions = []

$('.nav-tabs li a').on('click',function(){
  setTimeout(function(){
    taAutosize();
  }, 50);
})

$('.bt_configureEqLogic').on('click',function(){
  $('#md_modal').dialog({title: "{{Configuration de l'équipement}}"})
  .load('index.php?v=d&modal=eqLogic.configure&eqLogic_id=' + $(this).attr('data-id')).dialog('open');
});

$('#div_configuration').off('click','.bt_needGenericType').on('click','.bt_needGenericType',function(){
  $('#md_modal').dialog({title: "{{Information type générique}}"})
  .load('index.php?v=d&plugin=ash&modal=showNeedGenericType&eqLogic_id=' + $(this).closest('tr').attr('data-link_id')).dialog('open');
});

$('.bt_advanceConfigureEqLogic').on('click',function(){
  $('#md_modal').dialog({title: "{{Configuration avancée}}"})
  .load('index.php?v=d&plugin=ash&modal=advanceConfig&eqLogic_id=' + $(this).attr('data-id')).dialog('open');
});

$('#bt_saveConfiguration').on('click',function(){
  var devices = $('#div_configuration .device[data-link_type=eqLogic]').getValues('.deviceAttr');
  $('#div_scenes .scene').each(function () {
    var scene = $(this).getValues('.sceneAttr')[0];
    scene.options.inAction = $(this).find('.inAction').getValues('.expressionAttr');
    scene.options.outAction = $(this).find('.outAction').getValues('.expressionAttr');
    devices.push(scene);
  });
  
  $.ajax({
    type: "POST",
    url: "plugins/ash/core/ajax/ash.ajax.php",
    data: {
      action: "saveDevices",
      devices : json_encode(devices),
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
      sendDevices();
    },
  });
});

function sendDevices(){
  $.ajax({
    type: "POST",
    url: "plugins/ash/core/ajax/ash.ajax.php",
    data: {
      action: "sendDevices",
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
      $('#div_alert').showAlert({message: '{{Synchronisation réussie. Pour voir le status des equipements à jour, merci de rafraichir la page (F5)}}', level: 'success'});
    },
  });
}

function loadData(){
  $("#div_scenes").empty();
  $.ajax({
    type: "POST",
    url: "plugins/ash/core/ajax/ash.ajax.php",
    data: {
      action: "allDevices"
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
      var nbDeviceOk = 0
      var nbDeviceNok = 0
      for(var i in data.result){
        if(data.result[i]['link_type'] == 'scene'){
          addScene(data.result[i]);
          continue;
        }
        var el = $('.device[data-link_id='+data.result[i]['link_id']+'][data-link_type='+data.result[i]['link_type']+']');
        if(!el){
          continue;
        }
        el.setValues(data.result[i], '.deviceAttr');
        if(data.result[i].options && data.result[i].options.configState){
          if(data.result[i].options.configState == 'OK'){
            el.find('.deviceAttr[data-l2key=configState]').removeClass('label-danger bt_needGenericType cursor').addClass('label-success');
          }else{
            el.find('.deviceAttr[data-l2key=configState]').removeClass('label-success').addClass('label-danger bt_needGenericType cursor');
          }
        }
      }
      $('#eqlogictab .tablesorter').trigger('update')
      jeedom.cmd.displayActionsOption({
        params : actionOptions,
        async : false,
        error: function (error) {
          $('#div_alert').showAlert({message: error.message, level: 'danger'});
        },
        success : function(data){
          for(var i in data){
            $('#'+data[i].id).append(data[i].html.html);
          }
          taAutosize();
        }
      });
    },
  });
}

loadData();

$('#bt_displayDevice').on('click',function(){
  $('#md_modal').dialog({title: "{{Configuration péripheriques}}"});
  $('#md_modal').load('index.php?v=d&plugin=ash&modal=showDevicesConf').dialog('open');
});


$('#bt_addScene').on('click', function () {
  bootbox.prompt("{{Nom de la scene ?}}", function (result) {
    if (result !== null && result != '') {
      addScene({options : {name: result}});
    }
  });
});

$('body').delegate('.rename', 'click', function () {
  var el = $(this);
  bootbox.prompt("{{Nouveau nom ?}}", function (result) {
    if (result !== null && result != '') {
      var previousName = el.text();
      el.text(result);
      el.closest('.panel.panel-default').find('span.name').text(result);
    }
  });
});

$("body").delegate(".listCmdAction", 'click', function () {
  var type = $(this).attr('data-type');
  var el = $(this).closest('.' + type).find('.expressionAttr[data-l1key=cmd]');
  jeedom.cmd.getSelectModal({cmd: {type: 'action'}}, function (result) {
    el.value(result.human);
    jeedom.cmd.displayActionOption(el.value(), '', function (html) {
      el.closest('.' + type).find('.actionOptions').html(html);
      taAutosize();
    });
  });
});

$("body").delegate(".listAction", 'click', function () {
  var type = $(this).attr('data-type');
  var el = $(this).closest('.' + type).find('.expressionAttr[data-l1key=cmd]');
  jeedom.getSelectActionModal({}, function (result) {
    el.value(result.human);
    jeedom.cmd.displayActionOption(el.value(), '', function (html) {
      el.closest('.' + type).find('.actionOptions').html(html);
      taAutosize();
    });
  });
});

$("body").delegate('.bt_removeAction', 'click', function () {
  var type = $(this).attr('data-type');
  $(this).closest('.' + type).remove();
});

$("#div_scenes").delegate('.bt_addInAction', 'click', function () {
  addAction({}, 'inAction', '{{Action d\'entrée}}', $(this).closest('.scene'));
});

$("#div_scenes").delegate('.bt_addOutAction', 'click', function () {
  addAction({}, 'outAction', '{{Action de sortie}}', $(this).closest('.scene'));
});

$('body').delegate('.cmdAction.expressionAttr[data-l1key=cmd]', 'focusout', function (event) {
  var type = $(this).attr('data-type')
  var expression = $(this).closest('.' + type).getValues('.expressionAttr');
  var el = $(this);
  jeedom.cmd.displayActionOption($(this).value(), init(expression[0].options), function (html) {
    el.closest('.' + type).find('.actionOptions').html(html);
    taAutosize();
  })
});

$("#div_scenes").delegate('.bt_removeScene', 'click', function () {
  $(this).closest('.scene').remove();
});


function addAction(_action, _type, _name, _el) {
  if (!isset(_action)) {
    _action = {};
  }
  if (!isset(_action.options)) {
    _action.options = {};
  }
  var input = '';
  var button = 'btn-default';
  if (_type == 'outAction') {
    input = 'has-error';
    button = 'btn-danger';
  }
  if (_type == 'inAction') {
    input = 'has-success';
    button = 'btn-success';
  }
  var div = '<div class="' + _type + '">';
  div += '<div class="form-group ">';
  div += '<label class="col-sm-2 control-label">' + _name + '</label>';
  div += '<div class="col-sm-1  ' + input + '">';
  div += '<input type="checkbox" class="expressionAttr" data-l1key="options" data-l2key="enable" checked title="{{Décocher pour desactiver l\'action}}" />';
  div += '<input type="checkbox" class="expressionAttr" data-l1key="options" data-l2key="background" title="{{Cocher pour que la commande s\'éxecute en parrallele des autres actions}}" />';
  div += '</div>';
  div += '<div class="col-sm-4 ' + input + '">';
  div += '<div class="input-group">';
  div += '<span class="input-group-btn">';
  div += '<a class="btn btn-default bt_removeAction btn-sm" data-type="' + _type + '"><i class="fa fa-minus-circle"></i></a>';
  div += '</span>';
  div += '<input class="expressionAttr form-control input-sm cmdAction" data-l1key="cmd" data-type="' + _type + '" />';
  div += '<span class="input-group-btn">';
  div += '<a class="btn ' + button + ' btn-sm listAction" data-type="' + _type + '" title="{{Sélectionner un mot-clé}}"><i class="fa fa-tasks"></i></a>';
  div += '<a class="btn ' + button + ' btn-sm listCmdAction" data-type="' + _type + '"><i class="fa fa-list-alt"></i></a>';
  div += '</span>';
  div += '</div>';
  div += '</div>';
  var actionOption_id = uniqId();
  div += '<div class="col-sm-5 actionOptions" id="'+actionOption_id+'">';
  div += '</div>';
  div += '</div>';
  if (isset(_el)) {
    _el.find('.div_' + _type).append(div);
    _el.find('.' + _type + ':last').setValues(_action, '.expressionAttr');
  } else {
    $('#div_' + _type).append(div);
    $('#div_' + _type + ' .' + _type + ':last').setValues(_action, '.expressionAttr');
  }
  actionOptions.push({
    expression : init(_action.cmd, ''),
    options : _action.options,
    id : actionOption_id
  });
}

function addScene(_scene) {
  if (init(_scene.options.name) == '') {
    return;
  }
  var random = Math.floor((Math.random() * 1000000) + 1);
  var div = '<div class="scene panel panel-default">';
  div += '<div class="panel-heading">';
  div += '<h4 class="panel-title">';
  div += '<a data-toggle="collapse" data-parent="#div_scenes" href="#collapse' + random + '">';
  div += '<span class="name">' + _scene.options.name + '</span>';
  div += '</a>';
  div += '</h4>';
  div += '</div>';
  div += '<div id="collapse' + random + '" class="panel-collapse collapse in">';
  div += '<div class="panel-body">';
  div += '<div class="well">';
  div += '<form class="form-horizontal" role="form">';
  div += '<div class="form-group">';
  div += '<label class="col-sm-2 control-label">{{Nom du scene}}</label>';
  div += '<div class="col-sm-2">';
  div += '<input class="sceneAttr" data-l1key="id" style="display:none;" />';
  div += '<input class="sceneAttr" data-l1key="enable" style="display:none;" value="1" />';
  div += '<input class="sceneAttr" data-l1key="link_type" style="display:none;" value="scene" />';
  div += '<input class="sceneAttr" data-l1key="type" style="display:none;" value="SCENE_TRIGGER" />';
  div += '<span class="sceneAttr label label-info rename cursor" data-l1key="options" data-l2key="name" style="font-size : 1em;" ></span>';
  div += '</div>';
  div += '<div class="col-sm-8">';
  div += '<div class="btn-group pull-right" role="group">';
  div += '<a class="btn btn-sm bt_removeScene btn-primary"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>';
  div += '<a class="btn btn-sm bt_addInAction btn-success"><i class="fa fa-plus-circle"></i> {{Action d\'entrée}}</a>';
  div += '<a class="btn btn-danger btn-sm bt_addOutAction"><i class="fa fa-plus-circle"></i> {{Action de sortie}}</a>';
  div += '</div>';
  div += '</div>';
  div += '</div>';
  div += '<hr/>';
  div += '<div class="div_inAction"></div>';
  div += '<hr/>';
  div += '<div class="div_outAction"></div>';
  div += '</form>';
  div += '</div>';
  div += '</div>';
  div += '</div>';
  div += '</div>';
  
  $('#div_scenes').append(div);
  $('#div_scenes .scene:last').setValues(_scene, '.sceneAttr');
  if (is_array(_scene.options.inAction)) {
    for (var i in _scene.options.inAction) {
      addAction(_scene.options.inAction[i], 'inAction', '{{Action d\'entrée}}', $('#div_scenes .scene:last'));
    }
  } else {
    if ($.trim(_scene.options.inAction) != '') {
      addAction(_scene.options.inAction[i], 'inAction', '{{Action d\'entrée}}', $('#div_scenes .scene:last'));
    }
  }
  
  if (is_array(_scene.options.outAction)) {
    for (var i in _scene.options.outAction) {
      addAction(_scene.options.outAction[i], 'outAction', '{{Action de sortie}}', $('#div_scenes .scene:last'));
    }
  } else {
    if ($.trim(_scene.options.outAction) != '') {
      addAction(_scene.options.outAction, 'outAction', '{{Action de sortie}}', $('#div_scenes .scene:last'));
    }
  }
  $('.collapse').collapse();
  $("#div_scenes .scene:last .div_inAction").sortable({axis: "y", cursor: "move", items: ".inAction", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
  $("#div_scenes .scene:last .div_outAction").sortable({axis: "y", cursor: "move", items: ".outAction", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
}

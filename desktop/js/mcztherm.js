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

// Code pour le tab consignes 
$("#div_p1").sortable({axis: "y", cursor: "move", items: ".p1", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
$("#div_p2").sortable({axis: "y", cursor: "move", items: ".p2", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
$("#div_p3").sortable({axis: "y", cursor: "move", items: ".p3", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
$("#div_p4").sortable({axis: "y", cursor: "move", items: ".p4", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
$("#div_p0").sortable({axis: "y", cursor: "move", items: ".p0", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});

$('.addAction').off('click').on('click', function () {
  addAction({}, $(this).attr('data-type'));
});

$("body").off('click','.bt_removeAction').on('click','.bt_removeAction',function () {
  var type = $(this).attr('data-type');
  $(this).closest('.' + type).remove();
});

$("body").off('click','.listCmdAction').on('click','.listCmdAction', function () {
  var type = $(this).attr('data-type');
  var el = $(this).closest('.' + type).find('.expressionAttr[data-l1key=cmd]');
  jeedom.cmd.getSelectModal({cmd: {type: 'action'}}, function (result) {
    el.value(result.human);
    jeedom.cmd.displayActionOption(el.value(), '', function (html) {
      el.closest('.' + type).find('.actionOptions').html(html);
    });
  });
});

function addAction(_action, _type) {
  var div = '<div class="' + _type + '">';
  div += '<div class="form-group ">';
  div += '<label class="col-sm-1 control-label">Action</label>';
  div += '<div class="col-sm-4">';
  div += '<div class="input-group">';
  div += '<span class="input-group-btn">';
  div += '<a class="btn btn-default bt_removeAction roundedLeft" data-type="' + _type + '"><i class="fas fa-minus-circle"></i></a>';
  div += '</span>';
  div += '<input class="expressionAttr form-control cmdAction" data-l1key="cmd" data-type="' + _type + '" />';
  div += '<span class="input-group-btn">';
  div += '<a class="btn btn-default listCmdAction roundedRight" data-type="' + _type + '"><i class="fas fa-list-alt"></i></a>';
  div += '</span>';
  div += '</div>';
  div += '</div>';
  div += '<div class="col-sm-7 actionOptions">';
  div += jeedom.cmd.displayActionOption(init(_action.cmd, ''), _action.options);
  div += '</div>';
  div += '</div>';
  $('#div_' + _type).append(div);
  $('#div_' + _type + ' .' + _type + '').last().setValues(_action, '.expressionAttr');
}

function saveEqLogic(_eqLogic) {
  if (!isset(_eqLogic.configuration)) {
    _eqLogic.configuration = {};
  }
  _eqLogic.configuration.P1 = $('#div_p1 .p1').getValues('.expressionAttr');
  _eqLogic.configuration.P2 = $('#div_p2 .p2').getValues('.expressionAttr');
  _eqLogic.configuration.P3 = $('#div_p3 .p3').getValues('.expressionAttr');
  _eqLogic.configuration.P4 = $('#div_p4 .p4').getValues('.expressionAttr');
  _eqLogic.configuration.P0 = $('#div_p0 .p0').getValues('.expressionAttr');
  return _eqLogic;
}

function printEqLogic(_eqLogic) {
  $('#div_p1').empty();
  $('#div_p2').empty();
  $('#div_p3').empty();
  $('#div_p4').empty();
  $('#div_p0').empty();
  if (isset(_eqLogic.configuration)) {
    if (isset(_eqLogic.configuration.P1)) {
      for (var i in _eqLogic.configuration.P1) {
        addAction(_eqLogic.configuration.P1[i], 'p1');
      }
    }
    if (isset(_eqLogic.configuration.P2)) {
      for (var i in _eqLogic.configuration.P2) {
        addAction(_eqLogic.configuration.P2[i], 'p2');
      }
    }
    if (isset(_eqLogic.configuration.P3)) {
      for (var i in _eqLogic.configuration.P3) {
        addAction(_eqLogic.configuration.P3[i], 'p3');
      }
    }
    if (isset(_eqLogic.configuration.P4)) {
      for (var i in _eqLogic.configuration.P4) {
        addAction(_eqLogic.configuration.P4[i], 'p4');
      }
    }
    if (isset(_eqLogic.configuration.P0)) {
      for (var i in _eqLogic.configuration.P0) {
        addAction(_eqLogic.configuration.P0[i], 'p0');
      }
    }
  }
}

// Code général pour command tab
$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
/* Fonction pour l'ajout de commande, appellé automatiquement par le plugin */
function addCmdToTable(_cmd) {
	if (!isset(_cmd)) {
		var _cmd = {configuration: {}};
	}
	if (!isset(_cmd.configuration)) {
		_cmd.configuration = {};
	}
	if (init(_cmd.logicalId) == 'refresh') {
		var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
		tr += '<td>';
			tr += '<span class="cmdAttr" data-l1key="id" style="display:none;"></span>';
			tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;" placeholder="{{Nom}}">';
		tr += '</td>';
		tr += '<td></td>';
		tr += '<td></td>';
		tr += '<td></td>';
		tr += '<td>';
		tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
		tr += '</td>';
		tr += '</tr>';
	} else {
		var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';

		tr += '<td>';
		tr += '<span class="cmdAttr" data-l1key="id" style="display:none;"></span>';
		tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;" placeholder="{{Nom}}">';
		if (init(_cmd.type) == 'action') {
			tr += 'Sur : <select class="cmdAttr form-control input-sm" data-l1key="value" style="display : none;margin-top : 5px;width:calc(100% - 50px);display:inline" title="{{La valeur de la commande vaut par défaut la commande}}">';
			tr += '<option value="">Aucune</option>';
			tr += '</select>';
		}
		tr += '</td>';

		tr += '<td>';
		tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
		tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
		tr += '</td>';

		tr += '<td>';
		if (init(_cmd.type) == 'action' && init(_cmd.subType) == 'other') {
			tr += '<select class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="updateCmdId" style=margin-top:5px;" title="Commande d\'information à mettre à jour">';
			tr += '<option value="">Aucune</option>';
			tr += '</select>';
			tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="updateCmdToValue" placeholder="Valeur de l\'information" style="margin-top:5px;">';
		} else if (init(_cmd.type) == 'action' && init(_cmd.subType) == 'slider') {
			tr += '<select class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="infoName" style="margin-top:5px;" title="Commande d\'information à mettre à jour">';
			tr += '<option value="">Aucune</option>';
			tr += '</select>';
			tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="value" placeholder="Valeur de l\'information" style="margin-top:5px;">';
		}
		tr += '</td>';

		tr += '<td>';
		tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
		tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
		tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label></span> ';
		tr += '<br>';
		tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;display:inline-block;">';
		tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;display:inline-block;">';
		tr += '<input class="cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;display:inline-block;margin-right:5px;">';
		tr += '</td>';

		tr += '<td>';
		if (is_numeric(_cmd.id)) {
			tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fa fa-cogs"></i></a> ';
			tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
		}
		tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i>';
		tr += '</td>';

		tr += '</tr>';
	}

	$('#table_cmd tbody').append(tr);
	var tr = $('#table_cmd tbody tr:last');
	jeedom.eqLogic.builSelectCmd({
		id: $('.eqLogicAttr[data-l1key=id]').value(),
		filter: {type: 'info'},
		error: function (error) {
			$('#div_alert').showAlert({message: error.message, level: 'danger'});
		},
		success: function (result) {
			tr.find('.cmdAttr[data-l1key=value]').append(result);
			tr.find('.cmdAttr[data-l1key=configuration][data-l2key=updateCmdId]').append(result);
			tr.find('.cmdAttr[data-l1key=configuration][data-l2key=infoName]').append(result);
			tr.setValues(_cmd, '.cmdAttr');
			jeedom.cmd.changeType(tr, init(_cmd.subType));
		}
	});
}

// Fonction pour ajouter la sélection des commandes
$("#bt_selectSondeInt").on('click', function () {
	jeedom.cmd.getSelectModal({cmd: {type: 'info', subType: 'numeric'}}, function (result) {
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=SondeInt]').value(result.human);
	});
});
$("#bt_selectSondeExt").on('click', function () {
	jeedom.cmd.getSelectModal({cmd: {type: 'info', subType: 'numeric'}}, function (result) {
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=SondeExt]').value(result.human);
	});
});
$("#bt_selectCmdMessage").on('click', function () {
	jeedom.cmd.getSelectModal({cmd: {type: 'action', subType: 'message'}}, function (result) {
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=CmdMessage]').value(result.human);
	});
});
$("#bt_selectCommandeMode").on('click', function () {
	jeedom.cmd.getSelectModal({cmd: {type: 'info', subType: 'string'}}, function (result) {
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=CommandeMode]').value(result.human);
	});
});
$("#bt_selectCmdTempMcz").on('click', function () {
	jeedom.cmd.getSelectModal({cmd: {type: 'action', subType: 'other'}}, function (result) {
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=CmdTempMcz]').value(result.human);
	});
});
$("#bt_selectCmdMajDateHeure").on('click', function () {
	jeedom.cmd.getSelectModal({cmd: {type: 'action', subType: 'other'}}, function (result) {
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=CmdMajDateHeure]').value(result.human);
	});
});
$("#bt_selectInfoEtatPoele").on('click', function () {
	jeedom.cmd.getSelectModal({cmd: {type: 'info', subType: 'string'}}, function (result) {
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=InfoEtatPoele]').value(result.human);
	});
});
$("#bt_selectInfoProfilPoele").on('click', function () {
	jeedom.cmd.getSelectModal({cmd: {type: 'info', subType: 'string'}}, function (result) {
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=InfoProfilPoele]').value(result.human);
	});
});
$("#bt_selectInfoPuissancePoele").on('click', function () {
	jeedom.cmd.getSelectModal({cmd: {type: 'info', subType: 'string'}}, function (result) {
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=InfoPuissancePoele]').value(result.human);
	});
});
$("#bt_selectInfoFan1Poele").on('click', function () {
	jeedom.cmd.getSelectModal({cmd: {type: 'info', subType: 'string'}}, function (result) {
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=InfoFan1Poele]').value(result.human);
	});
});
$("#bt_selectInfoFan2Poele").on('click', function () {
	jeedom.cmd.getSelectModal({cmd: {type: 'info', subType: 'string'}}, function (result) {
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=InfoFan2Poele]').value(result.human);
	});
});
$("#bt_selectInfoFan3Poele").on('click', function () {
	jeedom.cmd.getSelectModal({cmd: {type: 'info', subType: 'string'}}, function (result) {
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=InfoFan3Poele]').value(result.human);
	});
});
$("#bt_selectInfoEcoPoele").on('click', function () {
	jeedom.cmd.getSelectModal({cmd: {type: 'info', subType: 'string'}}, function (result) {
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=InfoEcoPoele]').value(result.human);
	});
});
$("#bt_selectInfoTAmbiantePoele").on('click', function () {
	jeedom.cmd.getSelectModal({cmd: {type: 'info', subType: 'string'}}, function (result) {
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=InfoTAmbiantePoele]').value(result.human);
	});
});
$("#bt_selectInfoTConsignePoele").on('click', function () {
	jeedom.cmd.getSelectModal({cmd: {type: 'info', subType: 'string'}}, function (result) {
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=InfoTConsignePoele]').value(result.human);
	});
});
$("#bt_selectCmdOnPoele").on('click', function () {
	jeedom.cmd.getSelectModal({cmd: {type: 'action', subType: 'other'}}, function (result) {
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=CmdOnPoele]').value(result.human);
	});
});
$("#bt_selectCmdOffPoele").on('click', function () {
	jeedom.cmd.getSelectModal({cmd: {type: 'action', subType: 'other'}}, function (result) {
		$('.eqLogicAttr[data-l1key=configuration][data-l2key=CmdOffPoele]').value(result.human);
	});
});


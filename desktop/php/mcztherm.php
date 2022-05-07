<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('mcztherm');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fas fa-cog"></i>{{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoPrimary" data-action="add" style="color:#006579;">
				<i class="fas fa-plus-circle"></i>
				<br>
				<span>{{Ajouter}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br>
				<span>{{Configuration}}</span>
			</div>
		</div>
		<legend><i class="fas fa-table"></i>{{Mes thermostat}}</legend>
		<input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
		<div class="eqLogicThumbnailContainer">
			<?php
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
				echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
				echo '<br>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '</div>';
			}
			?>
		</div>
	</div>

	<div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex">
			<span class="input-group-btn">
				<a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure">
					<i class="fa fa-cogs"></i>
					{{Configuration avancée}}
				</a>
				<a class="btn btn-default btn-sm eqLogicAction" data-action="copy">
					<i class="fas fa-copy"></i>
					{{Dupliquer}}
				</a>
				<a class="btn btn-sm btn-success eqLogicAction" data-action="save">
					<i class="fas fa-check-circle"></i>
					{{Sauvegarder}}
				</a>
				<a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove">
					<i class="fas fa-minus-circle"></i>
					{{Supprimer}}
				</a>
			</span>
		</div>
		<!--- Definition des tab --->
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i>{{Général}}</a></li>
			<li role="presentation"><a href="#consignestab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i>{{Consignes}}</a></li>
			<li role="presentation"><a href="#infostab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i>{{Infos du poêle}}</a></li>
			<li role="presentation"><a href="#cmdstab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i>{{Commandes du poêle}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i>{{Commandes}}</a></li>
		</ul>

		<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
			<!--- tab: eqlogictab --->
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<br/>
				<form class="form-horizontal">
					<fieldset>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
							<div class="col-sm-3">
								<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
								<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}"/>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label" >{{Objet parent}}</label>
							<div class="col-sm-3">
								<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
								<option value="">{{Aucun}}</option>
									<?php
									$options = '';
									foreach ((jeeObject::buildTree(null, false)) as $object) {
										$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
									}
									echo $options;
									?>
								</select>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Catégorie}}</label>
							<div class="col-sm-9">
								<?php
								foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
									echo '<label class="checkbox-inline">';
									echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
									echo '</label>';
								}
								?>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label"></label>
							<div class="col-sm-9">
								<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
								<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
							</div>
						</div>
					</fieldset>
				</form>

				<!--- Modes --->
				<legend><i class="fas"></i> {{Modes}}</legend>
				<form class="form-horizontal">
					<fieldset>
						<div class="form-group">
								<a class="btn btn-success pull-right addMode col-xs-2" data-type="mode"><i class="fas fa-plus-circle"></i> {{Ajouter un mode}}</a>
						</div>

						<div class="form-group">
							<fieldset>
								<div id="div_mode" class="col-xs-10 col-xs-offset-2">
								</div>
							</fieldset>
						</div>
					</fieldset>
				</form>
				<br>


<!---  new code replace this 
				<form class="form-horizontal">
					<fieldset>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Jour}}</label>
							<div class="col-sm-1" style="width:20px">
								<label class="checkbox-inline" style="vertical-align:top;"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="CheckModeJour"/></label>
							</div>
							<div class="col-sm-2">
								<div class="input-group">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="TempModeJour" placeholder="{{Temperature}}"/>
								</div>
							</div>
							<div class="col-sm-2">
								<div class="input-group">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="HeureModeJourStart" placeholder="{{Heure de début (hh:mm)}}"/>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Nuit}}</label>
							<div class="col-sm-1" style="width:20px">
								<label class="checkbox-inline" style="vertical-align:top;"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="CheckModeNuit"/></label>
							</div>
							<div class="col-sm-2">
								<div class="input-group">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="TempModeNuit" placeholder="{{Temperature}}"/>
								</div>
							</div>
							<div class="col-sm-2">
								<div class="input-group">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="HeureModeNuitStart" placeholder="{{Heure de début (hh:mm)}}"/>
								</div>
							</div>
						</div>
					</fieldset>
				</form>
--->

				<!--- Sondes --->
				<legend><i class="fas"></i> {{Sondes}}</legend>
				<form class="form-horizontal">
					<fieldset>
		 				<div class="form-group">
							<label class="col-sm-3 control-label">{{Sonde intérieure}}</label>
							<div class="col-sm-4">
								<div class="input-group">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="SondeInt" placeholder="{{Sonde intérieure}}"/>
									<span class="input-group-btn">
										<a class="btn btn-default" id="bt_selectSondeInt" title="{{Sélectionner une commande}}">
											<i class="fa fa-list-alt"></i>
										</a>
									</span>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Sonde extérieure}}(-)</label>
							<div class="col-sm-4">
								<div class="input-group">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="SondeExt" placeholder="{{Sonde extérieure}}"/>
									<span class="input-group-btn">
										<a class="btn btn-default" id="bt_selectSondeExt" title="{{Sélectionner une commande}}">
											<i class="fa fa-list-alt"></i>
										</a>
									</span>
								</div>
							</div>
						</div>
					</fieldset>
				</form>

				<!--- Divers --->
				<legend><i class="fas"></i> {{Divers}}</legend>
				<form class="form-horizontal">
					<fieldset>
		 				<div class="form-group">
							<label class="col-sm-3 control-label">{{Commande message}}</label>
							<div class="col-sm-4">
								<div class="input-group">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="CmdMessage" placeholder="{{Envoi de message}}"/>
									<span class="input-group-btn">
										<a class="btn btn-default" id="bt_selectCmdMessage" title="{{Sélectionner une commande}}">
											<i class="fa fa-list-alt"></i>
										</a>
									</span>
								</div>
							</div>
						</div>
					</fieldset>
				</form>
			</div>

			<!--- tab: consignestab --->
			<div role="tabpanel" class="tab-pane" id="consignestab">
				<br/>
				<form class="form-horizontal">
					<fieldset>
						<legend><i class="fa" aria-hidden="true"></i> {{Consignes}}</legend>
						<div class="form-group">
							<label class="col-sm-2 control-label">{{Arrêt (Puissance 0)}}</label>
							<label class="col-sm-1 control-label">{{Seuil(°C)}}</label>
							<div class="col-sm-1">
								<div class="input-group">
									<input class="eqLogicAttr form-control" style="width:75px" data-l1key="configuration" data-l2key="DeltaTempArret" placeholder="{{Delta température}}"/>
								</div>
							</div>
							<label class="col-sm-1 control-label">{{Hystérèse (°C)}}</label>
							<div class="col-sm-1">
								<div class="input-group">
									<input class="eqLogicAttr form-control" style="width:75px" data-l1key="configuration" data-l2key="Hysterese" placeholder="{{Hystérèse (°C)'}}"/>
								</div>
							</div>
							<label class="col-sm-1 control-label">{{Arrêt Min (min)}}</label>
							<div class="col-sm-1">
								<div class="input-group">
									<input class="eqLogicAttr form-control" style="width:75px" data-l1key="configuration" data-l2key="DureeArretMin" placeholder="{{Durée arrêt minimum (min)'}}"/>
								</div>
							</div>
							<a class="btn btn-default btn-xs pull-right addAction" data-type="p0" style="position: relative; top : 5px;"><i class="fas fa-plus-circle"></i> {{Ajouter une action}}</a>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-3">
								<br/>
								<div id="div_p0">
								</div>
							</div>
						</div>
						<br/>					
						<div class="form-group">
							<label class="col-sm-2 control-label">{{Puissance 1}}</label>
							<label class="col-sm-1 control-label">{{Seuil(°C)}}</label>
							<div class="col-sm-2">
								<div class="input-group">
									<input class="eqLogicAttr form-control" style="width:75px" data-l1key="configuration" data-l2key="DeltaTempP1" placeholder="{{Delta temperature}}"/>
								</div>
							</div>
							<a class="btn btn-default btn-xs pull-right addAction" data-type="p1" style="position: relative; top : 5px;"><i class="fas fa-plus-circle"></i> {{Ajouter une action}}</a>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-3">
								<br/>
								<div id="div_p1">
								</div>
							</div>
						</div>	
						<br/>
						<div class="form-group">
							<label class="col-sm-2 control-label">{{Puissance 2}}</label>
							<label class="col-sm-1 control-label">{{Seuil(°C)}}</label>
							<div class="col-sm-2">
								<div class="input-group">
									<input class="eqLogicAttr form-control" style="width:75px" data-l1key="configuration" data-l2key="DeltaTempP2" placeholder="{{Delta temperature}}"/>
								</div>
							</div>
							<a class="btn btn-default btn-xs pull-right addAction" data-type="p2" style="position: relative; top : 5px;"><i class="fas fa-plus-circle"></i> {{Ajouter une action}}</a>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-3">
								<br/>
								<div id="div_p2">
								</div>
							</div>
						</div>						
						<br/>
						<div class="form-group">
							<label class="col-sm-2 control-label">{{Puissance 3}}</label>
							<label class="col-sm-1 control-label">{{Seuil(°C)}}</label>
							<div class="col-sm-2">
								<div class="input-group">
									<input class="eqLogicAttr form-control" style="width:75px" data-l1key="configuration" data-l2key="DeltaTempP3" placeholder="{{Delta temperature}}"/>
								</div>
							</div>
							<a class="btn btn-default btn-xs pull-right addAction" data-type="p3" style="position: relative; top : 5px;"><i class="fas fa-plus-circle"></i> {{Ajouter une action}}</a>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-3">
								<br/>
								<div id="div_p3">
								</div>
							</div>
						</div>						
						<br/>
						<div class="form-group">
							<label class="col-sm-2 control-label">{{Puissance 4}}</label>
							<label class="col-sm-1 control-label">{{Seuil(°C)}}</label>
							<div class="col-sm-2">
								<div class="input-group">
									<input class="eqLogicAttr form-control" style="width:75px" data-l1key="configuration" data-l2key="DeltaTempP4" placeholder="{{Delta temperature}}"/>
								</div>
							</div>
							<a class="btn btn-default btn-xs pull-right addAction" data-type="p4" style="position: relative; top : 5px;"><i class="fas fa-plus-circle"></i> {{Ajouter une action}}</a>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-3">
								<br/>
								<div id="div_p4">
								</div>
							</div>
						</div>						
						<br/><br/>
					</fieldset>
				</form>
			</div>

			<!--- tab: infostab --->
			<div role="tabpanel" class="tab-pane" id="infostab">
				<br/>
				<form class="form-horizontal">
					<fieldset>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Etat du poêle}}</label>
							<div class="col-sm-4">
								<div class="input-group">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="InfoEtatPoele" placeholder="{{Commande info indiquant l'état}}"/>
									<span class="input-group-btn">
										<a class="btn btn-default" id="bt_selectInfoEtatPoele" title="{{Sélectionner une commande}}">
											<i class="fa fa-list-alt"></i>
										</a>
									</span>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Exclusion sur un état particulier}}</label>
							<div class="col-sm-1" style="width:20px">
								<label class="checkbox-inline" style="vertical-align:top;"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="CheckEtat"/></label>
							</div>
							<div class="col-sm-2">
								<div class="input-group">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="ExclEtat" placeholder="{{Valeur}}"/>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Valeur état off}}</label>
							<div class="col-sm-2">
								<div class="input-group">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="EtatOff" placeholder="{{Valeur}}"/>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Attente sur un état (liste)}}</label>
							<div class="col-sm-1" style="width:20px">
								<label class="checkbox-inline" style="vertical-align:top;"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="CheckWaitEtat"/></label>
							</div>
							<div class="col-sm-4">
								<div class="input-group" style="width:100%">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="WaitEtat" placeholder="{{liste délimitée par ;}}"/>
								</div>
							</div>
						</div>
<!---
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Profil actif}}</label>
							<div class="col-sm-4">
								<div class="input-group">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="InfoProfilPoele" placeholder="{{Commande info indiquant le profil actif}}"/>
									<span class="input-group-btn">
										<a class="btn btn-default" id="bt_selectInfoProfilPoele" title="{{Sélectionner une commande}}">
											<i class="fa fa-list-alt"></i>
										</a>
									</span>
								</div>
							</div>
						</div>
--->
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Puissance active}}</label>
							<div class="col-sm-4">
								<div class="input-group">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="InfoPuissancePoele" placeholder="{{Commande info indiquant la puissance active}}"/>
									<span class="input-group-btn">
										<a class="btn btn-default" id="bt_selectInfoPuissancePoele" title="{{Sélectionner une commande}}">
											<i class="fa fa-list-alt"></i>
										</a>
									</span>
								</div>
							</div>
						</div>
<!---
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Niveau ventilateur ambiance}}</label>
							<div class="col-sm-4">
								<div class="input-group">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="InfoFan1Poele" placeholder="{{Commande info indiquant le niveau du ventilateur ambiance}}"/>
									<span class="input-group-btn">
										<a class="btn btn-default" id="bt_selectInfoFan1Poele" title="{{Sélectionner une commande}}">
											<i class="fa fa-list-alt"></i>
										</a>
									</span>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Niveau ventilateur canalisé 1}}</label>
							<div class="col-sm-4">
								<div class="input-group">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="InfoFan2Poele" placeholder="{{Commande info indiquant le niveau du ventilateur canalisé 1}}"/>
									<span class="input-group-btn">
										<a class="btn btn-default" id="bt_selectInfoFan2Poele" title="{{Sélectionner une commande}}">
											<i class="fa fa-list-alt"></i>
										</a>
									</span>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Niveau ventilateur canalisé 2}}</label>
							<div class="col-sm-4">
								<div class="input-group">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="InfoFan3Poele" placeholder="{{Commande info indiquant le niveau du ventilateur canalisé 2}}"/>
									<span class="input-group-btn">
										<a class="btn btn-default" id="bt_selectInfoFan3Poele" title="{{Sélectionner une commande}}">
											<i class="fa fa-list-alt"></i>
										</a>
									</span>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Etat mode ECO}}</label>
							<div class="col-sm-4">
								<div class="input-group">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="InfoEcoPoele" placeholder="{{Commande info indiquant l'état du mode ECO}}"/>
									<span class="input-group-btn">
										<a class="btn btn-default" id="bt_selectInfoEcoPoele" title="{{Sélectionner une commande}}">
											<i class="fa fa-list-alt"></i>
										</a>
									</span>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Température ambiante}}</label>
							<div class="col-sm-4">
								<div class="input-group">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="InfoTAmbiantePoele" placeholder="{{Commande info indiquant la température ambiante}}"/>
									<span class="input-group-btn">
										<a class="btn btn-default" id="bt_selectInfoTAmbiantePoele" title="{{Sélectionner une commande}}">
											<i class="fa fa-list-alt"></i>
										</a>
									</span>
								</div>
							</div>
						</div>
--->
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Consigne de température}}</label>
							<div class="col-sm-4">
								<div class="input-group">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="InfoTConsignePoele" placeholder="{{Commande info indiquant la consigne de température}}"/>
									<span class="input-group-btn">
										<a class="btn btn-default" id="bt_selectInfoTConsignePoele" title="{{Sélectionner une commande}}">
											<i class="fa fa-list-alt"></i>
										</a>
									</span>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Niveau pellets}}</label>
							<div class="col-sm-4">
								<div class="input-group">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="InfoNiveauPellets" placeholder="{{Commande info indiquant le niveau de pellets}}"/>
									<span class="input-group-btn">
										<a class="btn btn-default" id="bt_selectInfoNiveauPellets" title="{{Sélectionner une commande}}">
											<i class="fa fa-list-alt"></i>
										</a>
									</span>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Valeur Niveau insuffisant}}</label>
							<div class="col-sm-2">
								<div class="input-group">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="NiveauPelletsNOK" placeholder="{{Valeur}}"/>
								</div>
							</div>
						</div>
					</fieldset>
				</form>
			</div>

			<!--- tab: cmdstab --->
			<div role="tabpanel" class="tab-pane" id="cmdstab">
				<br/>
				<form class="form-horizontal">
					<fieldset>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Allumer le poêle}}</label>
							<div class="col-sm-4">
								<div class="input-group">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="CmdOnPoele" placeholder="{{Commande pour allumer le poêle}}"/>
									<span class="input-group-btn">
										<a class="btn btn-default" id="bt_selectCmdOnPoele" title="{{Sélectionner une commande}}">
											<i class="fa fa-list-alt"></i>
										</a>
									</span>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Eteindre le poêle}}</label>
							<div class="col-sm-4">
								<div class="input-group">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="CmdOffPoele" placeholder="{{Commande pour éteindre le poêle}}"/>
									<span class="input-group-btn">
										<a class="btn btn-default" id="bt_selectCmdOffPoele" title="{{Sélectionner une commande}}">
											<i class="fa fa-list-alt"></i>
										</a>
									</span>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Commande de température du poêle}}</label>
							<div class="col-sm-4">
								<div class="input-group">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="CmdTempMcz" placeholder="{{Commande pour indiquer la température}}"/>
									<span class="input-group-btn">
										<a class="btn btn-default" id="bt_selectCmdTempMcz" title="{{Sélectionner une commande}}">
											<i class="fa fa-list-alt"></i>
										</a>
									</span>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Mise à jour Date/Heure}}</label>
							<div class="col-sm-4">
								<div class="input-group">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="CmdMajDateHeure" placeholder="{{Commande pour mettre à jour la date et l\'heure'}}"/>
									<span class="input-group-btn">
										<a class="btn btn-default" id="bt_selectCmdMajDateHeure" title="{{Sélectionner une commande}}">
											<i class="fa fa-list-alt"></i>
										</a>
									</span>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Heure de mise à jour}}</label>
							<div class="col-sm-2">
								<div class="input-group">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="HeureMaj" placeholder="{{HH:mm}}"/>
								</div>
							</div>
						</div>
					</fieldset>
				</form>
			</div>

			<!--- tab: commandtab --->
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<a class="btn btn-success btn-sm cmdAction pull-right" data-action="add" style="margin-top:5px;">
					<i class="fa fa-plus-circle"></i>
					{{Commandes}}
				</a>
				<table id="table_cmd" class="table table-bordered table-condensed">
					<thead>
						<tr>
							<th>{{Nom}}</th>
							<th>{{Type}}</th>
							<th>{{Paramètres}}</th>
							<th>{{Options}}</th>
							<th>{{Action}}</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<?php include_file('desktop', 'mcztherm', 'js', 'mcztherm');?>
<?php include_file('core', 'plugin.template', 'js');?>

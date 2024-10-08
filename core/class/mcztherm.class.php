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

// code date:  06/10/2024


/* * ***************************Includes********************************* */
require_once __DIR__ . '/../../../../core/php/core.inc.php';

class mcztherm extends eqLogic {
	/* **************************Attributs****************************** */


	/* ***********************Methode static*************************** */

	private static function processMajDateHeure($mcztherm) {
		$ts_curtime = strtotime(date('Y-m-d H:i:00'));
		if (($mcztherm->getConfiguration('HeureMaj') != '') &&  ($mcztherm->getConfiguration('CmdMajDateHeure') != ''))  {
			$heure = $mcztherm->getConfiguration('HeureMaj');
			$heure = str_replace(':','',$heure);   //enleve les : dans l'heure
			$heure = substr('000'.$heure,-4);   //Traitement des 0 sur les heures < 10:00
			$ts_jour = strtotime(date('Y-m-d') . ' ' . $heure);
			if ($ts_curtime == $ts_jour) {
				$DateHeure = date('dmYHi');
				if (is_object($mcztherm)) {
					$cmdhb = $mcztherm->getCmd(null,'ordrepoele');
					if (is_object($cmdhb)) {
						$cmdhbname = $cmdhb->getHumanName();	
						cmd::byString('#'.$cmdhbname.'#')->event('9001,' . $DateHeure);
						$cmdmcz = $mcztherm->getConfiguration('CmdMajDateHeure');
						if (isset($cmdmcz) && $cmdmcz != '') {
							cmd::byId(str_replace('#','',$cmdmcz))->execCmd();
						}
						log::add('mcztherm','debug','- Heure poele mise à jour');
					} else {
						log::add('mcztherm','warning', '  ordrepoele n\'est pas un objet');
					}
				} 	
			}
		} 
	}


	private static function processTempCommand($mcztherm, $temperature) {
		// Récupère la Temp Consigne du poele
		$tempConsignePoele = mcztherm::getCmdInfoValue($mcztherm, "InfoTConsignePoele");
		if ($tempConsignePoele == $temperature) { 
			log::add('mcztherm','debug','- Temperature ' . $temperature . ' déjà demandée');
			return; 
		}
		//$mcztherm = eqLogic::byId($equipement);
		if (is_object($mcztherm)) {
			$cmdhb = $mcztherm->getCmd(null,'t_demandee');
			$cmdhbname = $cmdhb->getHumanName();	
			cmd::byString('#'.$cmdhbname.'#')->event($temperature*2);
		
			$cmdmcz = $mcztherm->getConfiguration('CmdTempMcz');
			if (isset($cmdmcz) && $cmdmcz != '') {
				cmd::byId(str_replace('#','',$cmdmcz))->execCmd();

				// To write log info
				$name = cmd::byId(str_replace('#','',$cmdmcz))->getHumanName();
				log::add('mcztherm','info',' - Execute cmd - '. $name .' Set Temperature to '.$temperature);
			} else {
				log::add('mcztherm','warning', ' Cmd "'. $cmdmcz . '" n\'est pas valable ou pas definie');	
			}
		} else {
			log::add('mcztherm','warning', '  Equipement n\'est pas un objet');
		}
	}

	private static function processConsigneCommands($mcztherm, $consigne) {
		// Récupère le profil actif du poêle et le compare à la currentConsigne.
		$puissanceActiveVal = intval(substr($mcztherm::getCmdInfoValue($mcztherm, 'InfoPuissancePoele'), -1));
		$currentConsigneVal = intval(substr($mcztherm->getCache('currentConsigne', ''), -1));
		log::add('mcztherm','debug',' - P active: ' . $puissanceActiveVal . '   cur Consigne: ' . $currentConsigneVal);
		if ($puissanceActiveVal == $currentConsigneVal) {
			// Teste si la même consigne. Si,oui ne fait rien et quitte.
			if ($mcztherm->getCache('currentConsigne', '') == $consigne) { 
				log::add('mcztherm','debug','- Consigne ' . $consigne . ' déjà activée');
				return; 
			}
		}
		if ($consigne =='P0') {
			log::add('mcztherm','info',' - Passage en consigne: Arret (' . $consigne . ')');
		} else {
			log::add('mcztherm','info',' - Passage en consigne: ' . $consigne);
		}
		$mcztherm->setCache('currentConsigne', $consigne);
		if ($consigne == 'P0') {
			$mcztherm->setCache('lastOff', date('Y-m-d H:i:00'));  // Sauve date/heure de l'arrêt
		}

		$currentconsigneval = intval(substr($consigne, -1 ));
		if (is_object($mcztherm)) {
			$cmdhb = $mcztherm->getCmd(null,'currentconsigne');
			if (is_object($cmdhb)) {
				$cmdhbname = $cmdhb->getHumanName();	
				cmd::byString('#'.$cmdhbname.'#')->event($currentconsigneval);
			} else {
				log::add('mcztherm','debug','*** currentconsigne not an object');
			}
		}


		foreach ($mcztherm->getConfiguration($consigne) as $action) {
			try {
				$cmd = cmd::byId(str_replace('#', '', $action['cmd']));
				log::add('mcztherm','info',' - Action:  ' . cmd::byId(str_replace('#','',$action['cmd']))->getHumanName());
				if (is_object($cmd) && $mcztherm->getId() == $cmd->getEqLogic_id()) {
					continue;
				}
				$options = array();
				if (isset($action['options'])) {
					$options = $action['options'];
					foreach ($options as $key => $value) {
						$options[$key] = str_replace('#slider#', $consigne, $value);
					}
				}
				scenarioExpression::createAndExec('action', $action['cmd'], $options);
			} catch (Exception $e) {
				log::add('mcztherm', 'error', $mcztherm->getHumanName() . __(' : Erreur lors de l\'éxecution de ', __FILE__) . $action['cmd'] . __('. Détails : ', __FILE__) . getMessage());
			}
		}
	}

	private static function processOn($mcztherm) {
		// Teste si le poele est Eteint
		// Si oui, allume le poele.
		if ($mcztherm->getConfiguration('InfoEtatPoele') != '') {
			$cmdEtat = cmd::byId(str_replace('#','',$mcztherm->getConfiguration('InfoEtatPoele')))->execCmd();
			if ($cmdEtat == $mcztherm->getConfiguration('EtatOff')) {
				// allumage du poele.  Envoi commande ON
				$cmdOn = $mcztherm->getConfiguration('CmdOnPoele');
				try {
					$cmd = cmd::byId(str_replace('#', '', $cmdOn));
					log::add('mcztherm','info','  - Action:  ' . cmd::byId(str_replace('#', '', $cmdOn))->getHumanName() );
					if (is_object($cmd) && $mcztherm->getId() != $cmd->getEqLogic_id()) {
						$options = array();
						if (isset($action['options'])) {
							$options = $action['options'];
							foreach ($options as $key => $value) {
								$options[$key] = str_replace('#slider#', $consigne, $value);
							}
						}
						scenarioExpression::createAndExec('action', $cmdOn, $options);
					}
				} catch (Exception $e) {
					log::add('mcztherm', 'error', $mcztherm->getHumanName() . __(' : Erreur lors de l\'éxecution de ', __FILE__) . $cmdOn . __('. Détails : ', __FILE__) . getMessage());
				}
			}
		}
	}



	private static function getCmdInfoValue($mcztherm, $cfgname) {
		// mcztherm: eqlogic
		// cfgname:  nom de la varaiable de configuration
		// retourne la valeur de la commande spécifiée par la variable de configuration
		$cmd = cmd::byId(str_replace('#','',$mcztherm->getConfiguration($cfgname)));
		$value = $cmd->execCmd();
		return ($value);
	}



	private static function testExclusionEtat($mcztherm) {
		// mcztherm: eqlogic
		// return value:  0: pas d'exclusion    1: exclusion
		// ----
		// Examine si mode d'exclusion de l'état de la commande info 
		$returnval = 0;
		$checkbox = $mcztherm->getConfiguration('CheckEtat');
		//log::add('mcztherm','debug','- checkbox Etat: '. $checkbox );
		if ($checkbox == 1) {
			if ($mcztherm->getConfiguration('InfoEtatPoele') != '') {
				$cmdEtat = cmd::byId(str_replace('#','',$mcztherm->getConfiguration('InfoEtatPoele')))->execCmd();
				if ($cmdEtat == $mcztherm->getConfiguration('ExclEtat')) {
					log::add('mcztherm','debug','- Exclusion sur état --> ' . $cmdEtat . ' ==> Stop.  Nothing to do.');
					$returnval = 1;
				} else {
					log::add('mcztherm','debug','- Pas d\'exclusion sur état --> ' . $cmdEtat . ' ==> Continue ....');
				}
			}
		}
		return($returnval);
	}


	private static function testMode($mcztherm) {
		// test mode to apply at currrent time
		// mcztherm:  eqLogic
		// return:  mode array
		$returnval = array();
		$heures = array();
		$curtime = date('Hi');
		log::add('mcztherm','debug', 'curtime => ' . $curtime);
		// retrieve all time and set in array
		$modes = $mcztherm->getConfiguration('mode');
		foreach($modes as $mode) {
			if ($mode['active'] == 1) {
				$heure = $mode['time'];
				$heure = str_replace(':','',$heure);   //enleve les : dans l'heure
				$heure = substr('000'.$heure,-4);   //Traitement des 0 sur les heures < 10:00
				$heures[] = $heure;
			}
		}
		// add 2359 & 0000 to array
		//$heures[] = "2359";
		$heures[] = "0000";

		// Sort array lowest to biggest
		rsort($heures);
		log::add('mcztherm','debug', 'heures (desc) => ' . json_encode($heures));

		foreach($heures as $heure) {
			if ($curtime >= $heure) {
				$result = $heure;
				break;
			}
		}
		if ($result == '0000') {
			$curtime = '2359';
			foreach($heures as $heure) {
				if ($curtime >= $heure) {
					$result = $heure;
					break;
				}
			}
		}
		log::add('mcztherm','debug', 'result => ' . $result);

		// Retrieve mode corresponding to result
		foreach($modes as $mode) {
			if ($mode['active'] == 1) {
				$heure = $mode['time'];
				$heure = str_replace(':','',$heure);   //enleve les : dans l'heure
				$heure = substr('000'.$heure,-4);   //Traitement des 0 sur les heures < 10:00
				if ($heure == $result) {
					$returnval = $mode;
				}
			}
		}

		log::add('mcztherm','debug', 'Actual mode => ' . json_encode($returnval));
		return($returnval);
	}


	private static function testChangeMode($mcztherm) {
		// Test if we have to change the mode
		// mcztherm:   eqLogic
		// return: mode array  or 1 if not a time to change mode
		//$returnval = array();
		$curtime = date('Hi');
		$modes = $mcztherm->getConfiguration('mode');
		foreach($modes as $mode) {
			if ($mode['active'] == 1) {
				$heure = $mode['time'];
				$heure = str_replace(':','',$heure);   //enleve les : dans l'heure
				$heure = substr('000'.$heure,-4);   //Traitement des 0 sur les heures < 10:00
				if ($curtime == $heure) {
					$returnval = $mode;
					break;
				}
			}
		}
		if (!isset($returnval)) {
			$returnval = 1;
		} else {
			log::add('mcztherm','debug', 'Change mode => ' . json_encode($returnval));
		}

		return($returnval);
	}

	private static function testActivationThermostat($mcztherm) {
		// Teste si le thermostat doit être activé en fonction de la commande activation et de l'heure.
		// Return:   0: rien à faire   1: thermostat activé, commande activation remise à off
		$returnval = 0;
		//log::add('mcztherm', 'debug', '  - Via testActivation Thermostat');
		$cmd = $mcztherm->getCmd(null,'activation');  // Retourne la commande "activation" si elle existe
		if (is_object($cmd)) { //Si la commande existe
			$cmdValue = $cmd->execCmd();
			if ($cmdValue == 1) { // activation est On
				//log::add('mcztherm', 'debug', '- Activation est sur ON');
				//teste si daemon est demarré ?   Si non, le demarre
				$mczremote = plugin::byId('mczremote');
				$mczremoteinfos = $mczremote->deamon_info();
				if ($mczremoteinfos['state'] == 'nok') {
					log::add('mcztherm','debug',' - (Activation) Start MCZ Remote Daemon');
					$mczremote->deamon_start();
					// @@@@@ mcztherm::processStartDaemon();
				} 


				$heure = $mcztherm->getCmd(null,'horaire')->execCmd();
				// skip si heure est à 0000
				if ($heure == '0000')  {
					// skip ...
				} else {
					$heure = substr('000'.$heure,-4);   //Traitement des 0 sur les heures < 10:00
					$heure_timestamp = strtotime(date('d-m-Y') . ' ' . $heure);
					if ($heure_timestamp == strtotime(date('H:i'))) {
						log::add('mcztherm', 'info', ' - Activation du thermostat:  heure correspond ==> activation');
						$returnval = 1;
						$cmd = $mcztherm->getCmd(null,'on');  // execute la commande ON
						$cmd->execCmd();
						$cmd = $mcztherm->getCmd(null,'act_off');  // Exécute la commande "act_off"
						$cmd->execCmd();
					}
				}
			}
		}
		return($returnval);
	}

	private static function testWaitEtat($mcztherm) {
		// teste si Etat est contient un des mots spécifiés dans la liste
		// Return:   0: on continue   1: Un des mots trouvé ==>  signale
		$returnval = 0;
		if (($mcztherm->getConfiguration('CheckWaitEtat') == 1) && ($mcztherm->getConfiguration('WaitEtat') != '')) {
			$liste = $mcztherm->getConfiguration('WaitEtat');
			$arliste = explode(';', $liste);
			$etat = mcztherm::getCmdInfoValue($mcztherm, 'InfoEtatPoele');
			foreach ($arliste as $mot) {
				if (substr_compare($etat, $mot, 0, strlen($mot), true) == 0) {
					log::add('mcztherm', 'info',' - ' . $etat . ' ==> Attente boucle suivante');
					$returnval = 1;
					break;
				}
			}
		}
		return($returnval);
	}

	private static function testEtatError($mcztherm) {
		// teste si Etat reporte une erreur
		// Return:   0: on continue   1: Une erreur et le signale
		$returnval = 0;
		$etat = mcztherm::getCmdInfoValue($mcztherm, 'InfoEtatPoele');

		if (substr_compare($etat, 'Erreur', 0, strlen('Erreur'), true) == 0) {
			// Envoi d'un message de notification
			if ($mcztherm->getConfiguration('CmdMessage') != '') {
				$cmd = cmd::byId(str_replace('#','',$mcztherm->getConfiguration('CmdMessage')));
				$options = array('title'=>'MCZ Maestro', 'message'=> $etat);
				$cmd->execCmd($options, $cache = 0);
				log::add('mcztherm', 'warning', '* '. $etat);
				$returnval = 1;
			}
		}

		if (($mcztherm->getConfiguration('InfoNiveauPellets') != '') || ($mcztherm->getConfiguration('NiveauPelletsNOK') != '')) {
			$nivPellets = mcztherm::getCmdInfoValue($mcztherm, 'InfoNiveauPellets');
			$nivPelletsNOK = $mcztherm->getConfiguration('NiveauPelletsNOK');
			//log::add('mcztherm', 'debug', 'nivPellets:' . $nivPellets . '  nivPelletsNOK:' . $nivPelletsNOK); 
			if (substr_compare($nivPellets, $nivPelletsNOK, 0, strlen($nivPelletsNOK), true) == 0) {
				// Envoi d'un message de notification
				if ($mcztherm->getConfiguration('CmdMessage') != '') {
					$cmd = cmd::byId(str_replace('#','',$mcztherm->getConfiguration('CmdMessage')));
					$options = array('title'=>'MCZ Maestro', 'message'=> 'Pellets ' . $nivPellets);
					$cmd->execCmd($options, $cache = 0);
					log::add('mcztherm', 'warning', '* Pellets '. $nivPellets);
					$returnval = 1;
				}
			}
		}
		return($returnval);
	}

	private static function testLastOffDelay($mcztherm) {
		// return: 0: delai minimum passé   1: délai pas dépassé ==> attendre
		$returnval = 0;
		$delay = 60 * $mcztherm->getConfiguration('DureeArretMin');
		$ts_curtime = strtotime(date('Y-m-d H:i:00'));
		$ts_lastOff = strtotime($mcztherm->getCache('lastOff'));
		if (($ts_lastOff + $delay) >= $ts_curtime) {
			log::add('mcztherm','debug','- testLastOffDelay: Delai pas dépassé  (LastOff:' . $mcztherm->getCache('lastOff') . ')');
			$returnval = 1;
		}
		return($returnval);
	}

	// *************************** Public functions **************************

	public static function processEteindre($equipement) {
		$mcztherm = eqLogic::byId($equipement);
		log::add('mcztherm','debug','Execution de la fonction processEteindre');
		// Teste si le poele est eteint
		// Si non, eteint le poele.
		if ($mcztherm->getConfiguration('InfoEtatPoele') != '') {
			$cmdEtat = cmd::byId(str_replace('#','',$mcztherm->getConfiguration('InfoEtatPoele')))->execCmd();
			if ($cmdEtat != $mcztherm->getConfiguration('EtatOff')) {
				log::add('mcztherm','debug','Envoi commande off');
				// Extinction du poele.  Envoi commande OFF
				$cmdOff = $mcztherm->getConfiguration('CmdOffPoele');
				try {
					$cmd = cmd::byId(str_replace('#', '', $cmdOff));
					log::add('mcztherm','info','  - Action:  ' . cmd::byId(str_replace('#', '', $cmdOff))->getHumanName() );
					if (is_object($cmd) && $mcztherm->getId() != $cmd->getEqLogic_id()) {
						$options = array();
						if (isset($action['options'])) {
							$options = $action['options'];
							foreach ($options as $key => $value) {
								$options[$key] = str_replace('#slider#', $consigne, $value);
							}
						}
						scenarioExpression::createAndExec('action', $cmdOff, $options);
					}
				} catch (Exception $e) {
					log::add('mcztherm', 'error', $mcztherm->getHumanName() . __(' : Erreur lors de l\'éxecution de ', __FILE__) . $cmdOff . __('. Détails : ', __FILE__) . getMessage());
				}
				// Indiquer currentConsigne,  currentMode
				mcztherm::processConsigneCommands($mcztherm, 'P0');
				// Récupère l'info de consigne de température
				$cmdhb = $mcztherm->getCmd(null,'T_consigne_info');
				$consigneTemperature = cmd::byString('#'. $cmdhb->getHumanName() .'#')->execCmd();
				mcztherm::processTempCommand($mcztherm, $consigneTemperature);
				log::add('mcztherm','debug', 'Eteindre: Mode => P0  Temp => ' . $consigneTemperature);


			}
		}
	}


	public static function processStopDaemon() {
		//log::add('mcztherm','debug','Execution de la fonction processStopDaemon');
		$mczremote = plugin::byId('mczremote');
		$mczremoteinfos = $mczremote->deamon_info();
		//foreach ($mczremoteinfos as $key => $value) {
		//	log::add('mcztherm','debug',$key . '---' . $value);
		//}
		if ($mczremoteinfos['state'] == 'ok') {
			log::add('mcztherm','debug','Stop MCZ Remote Daemon');
			$mczremote->deamon_stop();
		} else {
			log::add('mcztherm','debug','MCZ Remote Daemon  already stopped');
		}
	}

	public static function processStartDaemon() {
		//log::add('mcztherm','debug','Execution de la fonction processStartDaemon');
		$mczremote = plugin::byId('mczremote');
		$mczremote->deamon_start();
	}



	public static function nextexec($equipement) {
		// currentMode est le nom du mode (jour, nuit, lever, ....)
		$mcztherm = eqLogic::byId($equipement);
		log::add('mcztherm','debug','Execution de la fonction nextexec');
		//log::add('mcztherm','debug', 'mode => ' . json_encode($mcztherm->getConfiguration('mode')));
		if ($mcztherm->getIsEnable() != 1) { // Si l'équipement est inactif ==> quit
			return;
		}
		$cmd = $mcztherm->getCmd(null,'etat');  // Retourne la commande "etat" si elle existe
		if (!is_object($cmd)) { //Si la commande n'existe pas ==> quit
			return;
		}

		// Synchronisation Date, Heure du poele
		mcztherm::processMajDateHeure($mcztherm);

		if ($mcztherm->getCache('lastAction', '') == '') {
			$mcztherm->setCache('lastAction', date('1970-01-01 00:00:00'));
			$mcztherm->setCache('currentMode', '--');
		}


		$cmdValue = $cmd->execCmd();
		if ($cmdValue == 0) { // mcztherm est Off
			// remise à zéro des variables de fonctionnment
			$mcztherm->setCache('lastAction', date('1970-01-01 00:00:00'));
			$mcztherm->setCache('currentMode', '--');
			if ($mcztherm::testActivationThermostat($mcztherm) == 1) {
				$cmdValue = 1;
			}											
		}


		if ($cmdValue == 1) { // mcztherm sur On
			$lastAction = $mcztherm->getCache('lastAction', '');
			$currentMode = $mcztherm->getCache('currentMode', '');											
			//log::add('mcztherm','debug','** Infos(begin): lastAction: ' . $lastAction . ' currentMode: ' . $currentMode);

			// Teste si etat reporte une erreur ==> Notification (no reaction)
			mcztherm::testEtatError($mcztherm);

			// Examine si mode d'exclusion de l'état de la commande info 
			if ($mcztherm::testExclusionEtat($mcztherm) == 1) {
				log::add('mcztherm','debug','- Exclusion sur état du poele est active');
				return;
			}


			// recupere les variables de fonctionnement en cache heure, mode
			$lastAction = $mcztherm->getCache('lastAction', '');
			$currentMode = $mcztherm->getCache('currentMode', '');											
			log::add('mcztherm','info',' - Infos: lastAction: ' . $lastAction . ' currentMode: ' . $currentMode );

			// Récupère l'etat du poêle.  Si "Puissance x" le compare à la currentConsigne.
			//$puissanceActiveVal = intval(substr($mcztherm::getCmdInfoValue($mcztherm, 'InfoPuissancePoele'), -1));
			$etatPoele = $mcztherm::getCmdInfoValue($mcztherm, 'InfoPuissancePoele');
			log::add('mcztherm','debug', ' - EtatPoele:' . $etatPoele);
			if(preg_match("/Puissance [0-5]/", $etatPoele )) {
				$puissanceActiveVal = intval(substr($etatPoele, -1));
			} else if(preg_match("/Eteint/", $etatPoele )) {
				$puissanceActiveVal = 0;
			}
			$currentConsigneVal = intval(substr($mcztherm->getCache('currentConsigne', ''), -1));
			log::add('mcztherm','debug',' - P active: ' . $puissanceActiveVal . '   Current Consigne: ' . $currentConsigneVal);
			if ($puissanceActiveVal != $currentConsigneVal) {
				log::add('mcztherm','debug',' - Correction - Nouveau currentConsigne: P'. $puissanceActiveVal);
				$mcztherm->setCache('currentConsigne', 'P' . $puissanceActiveVal);
			}

			if ($lastAction == '1970-01-01 00:00:00') {
				// Determine quel mode choisir en fonction de l'heure et récupère la consigne et la température.
				$mode = $mcztherm::testMode($mcztherm);
				log::add('mcztherm','debug','- actualMode: '. $mode['nom']);
				//if ($mode == 1) { return; }     // conditions d'heure invalide
				$currentMode = $mode['nom'];  // curmode value is correct. May become $currentMode value
				$curTemperature = $mode['temp'];

				// indique la nouvelle température dans le slider
				$options = array('slider'=>$curTemperature);
				$cmdhb = $mcztherm->getCmd(null,'T_consigne');
				$cmdhbname = $cmdhb->getHumanName();	
				cmd::byString('#'.$cmdhbname.'#')->execCmd($options, $cache = 0);

				$mcztherm->setCache('lastAction', date('Y-m-d H:i:00'));
				$mcztherm->setCache('currentMode', $currentMode);											
			}
		

			// Teste si on est à un changement de mode
			$newMode = $mcztherm::testChangeMode($mcztherm);
			// //log::add('mcztherm','debug', 'newMode => ' . json_encode($newMode));
			if (is_array($newMode)) {  
				$currentMode = $newMode['nom'];  // curmode value is correct. May become $currentMode value
				$newTemperature = $newMode['temp'];
				// indique la nouvelle température dans le slider
				$options = array('slider'=>$newTemperature);
				$cmdhb = $mcztherm->getCmd(null,'T_consigne');
				cmd::byString('#'. $cmdhb->getHumanName() .'#')->execCmd($options, $cache = 0);

				$mcztherm->setCache('lastAction', date('Y-m-d H:i:00'));
				$mcztherm->setCache('currentMode', $currentMode);											
			}


			// Verifier si le currentmode est toujours valide.  Un changement peut avoir été effectué ....
			$checkMode = $mcztherm::testMode($mcztherm);
			log::add('mcztherm','debug', 'CheckMode => ' . json_encode($newMode));
			if (is_array($checkMode)) {  
				if ($currentMode != $checkMode['nom']) {
					$currentMode = $checkMode['nom'];  // curmode value is correct. May become $currentMode value
					$newTemperature = $checkMode['temp'];
					// indique la nouvelle température dans le slider
					$options = array('slider'=>$newTemperature);
					$cmdhb = $mcztherm->getCmd(null,'T_consigne');
					cmd::byString('#'. $cmdhb->getHumanName() .'#')->execCmd($options, $cache = 0);

					$mcztherm->setCache('lastAction', date('Y-m-d H:i:00'));
					$mcztherm->setCache('currentMode', $currentMode);											
				}
			}


			// Récupère l'info de consigne de température
			$cmdhb = $mcztherm->getCmd(null,'T_consigne_info');
			$consigneTemperature = cmd::byString('#'. $cmdhb->getHumanName() .'#')->execCmd();

			log::add('mcztherm','debug', 'Actual: Mode => ' . $currentMode . ' Temp => ' . $consigneTemperature);

			// Détermination de la tendance de chauffe pour le calcul de l'hystéresis
			$curconsigne = $mcztherm->getCache('currentConsigne', '');
			if ($curconsigne == 'P0') {
				$tendance = -1;
			} else {
				$tendance = 1;
			}
			log::add('mcztherm','debug','- Current Consigne ' . $curconsigne . '  Tendance ' . $tendance);
			$hysterese = ($mcztherm->getConfiguration('Hysterese') / 2) * $tendance;

			// récupère et calcule les différentes températures de seuil
			$TempP0 = $consigneTemperature + $mcztherm->getConfiguration('DeltaTempArret') + $hysterese;
			$TempP1 = $consigneTemperature + $mcztherm->getConfiguration('DeltaTempP1') + $hysterese;
			$TempP2 = $consigneTemperature + $mcztherm->getConfiguration('DeltaTempP2') + $hysterese;
			$TempP3 = $consigneTemperature + $mcztherm->getConfiguration('DeltaTempP3') + $hysterese;
			$TempP4 = $consigneTemperature + $mcztherm->getConfiguration('DeltaTempP4') + $hysterese;
			$TempP5 = $consigneTemperature + $mcztherm->getConfiguration('DeltaTempP5') + $hysterese;

			if (mcztherm::testWaitEtat($mcztherm) == 1) {
				//log::add('mcztherm', 'debug','- Un des états trouvé ==> Attente boucle suivante');
				return;
			}

			$TempAmbiante = mcztherm::getCmdInfoValue($mcztherm, 'SondeInt');

			if ($tendance > 0) {
				log::add('mcztherm', 'info', '-- T ambiante:' . $TempAmbiante . ' Demandée:' . $consigneTemperature .' Current Consigne ' . $curconsigne . '  Tendance:+ ');
				log::add('mcztherm', 'info', ' - Seuils: Arret:' . $TempP0 . '  P1:' . $TempP1 . '  P2:' . $TempP2 . '  P3:' . $TempP3 . '  P4:' . $TempP4 . '  P5:' . $TempP5);
				if($TempAmbiante >= $TempP0) {
					// arrêt du poele
					//log::add('mcztherm','info',' -  passage en consigne arret');
					mcztherm::processConsigneCommands($mcztherm, 'P0');
					mcztherm::processTempCommand($mcztherm, $consigneTemperature);
					// moved to processConsigneCommands:    $mcztherm->setCache('lastOff', date('Y-m-d H:i:00'));  // Sauve date/heure de l'arrêt
				} else if ($TempAmbiante >= $TempP1) {
					if (mcztherm::testLastOffDelay($mcztherm) == 1) { return; }  // Voir si allumage possible vu délai
					//log::add('mcztherm','info',' -  passage en consigne P1');
					mcztherm::processConsigneCommands($mcztherm, 'P1');  // activer les consignes P1
					mcztherm::processTempCommand($mcztherm, $consigneTemperature);
					mcztherm::processOn($mcztherm); // Test, Allume le poele 
				} else if ($TempAmbiante >= $TempP2){
					if (mcztherm::testLastOffDelay($mcztherm) == 1) { return; }  // Voir si allumage possible vu délai
					//log::add('mcztherm','info',' -  passage en consigne P2');
					mcztherm::processConsigneCommands($mcztherm, 'P2');  // activer les consignes P2
					mcztherm::processTempCommand($mcztherm, $consigneTemperature);
					mcztherm::processOn($mcztherm); // Test, Allume le poele 
				} else if ($TempAmbiante >= $TempP3){
					if (mcztherm::testLastOffDelay($mcztherm) == 1) { return; }  // Voir si allumage possible vu délai
					//log::add('mcztherm','info',' -  passage en consigne P3');
					mcztherm::processConsigneCommands($mcztherm, 'P3');  // activer les consignes P3
					mcztherm::processTempCommand($mcztherm, $consigneTemperature);
					mcztherm::processOn($mcztherm); // Test, Allumer le poele 
				} else if ($TempAmbiante >= $TempP4){
					if (mcztherm::testLastOffDelay($mcztherm) == 1) { return; }  // Voir si allumage possible vu délai
					//log::add('mcztherm','info',' -  passage en consigne P4');
					mcztherm::processConsigneCommands($mcztherm, 'P4');  // activer les consignes P4
					mcztherm::processTempCommand($mcztherm, $consigneTemperature);
					mcztherm::processOn($mcztherm); // Test, Allumer le poele 
				} else if ($TempAmbiante >= $TempP5){
					if (mcztherm::testLastOffDelay($mcztherm) == 1) { return; }  // Voir si allumage possible vu délai
					//log::add('mcztherm','info',' -  passage en consigne P5');
					mcztherm::processConsigneCommands($mcztherm, 'P5');  // activer les consignes P5
					mcztherm::processTempCommand($mcztherm, $consigneTemperature);
					mcztherm::processOn($mcztherm); // Test, Allumer le poele 
				} else if ($TempAmbiante < $TempP5){
					if (mcztherm::testLastOffDelay($mcztherm) == 1) { return; }  // Voir si allumage possible vu délai
					//log::add('mcztherm','info',' -  passage en consigne P5max');
					mcztherm::processConsigneCommands($mcztherm, 'P5');  // activer les consignes P5
					mcztherm::processTempCommand($mcztherm, $consigneTemperature);
					mcztherm::processOn($mcztherm); // Test, Allumer le poele 
				}
			} else if ($tendance < 0) {
				log::add('mcztherm', 'info', '-- T ambiante:' . $TempAmbiante . ' Demandée:' . $consigneTemperature . ' Current Consigne ' . $curconsigne . '  Tendance:- ');
				log::add('mcztherm', 'info', ' - Seuils: Arret:---  P1:' . $TempP1 . '  P2:' . $TempP2 . '  P3:' . $TempP3 . '  P4:' . $TempP4 . '  P5:' . $TempP5);
				if ($TempAmbiante < $TempP5){
					if (mcztherm::testLastOffDelay($mcztherm) == 1) { return; }  // Voir si allumage possible vu délai
					//log::add('mcztherm','info',' -  passage en consigne P4');
					mcztherm::processConsigneCommands($mcztherm, 'P5');  // activer les consignes P5
					mcztherm::processTempCommand($mcztherm, $consigneTemperature);
					mcztherm::processOn($mcztherm); // Test, Allumer le poele 
				} else if ($TempAmbiante < $TempP4){
					if (mcztherm::testLastOffDelay($mcztherm) == 1) { return; }  // Voir si allumage possible vu délai
					//log::add('mcztherm','info',' -  passage en consigne P4');
					mcztherm::processConsigneCommands($mcztherm, 'P4');  // activer les consignes P4
					mcztherm::processTempCommand($mcztherm, $consigneTemperature);
					mcztherm::processOn($mcztherm); // Test, Allumer le poele 
				} else if ($TempAmbiante < $TempP3){
					if (mcztherm::testLastOffDelay($mcztherm) == 1) { return; }  // Voir si allumage possible vu délai
					//log::add('mcztherm','info',' -  passage en consigne P3');
					mcztherm::processConsigneCommands($mcztherm, 'P3');  // activer les consignes P3
					mcztherm::processTempCommand($mcztherm, $consigneTemperature);
					mcztherm::processOn($mcztherm); // Test, Allumer le poele 
				} else if ($TempAmbiante < $TempP2){
					if (mcztherm::testLastOffDelay($mcztherm) == 1) { return; }  // Voir si allumage possible vu délai
					//log::add('mcztherm','info',' -  passage en consigne P2');
					mcztherm::processConsigneCommands($mcztherm, 'P2');  // activer les consignes P2
					mcztherm::processTempCommand($mcztherm, $consigneTemperature);
					mcztherm::processOn($mcztherm); // Test, Allume le poele 
				} else if ($TempAmbiante < $TempP1) {
					if (mcztherm::testLastOffDelay($mcztherm) == 1) { return; }  // Voir si allumage possible vu délai
					//log::add('mcztherm','info',' -  passage en consigne P1');
					mcztherm::processConsigneCommands($mcztherm, 'P1');  // activer les consignes P1
					mcztherm::processTempCommand($mcztherm, $consigneTemperature);
					mcztherm::processOn($mcztherm); // Test, Allume le poele 
				} else if($TempAmbiante < $TempP0) {
					// arrêt du poele
					log::add('mcztherm','debug','-  Le poele est déjà arrêté.');
					//mcztherm::processConsigneCommands($mcztherm, 'P0');
					//mcztherm::processTempCommand($mcztherm, $consigneTemperature);
					///// moved to processConsigneCommands:    $mcztherm->setCache('lastOff', date('Y-m-d H:i:00'));  // Sauve date/heure de l'arrêt
				}
			}

		}  // endof if ($cmdValue == 1)
	}  //endof nextexec	



	/* Fonction exécutée automatiquement toutes les 5 minutes par Jeedom */
	public static function cron5() {
		foreach (self::byType('mcztherm') as $mcztherm) {  // Parcours tous les équipements du plugin
			mcztherm::nextexec($mcztherm->getId());
		}
	}


	/* *********************Méthodes d'instance************************* */

	public function preInsert() {

	}

	public function postInsert() {

	}

	public function preSave() {
		// ConsignesTab: Delta temperature
		if ($this->getConfiguration('DeltaTempArret') === '') {
			$this->setConfiguration('DeltaTempArret', 0);
		}
		if ($this->getConfiguration('Hysterese') === '') {
			$this->setConfiguration('Hysterese', 1);
		}
		if ($this->getConfiguration('DureeArretMin') === '') {
			$this->setConfiguration('DureeArretMin', 30);
		}
		if ($this->getConfiguration('DeltaTempP1') === '') {
			$this->setConfiguration('DeltaTempP1', -1);
		}
		if ($this->getConfiguration('DeltaTempP2') === '') {
			$this->setConfiguration('DeltaTempP2', -2);
		}
		if ($this->getConfiguration('DeltaTempP3') === '') {
			$this->setConfiguration('DeltaTempP3', -3);
		}
		if ($this->getConfiguration('DeltaTempP4') === '') {
			$this->setConfiguration('DeltaTempP4', -5);
		}
		if ($this->getConfiguration('DeltaTempP5') === '') {
			$this->setConfiguration('DeltaTempP5', -7);
		}

		//Generaltab: Modes ...
		if ($this->getConfiguration('mode') === '') {
			$modes = array();
			$modes[] = array( 'nom' => 'Jour', 'active' => '1', 'temp' => '21', 'time' => '05:30');
			$modes[] = array('nom' => 'Nuit', 'active' => '1', 'temp' => '15', 'time' => '22:30');
			$this->setConfiguration('mode', $modes);
		}

		// Infostab: Attente sur état
		if ($this->getConfiguration('CheckWaitEtat') === '') {
			$this->setConfiguration('CheckWaitEtat', 1);
		}
		if ($this->getConfiguration('WaitEtat') === '') {
			$this->setConfiguration('WaitEtat', 'Extinction;Refroidissement;Erreur');
		}
		if ($this->getConfiguration('EtatOff') === '') {
			$this->setConfiguration('EtatOff', 'Eteint');
		}

		if ($this->getConfiguration('HeureMaj') === '') {
			$this->setConfiguration('HeureMaj', '03:05');
		}

		if ($this->getConfiguration('NiveauPelletsNOK') === '') {
			$this->setConfiguration('NiveauPelletsNOK', 'Niveau presque vide');
		}



	}

	public function postSave() {
		log::add('hbtherm','debug','Exécution de la fonction postSave');
		//Création des commandes
		$order = 0;
		$refresh = $this->getCmd(null, 'refresh');
		if (!is_object($refresh)) {
			$refresh = new mczthermCmd();
			$refresh->setLogicalId('refresh');
			$refresh->setName(__('Rafraichir', __FILE__));
		}
		$refresh->setOrder($order++);
		$refresh->setEqLogic_id($this->getId());
		$refresh->setType('action');
		$refresh->setSubType('other');
		$refresh->save();

		$info = $this->getCmd(null, 'etat');
		if (!is_object($info)) {
			$info = new mczthermCmd();
			$info->setLogicalId('etat');
			$info->setName(__('Etat', __FILE__));
			$info->setIsVisible(0);
			$info->setisHistorized(1);
		}
		$info->setOrder($order++);
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('binary');
		$info->save();

		$action = $this->getCmd(null, 'on');
		if (!is_object($action)) {
			$action = new mczthermCmd();
			$action->setLogicalId('on');
			$action->setName(__('On', __FILE__));
			$action->setTemplate('dashboard','mcztherm::mcztoggle');
			$action->setTemplate('mobile','mcztherm::mcztoggle');
			$action->setDisplay('showNameOndashboard','0');
			$action->setDisplay('showNameOnmobile','0');
		}
		$action->setOrder($order++);
		$action->setEqLogic_id($this->getId());
		$action->setValue($info->getId());
		$action->setConfiguration('updateCmdId', $info->getId());
		$action->setConfiguration('updateCmdToValue', 1);
		$action->setType('action');
		$action->setSubType('other');
		$action->save();

		$action = $this->getCmd(null, 'off');
		if (!is_object($action)) {
			$action = new mczthermCmd();
			$action->setLogicalId('off');
			$action->setName(__('Off', __FILE__));
			$action->setTemplate('dashboard','mcztherm::mcztoggle');
			$action->setTemplate('mobile','mcztherm::mcztoggle');
			$action->setDisplay('showNameOndashboard','0');
			$action->setDisplay('showNameOnmobile','0');
			// $action->setDisplay('forceReturnLineAfter','1');
		}
		$action->setOrder($order++);
		$action->setEqLogic_id($this->getId());
		$action->setValue($info->getId());
		$action->setConfiguration('updateCmdId', $info->getId());
		$action->setConfiguration('updateCmdToValue', 0);
		$action->setType('action');
		$action->setSubType('other');
		$action->save();

		$info = $this->getCmd(null, 't_consigne_info');
		if (!is_object($info)) {
			$info = new mczthermCmd();
			$info->setLogicalId('t_consigne_info');
			$info->setName(__('T_consigne_info', __FILE__));
			$info->setIsVisible(0);
			$info->setisHistorized(0);
			$info->setConfiguration('minValue', 5);
			$info->setConfiguration('maxValue', 35);
			$info->setUnite('');
		}
		$info->setOrder($order++);
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('numeric');
		$info->save();

		$action = $this->getCmd(null, 't_consigne');
		if (!is_object($action)) {     
			// Si la commande n'existe pas -> Elle est créée
			// Mettre ici les éléments que l'utilisateur peut modifier
			$action = new mczthermCmd();
			$action->setLogicalId('t_consigne');
			$action->setName(__('T_consigne', __FILE__));
			$action->setConfiguration('minValue', 5);
			$action->setConfiguration('maxValue', 35);
			$action->setTemplate('dashboard','button');
			$action->setTemplate('mobile','button');
			$action->setDisplay('showNameOndashboard','0');
			$action->setDisplay('showNameOnmobile','0');
		}
		// Les éléments ci-dessous sont mis à jour lors de chaque save de l'équipement
		$action->setOrder($order++);
		$action->setEqLogic_id($this->getId());
		$action->setConfiguration('infoName', $info->getId());
		$action->setValue($info->getId());
		$action->setType('action');
		$action->setSubType('slider');
		$action->setIsVisible(1);
		$action->save();

		// insérer ici les commandes techniques
		$info = $this->getCmd(null, 'activation');
		if (!is_object($info)) {
			$info = new mczthermCmd();
			$info->setLogicalId('activation');
			$info->setName(__('Activation', __FILE__));
			$info->setIsVisible(0);
		}
		$info->setOrder($order++);
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('binary');
		$info->save();

		$action = $this->getCmd(null, 'act_on');
		if (!is_object($action)) {
			$action = new mczthermCmd();
			$action->setLogicalId('act_on');
			$action->setName(__('Act_On', __FILE__));
			$action->setTemplate('dashboard','mcztherm::mczact');
			$action->setTemplate('mobile','mcztherm::mczact');
			$action->setDisplay('showNameOndashboard','0');
			$action->setDisplay('showNameOnmobile','0');
			$action->setDisplay('forceReturnLineBefore','1');
		}
		$action->setOrder($order++);
		$action->setEqLogic_id($this->getId());
		$action->setValue($info->getId());
		$action->setConfiguration('updateCmdId', $info->getId());
		$action->setConfiguration('updateCmdToValue', 1);
		$action->setType('action');
		$action->setSubType('other');
		$action->save();

		$action = $this->getCmd(null, 'act_off');
		if (!is_object($action)) {
			$action = new mczthermCmd();
			$action->setLogicalId('act_off');
			$action->setName(__('Act_Off', __FILE__));
			$action->setTemplate('dashboard','mcztherm::mczact');
			$action->setTemplate('mobile','mcztherm::mczact');
			$action->setDisplay('showNameOndashboard','0');
			$action->setDisplay('showNameOnmobile','0');
		}
		$action->setOrder($order++);
		$action->setEqLogic_id($this->getId());
		$action->setValue($info->getId());
		$action->setConfiguration('updateCmdId', $info->getId());
		$action->setConfiguration('updateCmdToValue', 0);
		$action->setType('action');
		$action->setSubType('other');
		$action->save();

		$info = $this->getCmd(null, 'horaire');
		if (!is_object($info)) {
			$info = new mczthermCmd();
			$info->setLogicalId('horaire');
			$info->setName(__('Horaire', __FILE__));
			$info->setIsVisible(0);
			$info->setisHistorized(1);
			$info->setConfiguration('minValue', 0);
			$info->setConfiguration('maxValue', 2359);
		}
		$info->setOrder($order++);
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('numeric');
		$info->save();

		$action = $this->getCmd(null, 'var_horaire');
		if (!is_object($action)) {
			$action = new mczthermCmd();
			$action->setLogicalId('var_horaire');
			$action->setName(__('Var_Horaire', __FILE__));
			$action->setConfiguration('minValue', 0);
			$action->setConfiguration('maxValue', 2359);
			$action->setTemplate('dashboard','mcztherm::mcztime');
			$action->setTemplate('mobile','mcztherm::mcztime');
			$action->setDisplay('showNameOndashboard','0');
			$action->setDisplay('showNameOnmobile','0');
			$arr['step'] = 5;
			$action->setDisplay('parameters', $arr);

		}
		$action->setOrder($order++);
		$action->setEqLogic_id($this->getId());
		$action->setConfiguration('infoName', $info->getId());
		$action->setValue($info->getId());
		$action->setType('action');
		$action->setSubType('slider');
		$action->save();


		$info = $this->getCmd(null, 't_demandee');
		if (!is_object($info)) {
			$info = new mczthermCmd();
			$info->setLogicalId('t_demandee');
			$info->setName(__('T_demandee', __FILE__));
			$info->setIsVisible(0);
			$info->setisHistorized(0);
			$info->setConfiguration('minValue', 0);
			$info->setConfiguration('maxValue', 70);
			$info->setUnite('');
		}
		$info->setOrder($order++);
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('numeric');
		$info->save();

		$action = $this->getCmd(null, 'var_t_demandee');
		if (!is_object($action)) {
			$action = new mczthermCmd();
			$action->setLogicalId('var_t_demandee');
			$action->setName(__('Var_T_demandee', __FILE__));
			$action->setConfiguration('minValue', 0);
			$action->setConfiguration('maxValue', 70);
			$action->setTemplate('dashboard','button');
			$action->setTemplate('mobile','button');
			$action->setDisplay('showNameOndashboard','0');
			$action->setDisplay('showNameOnmobile','0');
		}
		$action->setOrder($order++);
		$action->setEqLogic_id($this->getId());
		$action->setConfiguration('infoName', $info->getId());
		$action->setValue($info->getId());
		$action->setType('action');
		$action->setSubType('slider');
		$action->setIsVisible(0);
		$action->save();

		$info = $this->getCmd(null, 'ordrepoele');
		if (!is_object($info)) {
			$info = new mczthermCmd();
			$info->setLogicalId('ordrepoele');
			$info->setName(__('ordrepoele', __FILE__));
			$info->setIsVisible(0);
			$info->setisHistorized(0);
		}
		$info->setOrder($order++);
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('string');
		$info->save();

		$info = $this->getCmd(null, 'currentconsigne');
		if (!is_object($info)) {
			$info = new mczthermCmd();
			$info->setLogicalId('currentconsigne');
			$info->setName(__('currentconsigne', __FILE__));
			$info->setIsVisible(0);
			$info->setisHistorized(0);
		}
		$info->setOrder($order++);
		$info->setEqLogic_id($this->getId());
		$info->setType('info');
		$info->setSubType('string');
		$info->save();

		$action = $this->getCmd(null, 'eteindre');
		if (!is_object($action)) {
			$action = new mczthermCmd();
			$action->setLogicalId('eteindre');
			$action->setName(__('Eteindre', __FILE__));
			//$action->setTemplate('dashboard','mcztherm::mcztoggle');
			//$action->setTemplate('mobile','mcztherm::mcztoggle');
			//$action->setDisplay('showNameOndashboard','0');
			//$action->setDisplay('showNameOnmobile','0');
			// $action->setDisplay('forceReturnLineAfter','1');
		}
		$action->setOrder($order++);
		$action->setEqLogic_id($this->getId());
		$action->setValue($info->getId());
		$action->setConfiguration('updateCmdId', $info->getId());
		$action->setConfiguration('updateCmdToValue', 0);
		$action->setType('action');
		$action->setSubType('other');
		$action->save();

		$action = $this->getCmd(null, 'startdaemon');
		if (!is_object($action)) {
			$action = new mczthermCmd();
			$action->setLogicalId('startdaemon');
			$action->setName(__('Start Daemon', __FILE__));
			//$action->setTemplate('dashboard','mcztherm::mcztoggle');
			//$action->setTemplate('mobile','mcztherm::mcztoggle');
			//$action->setDisplay('showNameOndashboard','0');
			//$action->setDisplay('showNameOnmobile','0');
			// $action->setDisplay('forceReturnLineAfter','1');
			$action->setDisplay('forceReturnLineBefore','1');

		}
		$action->setOrder($order++);
		$action->setEqLogic_id($this->getId());
		$action->setValue($info->getId());
		$action->setConfiguration('updateCmdId', $info->getId());
		$action->setConfiguration('updateCmdToValue', 0);
		$action->setType('action');
		$action->setSubType('other');
		$action->save();

		$action = $this->getCmd(null, 'stopdaemon');
		if (!is_object($action)) {
			$action = new mczthermCmd();
			$action->setLogicalId('stopdaemon');
			$action->setName(__('Stop Daemon', __FILE__));
			//$action->setTemplate('dashboard','mcztherm::mcztoggle');
			//$action->setTemplate('mobile','mcztherm::mcztoggle');
			//$action->setDisplay('showNameOndashboard','0');
			//$action->setDisplay('showNameOnmobile','0');
			// $action->setDisplay('forceReturnLineAfter','1');
		}
		$action->setOrder($order++);
		$action->setEqLogic_id($this->getId());
		$action->setValue($info->getId());
		$action->setConfiguration('updateCmdId', $info->getId());
		$action->setConfiguration('updateCmdToValue', 0);
		$action->setType('action');
		$action->setSubType('other');
		$action->save();





	}

	public function preUpdate() {

	}

	public function postUpdate() {

	}

	public function preRemove() {

	}

	public function postRemove() {

	}

	/* Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
	public function toHtml($_version = 'dashboard') {

	}
	*/

	/* Non obligatoire mais ca permet de déclencher une action après modification de variable de configuration
	public static function postConfig_<Variable>() {

	}
	*/

	/* Non obligatoire mais ca permet de déclencher une action avant modification de variable de configuration
	public static function preConfig_<Variable>() {

	}
	*/

	/* **********************Getteur Setteur*************************** */
}

class mczthermCmd extends cmd {
	/* *************************Attributs****************************** */


	/* ***********************Methode static*************************** */


	/* *********************Methode d'instance************************* */

	/* Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
	public function dontRemoveCmd() {
		return true;
	}
	*/

	public function execute($_options = array()) {
		//log::add('mcztherm','debug','Exécution de la fonction Execute');
		$eqlogic = $this->getEqLogic()->getId();
		$action = $this->getLogicalId();

		if ($action == 'eteindre') {
			//log::add('mcztherm','debug','- @@@@@ Action Eteindre');
			mcztherm::processEteindre($eqlogic);
		}
		else if ($action == 'startdaemon') {
			//log::add('mcztherm','debug','- @@@@@ Action Start MCZ Remote Daemon');
			mcztherm::processStartDaemon();
		}
		else if ($action == 'stopdaemon') {
			//log::add('mcztherm','debug','- @@@@@ Action Stop MCZ Remote Daemon');
			mcztherm::processStopDaemon();
		}
		else {
			// Action sur modification du slider
			switch ($this->getSubType()) {
				case 'other':
					//log::add('mcztherm','debug','- Action sur Other');
					$virtualCmd = virtualCmd::byId($this->getConfiguration('updateCmdId'));
					$value = $this->getConfiguration('updateCmdToValue');
					$result = jeedom::evaluateExpression($value);
					$virtualCmd->event($result);
					mcztherm::nextexec($eqlogic);
				break;
				case 'slider':
					//log::add('mcztherm','debug','- Action sur Slider');
					$virtualCmd = virtualCmd::byId($this->getConfiguration('infoName'));
					$value = $_options['slider'];
					$result = jeedom::evaluateExpression($value);
					$virtualCmd->event($result);	
				break;
			}
		}
	}

	/* **********************Getteur Setteur*************************** */

}
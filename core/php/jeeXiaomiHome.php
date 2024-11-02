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

if (!jeedom::apiAccess(init('apikey'), 'xiaomihome2')) {
	echo __('Vous n\'êtes pas autorisé à effectuer cette action', __FILE__);
	die();
}

if (init('test') != '') {
	echo 'OK';
	die();
}
$result = json_decode(file_get_contents("php://input"), true);
if (!is_array($result)) {
	log::add('xiaomihome2', 'debug', 'Format Invalide');
	die();
}

if (isset($result['devices'])) {
	foreach ($result['devices'] as $key => $datas) {
		$explode = explode('_',$key);
		$key = $explode[0];
		if ($key == 'aquara'){
			if (!($datas['cmd'] == 'heartbeat' || $datas['cmd'] == 'report' || $datas['cmd'] == 'read_ack')) {
				continue;
			}
			if (!isset($datas['sid'])) {
				continue;
			}
			$logical_id = $datas['sid'];
			if ($datas['model'] == 'gateway') {
				$logical_id = $datas['source'];
			}
			$xiaomihome2=xiaomihome2::byLogicalId($logical_id, 'xiaomihome2');
			if (!is_object($xiaomihome2)) {
				if ($datas['model'] == 'gateway') {
					//test si gateway qui a changé d'ip
					foreach (eqLogic::byType('xiaomihome2') as $gateway) {
						if ($gateway->getConfiguration('sid') == $datas['sid']) {
							$gateway->setConfiguration('gateway',$datas['source']);
							$gateway->setLogicalId($datas['source']);
							$gateway->save();
							return;
						}
					}
				}
				$xiaomihome2= xiaomihome2::createFromDef($datas,$key);
				if (!is_object($xiaomihome2)) {
					log::add('xiaomihome2', 'debug', __('Aucun équipement trouvé pour : ', __FILE__) . secureXSS($datas['sid']));
					continue;
				}
				sleep(2);
				event::add('jeedom::alert', array(
					'level' => 'warning',
					'page' => 'xiaomihome2',
					'message' => '',
				));
				event::add('xiaomihome2::includeDevice', $xiaomihome2->getId());
			}
			if (!$xiaomihome2->getIsEnable()) {
				continue;
			}
			if ($xiaomihome2->getConfiguration('gateway') != $datas['source'] && $datas['model'] != 'gateway') {
				$xiaomihome2->setConfiguration('gateway',$datas['source']);
				$xiaomihome2->save();
			}
			if ($datas['sid'] !== null && $datas['model'] !== null) {
				if (isset($datas['data'])) {
					$data = $datas['data'];
					foreach ($data as $key => $value) {
						if ($datas['cmd'] == 'heartbeat' && $key == 'status') {
							continue;
						}
						if ($datas['model'] == 'gateway'){
							xiaomihome2::receiveAquaraData($datas['source'], $datas['model'], $key, $value);
						} else {
							xiaomihome2::receiveAquaraData($datas['sid'], $datas['model'], $key, $value);
						}
					}
				}
				$xiaomihome2->setStatus('lastCommunication',date('Y-m-d H:i:s'));
				$xiaomihome2->save();
			}
		}
		elseif ($key == 'yeelight'){
			if (!isset($datas['capabilities']['id'])) {
				continue;
			}
			$logical_id = $datas['ip'];
			$xiaomihome2=xiaomihome2::byLogicalId($logical_id, 'xiaomihome2');
			if (!is_object($xiaomihome2)) {
				foreach (eqLogic::byType('xiaomihome2') as $yeelight) {
					if ($yeelight->getConfiguration('gateway') == $datas['ip']) {
						$yeelight->setLogicalId($datas['ip']);
						$yeelight->save();
						return;
					}
				}
				if (!isset($datas['capabilities']['model'])) {
					continue;
				}
				$xiaomihome2= xiaomihome2::createFromDef($datas,$key);
				if (!is_object($xiaomihome2)) {
					log::add('xiaomihome2', 'debug', __('Aucun équipement trouvé pour : ', __FILE__) . secureXSS($datas['capabilities']['id']));
					continue;
				}
				sleep(2);
				event::add('jeedom::alert', array(
					'level' => 'warning',
					'page' => 'xiaomihome2',
					'message' => '',
				));
				event::add('xiaomihome2::includeDevice', $xiaomihome2->getId());
			}
			if (!$xiaomihome2->getIsEnable()) {
				continue;
			}
			if (isset($datas['capabilities'])) {
				$data = $datas['capabilities'];
				$power = ($data['power'] == 'off')? 0:1;
				$xiaomihome2->checkAndUpdateCmd('status', $power);
				$xiaomihome2->checkAndUpdateCmd('brightness', $data['bright']);
				if ($xiaomihome2->getConfiguration('model') != 'mono' && $xiaomihome2->getConfiguration('model') != 'ceiling') {
					$xiaomihome2->checkAndUpdateCmd('color_mode', $data['color_mode']);
					$xiaomihome2->checkAndUpdateCmd('rgb', '#' . str_pad(dechex($data['rgb']), 6, "0", STR_PAD_LEFT));
					$xiaomihome2->checkAndUpdateCmd('hsv', $data['hue']);
					$xiaomihome2->checkAndUpdateCmd('saturation', $data['sat']);
				}
				if ($xiaomihome2->getConfiguration('model') != 'mono') {
					$xiaomihome2->checkAndUpdateCmd('temperature', $data['ct']);
				}
				if ($xiaomihome2->getConfiguration('model') == 'ceiling4' || $xiaomihome2->getConfiguration('model') == 'ceiling10') {
					$bgpower = ($data['bg_power'] == 'off')? 0:1;
					$xiaomihome2->checkAndUpdateCmd('bg_status', $bgpower);
					$xiaomihome2->checkAndUpdateCmd('bg_bright', $data['bg_bright']);
					$xiaomihome2->checkAndUpdateCmd('bg_rgb', $data['bg_rgb']);
				}
				$xiaomihome2->setConfiguration('ipwifi', $datas['ip']);
				$xiaomihome2->setConfiguration('gateway', $datas['ip']);
				$xiaomihome2->setStatus('lastCommunication',date('Y-m-d H:i:s'));
				$xiaomihome2->save();
			}
		}
		elseif ($key == 'wifi'){
			if (isset($datas['notfound'])){
				$logical_id = $datas['ip'];
				$xiaomihome2=xiaomihome2::byLogicalId($logical_id, 'xiaomihome2');
				event::add('xiaomihome2::notfound', $xiaomihome2->getId());
				continue;
			}
			if (isset($datas['found'])){
				$logical_id = $datas['ip'];
				$xiaomihome2=xiaomihome2::byLogicalId($logical_id, 'xiaomihome2');
				$xiaomihome2->setConfiguration('gateway',$datas['ip']);
				$xiaomihome2->setConfiguration('sid',$datas['serial']);
				$xiaomihome2->setConfiguration('short_id',$datas['devtype']);
				$xiaomihome2->setStatus('lastCommunication',date('Y-m-d H:i:s'));
				$xiaomihome2->setIsEnable(1);
				$xiaomihome2->setIsVisible(1);
				if (!in_array($datas['model'], array('vacuum','philipsceiling'))){
					$xiaomihome2->setConfiguration('password',$datas['token']);
				}
				$xiaomihome2->save();
				event::add('xiaomihome2::found', $xiaomihome2->getId());
				$refreshcmd = xiaomihome2Cmd::byEqLogicIdAndLogicalId($xiaomihome2->getId(),'refresh');
				$refreshcmd->execCmd();
				continue;
			}
			if (!isset($datas['model']) || !isset($datas['ip'])) {
				continue;
			}
			$logical_id = $datas['ip'];
			$xiaomihome2=xiaomihome2::byLogicalId($logical_id, 'xiaomihome2');
			if (!is_object($xiaomihome2)) {
				continue;
			}
			if (!$xiaomihome2->getIsEnable()) {
				continue;
			}
			log::add('xiaomihome2', 'debug', 'Status ' . print_r($datas, true));
			foreach ($xiaomihome2->getCmd('info') as $cmd) {
				$logicalId = $cmd->getLogicalId();
				if ($logicalId == '') {
					continue;
				}
				$path = explode('::', $logicalId);
				$value = $datas;
				foreach ($path as $key) {
					if (!isset($value[$key])) {
						continue (2);
					}
					$value = $value[$key];
					if (!is_array($value) && strpos($value, 'toggle') !== false && $cmd->getSubType() == 'binary') {
						$value = $cmd->execCmd();
						$value = ($value != 0) ? 0 : 1;
					}
				}
				if (!is_array($value)) {
					if ($cmd->getSubType() == 'numeric') {
						$value = round($value, 2);
					}
					$cmd->event($value);
				}
				if (strpos($logicalId,'battery') !== false) {
					$xiaomihome2->batteryStatus($value);
				}
				$xiaomihome2->setStatus('lastCommunication',date('Y-m-d H:i:s'));
				$xiaomihome2->save();
			}
		}
	}
}

?>

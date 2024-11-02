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
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class xiaomihome2 extends eqLogic {
  public static function cron5() {
    $deamon_info = self::deamon_info();
    if ($deamon_info['state'] != 'ok') {
      return;
    }
    $eqLogics = eqLogic::byType('xiaomihome2', true);
    foreach($eqLogics as $xiaomihome2) {
      if ($xiaomihome2->getConfiguration('type') == 'wifi') {
        if ($xiaomihome2->pingHost($xiaomihome2->getConfiguration('ipwifi')) == false) {
          log::add('xiaomihome2', 'debug', 'Offline Wifi : ' . $xiaomihome2->getName());
        } else {
          log::add('xiaomihome2', 'debug', 'Rafraîchissement de XiaomiWifi : ' . $xiaomihome2->getName());
          $value = json_encode(array('apikey' => jeedom::getApiKey('xiaomihome2'), 'type' => 'wifi','cmd' => 'refresh', 'model' => $xiaomihome2->getConfiguration('model'), 'dest' => $xiaomihome2->getConfiguration('gateway') , 'token' => $xiaomihome2->getConfiguration('password') , 'devtype' => $xiaomihome2->getConfiguration('short_id'), 'serial' => $xiaomihome2->getConfiguration('sid')));
          xiaomihome2::sendDaemon($value);
        }
      }
      if ($xiaomihome2->getConfiguration('type') == 'aquara' && $xiaomihome2->getConfiguration('model') == 'gateway') {
        log::add('xiaomihome2', 'debug', 'Rafraîchissement de Aqara : ' . $xiaomihome2->getName());
        $xiaomihome2->pingHost($xiaomihome2->getConfiguration('gateway'));
      }
      if ($xiaomihome2->getConfiguration('type') == 'yeelight') {
        if ($xiaomihome2->pingHost($xiaomihome2->getConfiguration('gateway')) == false) {
          log::add('xiaomihome2', 'debug', 'Equipement Yeelight déconnecté : ' . $xiaomihome2->getName());
        } else {
          log::add('xiaomihome2', 'debug', 'Rafraîchissement de Yeelight : ' . $xiaomihome2->getName());
          $value = json_encode(array('apikey' => jeedom::getApiKey('xiaomihome2'), 'type' => 'yeelight','cmd' => 'refresh', 'model' => $xiaomihome2->getConfiguration('model'), 'dest' => $xiaomihome2->getConfiguration('gateway') , 'token' => $xiaomihome2->getConfiguration('password') , 'devtype' => $xiaomihome2->getConfiguration('short_id'), 'serial' => $xiaomihome2->getConfiguration('sid'), 'id' => $xiaomihome2->getLogicalId()));
          xiaomihome2::sendDaemon($value);
        }
      }
    }
  }

  public static function createFromDef($_def,$_type) {
    event::add('jeedom::alert', array(
      'level' => 'warning',
      'page' => 'xiaomihome2',
      'message' => __('Nouveau module détecté', __FILE__),
    ));
    if ($_type == 'aquara') {
      if (!isset($_def['model']) || !isset($_def['sid'])) {
        log::add('xiaomihome2', 'error', 'Information manquante pour ajouter l\'équipement : ' . print_r($_def, true));
        event::add('jeedom::alert', array(
          'level' => 'danger',
          'page' => 'xiaomihome2',
          'message' => __('Information manquante pour ajouter l\'équipement. Inclusion impossible.', __FILE__),
        ));
        return false;
      }
      $logical_id = $_def['sid'];
      if ($_def['model'] == 'gateway') {
        $logical_id = $_def['source'];
      }
      $xiaomihome2=xiaomihome2::byLogicalId($logical_id, 'xiaomihome2');
      if (!is_object($xiaomihome2)) {
        if ($_def['model'] == 'gateway') {
          //test si gateway qui a changé d'ip
          foreach (eqLogic::byType('xiaomihome2') as $gateway) {
            if ($gateway->getConfiguration('sid') == $_def['sid']) {
              $gateway->setConfiguration('gateway',$_def['source']);
              $gateway->setLogicalId($logical_id );
              $gateway->save();
              return;
            }
          }
        }
        $device = self::devicesParameters($_def['model']);
        if (!is_array($device) || count($device) == 0) {
          log::add('xiaomihome2', 'debug', 'Impossible d\'ajouter l\'équipement : ' . print_r($_def, true));
          return true;
        }
        $xiaomihome2 = new xiaomihome2();
        $xiaomihome2->setEqType_name('xiaomihome2');
        $xiaomihome2->setLogicalId($logical_id);
        $xiaomihome2->setIsEnable(1);
        $xiaomihome2->setIsVisible(1);
        $xiaomihome2->setName($device['name'] . ' ' . $_def['sid']);
        $xiaomihome2->setConfiguration('sid', $_def['sid']);
        $xiaomihome2->setConfiguration('type', 'aquara');
        if (isset($device['configuration'])) {
          foreach ($device['configuration'] as $key => $value) {
            $xiaomihome2->setConfiguration($key, $value);
          }
        }
        event::add('jeedom::alert', array(
          'level' => 'warning',
          'page' => 'xiaomihome2',
          'message' => __('Module inclus avec succès ' . $_def['model'], __FILE__),
        ));
      }
      $xiaomihome2->setConfiguration('short_id',$_def['short_id']);
      $xiaomihome2->setConfiguration('gateway',$_def['source']);
      $xiaomihome2->setStatus('lastCommunication',date('Y-m-d H:i:s'));
      $xiaomihome2->setConfiguration('applyDevice','');
      $xiaomihome2->save();
    } elseif ($_type == 'yeelight') {
      if (!isset($_def['capabilities']['model']) || !isset($_def['capabilities']['id'])) {
        log::add('xiaomihome2', 'error', 'Information manquante pour ajouter l\'équipement : ' . print_r($_def, true));
        event::add('jeedom::alert', array(
          'level' => 'danger',
          'page' => 'xiaomihome2',
          'message' => __('Information manquante pour ajouter l\'équipement. Inclusion impossible.', __FILE__),
        ));
        return false;
      }
      $logical_id = $_def['capabilities']['id'];
      $xiaomihome2=xiaomihome2::byLogicalId($logical_id, 'xiaomihome2');
      if (!is_object($xiaomihome2)) {
        $device = self::devicesParameters($_def['capabilities']['model']);
        if (!is_array($device)) {
          return true;
        }
        if (count($device) == 0) {
          $device = self::devicesParameters('color');
        }
        $xiaomihome2 = new xiaomihome2();
        $xiaomihome2->setEqType_name('xiaomihome2');
        $xiaomihome2->setLogicalId($logical_id);
        $xiaomihome2->setName($_def['capabilities']['model'] . ' ' . $logical_id);
        $xiaomihome2->setConfiguration('sid', $logical_id);
        $xiaomihome2->setIsEnable(1);
        $xiaomihome2->setIsVisible(1);
        if (isset($device['configuration'])) {
          foreach ($device['configuration'] as $key => $value) {
            $xiaomihome2->setConfiguration($key, $value);
          }
        }
        event::add('jeedom::alert', array(
          'level' => 'warning',
          'page' => 'xiaomihome2',
          'message' => __('Module inclus avec succès ' . $_def['capabilities']['model'], __FILE__),
        ));
      }
      $xiaomihome2->setConfiguration('model',$_def['capabilities']['model']);
      $xiaomihome2->setConfiguration('short_id',$_def['capabilities']['fw_ver']);
      $xiaomihome2->setConfiguration('gateway',$_def['ip']);
      $xiaomihome2->setConfiguration('ipwifi', $_def['ip']);
      $xiaomihome2->setStatus('lastCommunication',date('Y-m-d H:i:s'));
      $xiaomihome2->setConfiguration('applyDevice','');
      $xiaomihome2->setConfiguration('type', 'yeelight');
      $xiaomihome2->save();
    }
    return $xiaomihome2;
  }

  public static function deamon_info() {
    $return = array();
    $return['log'] = 'xiaomihome2';
    $return['state'] = 'nok';
    $pid_file = jeedom::getTmpFolder('xiaomihome2') . '/deamon.pid';
    if (file_exists($pid_file)) {
      if (@posix_getsid(trim(file_get_contents($pid_file)))) {
        $return['state'] = 'ok';
      } else {
        shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null');
      }
    }
    $return['launchable'] = 'ok';
    return $return;
  }

  public static function deamon_start() {
    log::remove(__CLASS__ . '_update');
    log::remove(__CLASS__ . '_node');
    self::deamon_stop();
    $deamon_info = self::deamon_info();
    if ($deamon_info['launchable'] != 'ok') {
      throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
    }
    if (config::byKey('network::disableMangement')) {
      $url = network::getNetworkAccess('internal');
    } else {
      $url = network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp');
    }
    $xiaomihome2_path = realpath(dirname(__FILE__) . '/../../resources/xiaomihomed');
    $cmd = '/usr/bin/python3 ' . $xiaomihome2_path . '/xiaomihomed.py';
    $cmd .= ' --loglevel ' . log::convertLogLevel(log::getLogLevel('xiaomihome2'));
    $cmd .= ' --socketport ' . config::byKey('socketport', 'xiaomihome2');
    $cmd .= ' --callback ' . $url . '/plugins/xiaomihome2/core/php/jeexiaomihome.php';
    $cmd .= ' --apikey ' . jeedom::getApiKey('xiaomihome2');
    $cmd .= ' --cycle ' . config::byKey('cycle', 'xiaomihome2');
    $cmd .= ' --pid ' . jeedom::getTmpFolder('xiaomihome2') . '/deamon.pid';
    log::add('xiaomihome2', 'info', 'Lancement démon xiaomihome2 : ' . $cmd);
    $result = exec($cmd . ' >> ' . log::getPathToLog('xiaomihome2') . ' 2>&1 &');
    $i = 0;
    while ($i < 30) {
      $deamon_info = self::deamon_info();
      if ($deamon_info['state'] == 'ok') {
        break;
      }
      sleep(1);
      $i++;
    }
    if ($i >= 30) {
      log::add('xiaomihome2', 'error', 'Impossible de lancer le démon xiaomihome2d. Vérifiez le log.', 'unableStartDeamon');
      return false;
    }
    message::removeAll('xiaomihome2', 'unableStartDeamon');
    return true;
  }

  public static function deamon_stop() {
    $pid_file = jeedom::getTmpFolder('xiaomihome2') . '/deamon.pid';
    if (file_exists($pid_file)) {
      $pid = intval(trim(file_get_contents($pid_file)));
      system::kill($pid);
    }
    system::kill('xiaomihomed.py');
    system::fuserk(config::byKey('socketport', 'xiaomihome2'));
  }

  public static function discover($_mode) {
    if ($_mode == 'wifi') {
      $value = json_encode(array('apikey' => jeedom::getApiKey('xiaomihome2'), 'cmd' => 'scanwifi'));
    } else {
      $value = json_encode(array('apikey' => jeedom::getApiKey('xiaomihome2'), 'cmd' => 'scanyeelight'));
    }
    $socket = socket_create(AF_INET, SOCK_STREAM, 0);
    socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'xiaomihome2'));
    socket_write($socket, $value, strlen($value));
    socket_close($socket);
  }

  public function get_wifi_info(){
    if ($this->getConfiguration('type') == 'wifi' && $this->getConfiguration('ipwifi') != ''){
      $value = json_encode(array('apikey' => jeedom::getApiKey('xiaomihome2'), 'type' => 'wifi','cmd' => 'discover', 'dest' => $this->getConfiguration('ipwifi') , 'token' => $this->getConfiguration('password') , 'model' => $this->getConfiguration('model')));
      $socket = socket_create(AF_INET, SOCK_STREAM, 0);
      socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'xiaomihome2'));
      socket_write($socket, $value, strlen($value));
      socket_close($socket);
    }
  }

  public function inclusion_mode(){
    $value = json_encode(array('apikey' => jeedom::getApiKey('xiaomihome2'), 'type' => 'aquara','cmd' => 'send', 'password' => $this->getConfiguration('password',''),'sidG' => $this->getConfiguration('sid'), 'dest' => $this->getConfiguration('gateway') , 'token' => $this->getConfiguration('password') , 'model' => $this->getConfiguration('model'), 'sid' => $this->getConfiguration('sid'), 'short_id' => $this->getConfiguration('short_id'),'switch' => 'join_permission', 'request' => 'yes'));
    $socket = socket_create(AF_INET, SOCK_STREAM, 0);
    socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'xiaomihome2'));
    socket_write($socket, $value, strlen($value));
    socket_close($socket);
  }

  public function exclusion_mode($target_sid){
    $value = json_encode(array('apikey' => jeedom::getApiKey('xiaomihome2'), 'type' => 'aquara','cmd' => 'send', 'password' => $this->getConfiguration('password',''),'sidG' => $this->getConfiguration('sid'), 'dest' => $this->getConfiguration('gateway') , 'token' => $this->getConfiguration('password') , 'model' => $this->getConfiguration('model'), 'sid' => $this->getConfiguration('sid'), 'short_id' => $this->getConfiguration('short_id'),'switch' => 'remove_device', 'request' => $target_sid));
    $socket = socket_create(AF_INET, SOCK_STREAM, 0);
    socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'xiaomihome2'));
    socket_write($socket, $value, strlen($value));
    socket_close($socket);
  }

  public function getImage() {
    if (file_exists(dirname(__FILE__) . '/../../core/config/devices/' . $this->getConfiguration('model') . '/' . $this->getConfiguration('model') . '.png')) {
      return 'plugins/xiaomihome2/core/config/devices/' . $this->getConfiguration('model') . '/' . $this->getConfiguration('model') . '.png';
    } else {
      return 'plugins/xiaomihome2/plugin_info/xiaomihome_icon.png';
    }
  }

  public function preSave() {
    if ($this->getLogicalId() != $this->getConfiguration('ipwifi') && $this->getConfiguration('ipwifi') != ''){
      $this->setLogicalId($this->getConfiguration('ipwifi'));
    }
    if ($this->getConfiguration('type') != 'aquara'){
      if ($this->getConfiguration('gateway') != $this->getConfiguration('ipwifi') && $this->getConfiguration('ipwifi') != ''){
        $this->setConfiguration('gateway',$this->getConfiguration('ipwifi'));
      }
    }
    if ($this->getConfiguration('type','') == ''){
      $this->setConfiguration('type','wifi');
    }
  }

  public function postSave() {
    if ($this->getConfiguration('type') == 'aquara' && $this->getConfiguration('applyDevice') != $this->getConfiguration('model')) {
      $this->applyModuleConfiguration($this->getConfiguration('model'));
    } else if ($this->getConfiguration('type') == 'yeelight' && $this->getConfiguration('applyDevice') != $this->getConfiguration('model')) {
      $this->applyModuleConfiguration($this->getConfiguration('model'));
    } else if ($this->getConfiguration('type') == 'wifi' && $this->getConfiguration('applyDevice2') != $this->getConfiguration('model')) {
      $this->applyModuleConfiguration($this->getConfiguration('applyDevice2'));
    }
  }

  public static function devicesParameters($_device = '') {
    $return = array();
    foreach (ls(dirname(__FILE__) . '/../config/devices', '*') as $dir) {
      $path = dirname(__FILE__) . '/../config/devices/' . $dir;
      if (!is_dir($path)) {
        continue;
      }
      $files = ls($path, '*.json', false, array('files', 'quiet'));
      foreach ($files as $file) {
        try {
          $content = file_get_contents($path . '/' . $file);
          if (is_json($content)) {
            $return += json_decode($content, true);
          }
        } catch (Exception $e) {
        }
      }
    }
    if (isset($_device) && $_device != '') {
      if (isset($return[$_device])) {
        return $return[$_device];
      }
      return array();
    }
    return $return;
  }

  public function applyModuleConfiguration($model) {
    $this->setConfiguration('applyDevice', $model);
    $this->setConfiguration('applyDevice2', $model);
    $this->setConfiguration('model',$model);
    $this->save();
    //$this->import($device);
    if ($this->getConfiguration('model') == '') {
      return true;
    }
    $device = self::devicesParameters($model);
    if (!is_array($device)) {
      return true;
    }
    event::add('jeedom::alert', array(
      'level' => 'warning',
      'page' => 'xiaomihome2',
      'message' => __('Périphérique reconnu, intégration en cours...', __FILE__),
    ));
    if (isset($device['configuration'])) {
      foreach ($device['configuration'] as $key => $value) {
        $this->setConfiguration($key, $value);
      }
    }
    if (isset($device['category'])) {
      foreach ($device['category'] as $key => $value) {
        $this->setCategory($key, $value);
      }
    }
    $cmd_order = 0;
    $link_cmds = array();
    $link_actions = array();
    event::add('jeedom::alert', array(
      'level' => 'warning',
      'page' => 'xiaomihome2',
      'message' => __('Création des commandes...', __FILE__),
    ));

    $ids = array();
    $arrayToRemove = [];
    if (isset($device['commands'])) {
      foreach ($this->getCmd() as $eqLogic_cmd) {
        $exists = 0;
        foreach ($device['commands'] as $command) {
          if ($command['logicalId'] == $eqLogic_cmd->getLogicalId()) {
            $exists++;
          }
        }
        if ($exists < 1) {
          $arrayToRemove[] = $eqLogic_cmd;
        }
      }
      foreach ($arrayToRemove as $cmdToRemove) {
        try {
          $cmdToRemove->remove();
        } catch (Exception $e) {

        }
      }
      foreach ($device['commands'] as $command) {
        $cmd = null;
        foreach ($this->getCmd() as $liste_cmd) {
          if ((isset($command['logicalId']) && $liste_cmd->getLogicalId() == $command['logicalId'])
          || (isset($command['name']) && $liste_cmd->getName() == $command['name'])) {
            $cmd = $liste_cmd;
            break;
          }
        }
        try {
          if ($cmd == null || !is_object($cmd)) {
            $cmd = new xiaomihome2Cmd();
            $cmd->setOrder($cmd_order);
            $cmd->setEqLogic_id($this->getId());
          } else {
            $command['name'] = $cmd->getName();
            if (isset($command['display'])) {
              unset($command['display']);
            }
          }
          utils::a2o($cmd, $command);
          $cmd->setConfiguration('logicalId', $cmd->getLogicalId());
          $cmd->save();
          if (isset($command['value'])) {
            $link_cmds[$cmd->getId()] = $command['value'];
          }
          if (isset($command['configuration']) && isset($command['configuration']['updateCmdId'])) {
            $link_actions[$cmd->getId()] = $command['configuration']['updateCmdId'];
          }
          $cmd_order++;
        } catch (Exception $exc) {

        }
      }
    }

    if (count($link_cmds) > 0) {
      foreach ($this->getCmd() as $eqLogic_cmd) {
        foreach ($link_cmds as $cmd_id => $link_cmd) {
          if ($link_cmd == $eqLogic_cmd->getName()) {
            $cmd = cmd::byId($cmd_id);
            if (is_object($cmd)) {
              $cmd->setValue($eqLogic_cmd->getId());
              $cmd->save();
            }
          }
        }
      }
    }
    if (count($link_actions) > 0) {
      foreach ($this->getCmd() as $eqLogic_cmd) {
        foreach ($link_actions as $cmd_id => $link_action) {
          if ($link_action == $eqLogic_cmd->getName()) {
            $cmd = cmd::byId($cmd_id);
            if (is_object($cmd)) {
              $cmd->setConfiguration('updateCmdId', $eqLogic_cmd->getId());
              $cmd->save();
            }
          }
        }
      }
    }
    $this->save();
    if (isset($device['afterInclusionSend']) && $device['afterInclusionSend'] != '') {
      event::add('jeedom::alert', array(
        'level' => 'warning',
        'page' => 'xiaomihome2',
        'message' => __('Envoi des commandes post-inclusion...', __FILE__),
      ));
      sleep(5);
      $sends = explode('&&', $device['afterInclusionSend']);
      foreach ($sends as $send) {
        foreach ($this->getCmd('action') as $cmd) {
          if (strtolower($cmd->getName()) == strtolower(trim($send))) {
            $cmd->execute();
          }
        }
        sleep(1);
      }

    }
    sleep(2);
    event::add('jeedom::alert', array(
      'level' => 'warning',
      'page' => 'xiaomihome2',
      'message' => '',
    ));
  }
  public static function receiveAquaraData($id, $model, $key, $value) {
    $xiaomihome2 = self::byLogicalId($id, 'xiaomihome2');
    if (is_object($xiaomihome2)) {
      if ($key == 'humidity' || $key == 'temperature' || $key == 'pressure') {
        $value = $value / 100;
      }
      else if ($key == 'rotate') {
        $mvalues = explode(',',$value);
        $xiaomihome2->checkAndUpdateCmd('rotatetime', $mvalues[1]);
        $value = $mvalues[0] * 3.6;
        if ($value  > 0) {
          $xiaomihome2->checkAndUpdateCmd('status', 'rotate_right');
        } else {
          $xiaomihome2->checkAndUpdateCmd('status', 'rotate_left');
        }
      }
      else if ($key == 'rgb') {
        $value = str_pad(dechex($value), 8, "0", STR_PAD_LEFT);
        $light = hexdec(substr($value, 0, 2));
        $value = '#' . substr($value, -6);
        $xiaomihome2->checkAndUpdateCmd('brightness', $light);
        $xiaomihome2->checkAndUpdateCmd('rgb', $value);
      }
      else if ($key == 'voltage') {
        if ($model != 'plug' && $model != 'gateway' && $model != 'natgas') {
          if ($value>=3100) {
            $battery = 100;
          } else if ($value<3100 && $value>2800) {
            $battery = ceil(($value - 2800) *0.33);
          } else {
            $battery = 1;
          }
          $xiaomihome2->checkAndUpdateCmd('battery', $battery);
          $xiaomihome2->setConfiguration('battery',$battery);
          $xiaomihome2->batteryStatus($battery);
          $xiaomihome2->save();
        }

        $value = $value /1000;
      }
      else if ($key == 'density') {
        if ($model == 'smoke') {
          if ($value > 1000) {
            $visibility = 0;
          } else {
            $visibility = 100 - $value/10;
          }
          $xiaomihome2->checkAndUpdateCmd('visibility', $visibility);
        }
      }
      else if ($key == 'power_consumed') {
        $value = $value /1000;
      }
      else if ($key == 'no_motion') {
        $xiaomihome2->checkAndUpdateCmd('status', 0);
      }
      else if ($key == 'no_close') {
        $xiaomihome2->checkAndUpdateCmd('status', 1);
      }
      else if (($key == 'channel_0' || $key == 'channel_1') && ($model == 'ctrl_neutral1' || $model == 'ctrl_neutral2' || $model == 'ctrl_ln1.aq1' || $model == 'ctrl_ln2.aq1')) {
        $value = ($value == 'on') ? 1 : 0;
      }
      else if ($key == 'curtain_level') {
        $curtain_level = xiaomihome2Cmd::byEqLogicIdAndLogicalId($xiaomihome2->getId(),'curtain_level');
        if ($curtain_level->getConfiguration('invert') == 1) {
          $value = 100 - $value;
        }
      }
      else if ($key == 'status') {
        if ($model == 'motion' || $model == 'sensor_motion.aq2') {
          if ($value == 'motion') {
            $xiaomihome2->checkAndUpdateCmd('no_motion', 0);
            $value = 1;
          } else {
            $value = 0;
          }
        }
        else if ($model == 'magnet' || $model == 'sensor_magnet.aq2') {
          if ($value == 'open') {
            $value = 1;
          } else {
            $value = 0;
            $xiaomihome2->checkAndUpdateCmd('no_close', 0);
          }
        }
        else if ($model == 'sensor_wleak.aq1') {
          $value = ($value == 'leak') ? 1 : 0;
        }
        else if ($model == 'plug' || $model == '86plug') {
          $value = ($value == 'on') ? 1 : 0;
        }
      }
      $xiaomihome2->checkAndUpdateCmd($key, $value);
      /*$xiaomihome2Cmd = xiaomihome2Cmd::byEqLogicIdAndLogicalId($xiaomihome2->getId(),$key);
      if (is_object($xiaomihome2Cmd)) {
      $xiaomihome2Cmd->setConfiguration('value',$value);
      $xiaomihome2Cmd->save();
      $xiaomihome2Cmd->event($value);
    }*/
  }
}

public function pingHost($host, $timeout = 1) {
  exec(system::getCmdSudo() . "ping -c1 " . $host, $output, $return_var);
  if ($return_var == 0) {
    $result = true;
    $this->checkAndUpdateCmd('online', 1);
  } else {
    $result = false;
    $this->checkAndUpdateCmd('online', 0);
  }
  return $result;
}

public static function sendDaemon($value) {
  log::add('xiaomihome2', 'debug', 'Envoi : ' . $value);
  $deamon_info = self::deamon_info();
  if ($deamon_info['state'] != 'ok') {
    return;
  }
  $socket = socket_create(AF_INET, SOCK_STREAM, 0);
  socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'xiaomihome2'));
  socket_write($socket, $value, strlen($value));
  socket_close($socket);
}

}

class xiaomihome2Cmd extends cmd {
  public function execute($_options = null) {
    if ($this->getType() == 'info') {
      return $this->getConfiguration('value');
    } else {
      $eqLogic = $this->getEqLogic();
      log::add('xiaomihome2', 'debug', 'execute : ' . $this->getType() . ' ' . $eqLogic->getConfiguration('type') . ' ' . $this->getLogicalId());
      switch ($eqLogic->getConfiguration('type')) {
        case 'yeelight':
        $this->executeYeelight($_options);
        break;
        case 'aquara':
        $this->executeAqara($_options);
        break;
        case 'wifi':
        $this->executeWifi($_options);
        break;
        case 'miio':
        $this->executeMiio($_options);
        break;
      }
    }
  }

  public function executeYeelight($_options = null) {
    $eqLogic = $this->getEqLogic();
    if ($eqLogic->pingHost($eqLogic->getConfiguration('gateway')) == false) {
      log::add('xiaomihome2', 'debug', 'Equipement Yeelight déconnecté : ' . $eqLogic->getName());
      return;
    }
    switch ($this->getSubType()) {
      case 'slider':
      $option = $_options['slider'];
      if ($this->getLogicalId() == 'hsvAct') {
        $cplmtcmd = xiaomihome2Cmd::byEqLogicIdAndLogicalId($eqLogic->getId(),'saturation');
        $option = $option . ' ' . $cplmtcmd->execCmd();
      }
      if ($this->getLogicalId() == 'saturationAct') {
        $cplmtcmd = xiaomihome2Cmd::byEqLogicIdAndLogicalId($eqLogic->getId(),'hsv');
        $option = $cplmtcmd->execCmd() . ' ' . $option;
      }
      log::add('xiaomihome2', 'debug', 'Slider : ' . $option);
      break;
      case 'color':
      $option = str_replace('#','',$_options['color']);
      break;
      case 'message':
      if ($this->getLogicalId() == 'mid-scenar') {
        $eqLogic->checkAndUpdateCmd('vol', $_options['message']);
      }
      $option = $_options['title'];
      break;
      default :
      $option = '';
      break;
    }
    $sup = '';
    if ($eqLogic->getConfiguration('model','') == 'desklamp'){
      $brightCmd = xiaomihome2Cmd::byEqLogicIdAndLogicalId($eqLogic->getId(),'brightness');
      $sup = $brightCmd->execCmd();
    }
    if ($this->getLogicalId() != 'refresh') {
      if ($option == '000000') {
        $request ='turn off';
      } else {
        $request =$this->getConfiguration('request');
      }
      $value = json_encode(array('apikey' => jeedom::getApiKey('xiaomihome2'), 'type' => 'yeelight','cmd' => 'send', 'dest' => $eqLogic->getConfiguration('gateway') , 'model' => $eqLogic->getConfiguration('model'), 'sid' => $eqLogic->getConfiguration('sid'), 'short_id' => $eqLogic->getConfiguration('short_id'),'command' => $request, 'option' => $option, 'id' => $eqLogic->getLogicalId(), 'sup' => $sup));
      xiaomihome2::sendDaemon($value);
    } else {
      $value = json_encode(array('apikey' => jeedom::getApiKey('xiaomihome2'), 'type' => 'yeelight','cmd' => 'refresh', 'model' => $eqLogic->getConfiguration('model'), 'dest' => $eqLogic->getConfiguration('gateway') , 'token' => $eqLogic->getConfiguration('password') , 'devtype' => $eqLogic->getConfiguration('short_id'), 'serial' => $eqLogic->getConfiguration('sid'), 'id' => $eqLogic->getLogicalId()));
      xiaomihome2::sendDaemon($value);
      return;
    }
  }

  public function executeAqara($_options = null) {
    $eqLogic = $this->getEqLogic();
    if ($this->getLogicalId() == 'refresh') {
      $gateway = $eqLogic->getConfiguration('gateway');
      $xiaomihome2 = $eqLogic->byLogicalId($gateway, 'xiaomihome2');
      if ($xiaomihome2->pingHost($gateway) == false) {
        log::add('xiaomihome2', 'debug', 'Offline Aqara : ' . $xiaomihome2->getName());
        return;
      }
      $password = $xiaomihome2->getConfiguration('password','');
      $value = json_encode(array('apikey' => jeedom::getApiKey('xiaomihome2'), 'type' => 'aquara','model' => 'read','cmd' => 'refresh', 'dest' => $gateway , 'password' => $password,'sidG' => $xiaomihome2->getConfiguration('sid'), 'sid' => $eqLogic->getConfiguration('sid')));
      xiaomihome2::sendDaemon($value);
      return;
    }
    switch ($this->getSubType()) {
      case 'color':
      $option = $_options['color'];
      if ($this->getConfiguration('switch') == 'rgb') {
        $xiaomihome2Cmd = xiaomihome2Cmd::byEqLogicIdAndLogicalId($eqLogic->getId(),'brightness');
        $bright = str_pad(dechex($xiaomihome2Cmd->execCmd()), 2, "0", STR_PAD_LEFT);
        $couleur = str_replace('#','',$option);
        if ($couleur == '000000') {
          $bright = '00';
        } else {
          if ($bright == '00') {
            $bright = dechex(50);
          }
        }
        $eqLogic->checkAndUpdateCmd('rgb', $_options['color']);
        $rgbcomplet = $bright . $couleur;
        $option = hexdec($rgbcomplet);
      }
      break;
      case 'slider':
      $option = $_options['slider'];
      //$option = dechex($_options['slider']);
      if ($this->getConfiguration('switch') == 'rgb') {
        $xiaomihome2Cmd = xiaomihome2Cmd::byEqLogicIdAndLogicalId($eqLogic->getId(),'rgb');
        $couleur = str_replace('#','',$xiaomihome2Cmd->execCmd());
        $bright = str_pad($option, 2, "0", STR_PAD_LEFT);
        $eqLogic->checkAndUpdateCmd('brightness', $_options['slider']);
        $rgbcomplet = dechex($bright) . $couleur;
        $option = hexdec($rgbcomplet);
      }
      if ($this->getConfiguration('switch') == 'vol') {
        $eqLogic->checkAndUpdateCmd('vol', $_options['slider']);
      }
      break;
      case 'message':
      $option = $_options['title'];
      break;
      case 'select':
      $option = $_options['select'];
      break;
      default :
      if ($this->getConfiguration('switch') == 'rgb') {
        if ($this->getLogicalId() == 'on') {
          $xiaomihome2Cmd = xiaomihome2Cmd::byEqLogicIdAndLogicalId($eqLogic->getId(),'rgb');
          $couleur = str_replace('#','',$xiaomihome2Cmd->execCmd());
          $rgbcomplet = dechex(50) . $couleur;
          $option = hexdec($rgbcomplet);
          $eqLogic->checkAndUpdateCmd('brightness', '50');
        } else {
          $xiaomihome2Cmd = xiaomihome2Cmd::byEqLogicIdAndLogicalId($eqLogic->getId(),'rgb');
          $couleur = str_replace('#','',$xiaomihome2Cmd->execCmd());
          $rgbcomplet = dechex(00) . $couleur;
          $option = hexdec($rgbcomplet);
          $eqLogic->checkAndUpdateCmd('brightness', '00');
        }
      } else {
        $option = $this->getConfiguration('request');
      }
      break;
    }

    if (strpos($eqLogic->getConfiguration('model'),'curtain_level') !== false) {
      $curtain_level = xiaomihome2Cmd::byEqLogicIdAndLogicalId($eqLogic->getId(),'curtain_level');
      if ($curtain_level->getConfiguration('invert') == 1) {
        if ($option == 'open') {
          $option = 'close';
        } elseif ($option == 'close') {
          $option = 'open';
        } else {
          $option = 100 - $option;
        }
      }
    }

    $gateway = $eqLogic->getConfiguration('gateway');
    $xiaomihome2 = $eqLogic->byLogicalId($gateway, 'xiaomihome2');
    if ($xiaomihome2->pingHost($gateway) == false) {
      log::add('xiaomihome2', 'debug', 'Offline Aqara : ' . $xiaomihome2->getName());
      return;
    }
    $password = $xiaomihome2->getConfiguration('password','');
    if ($password == '') {
      log::add('xiaomihome2', 'debug', 'Mot de passe manquant sur la passerelle Aqara ' . $gateway);
      return;
    }
    if ($this->getLogicalId() == 'mid-scenar') {
      $volume = intval($_options['message']);
    } else {
      $vol = xiaomihome2Cmd::byEqLogicIdAndLogicalId($xiaomihome2->getId(),'vol');
      $volume = $vol->execCmd();
    }
    if (is_numeric($option)) {
      $option = intval($option);
    }
    $value = json_encode(array('apikey' => jeedom::getApiKey('xiaomihome2'), 'type' => 'aquara','cmd' => 'send', 'dest' => $gateway , 'password' => $password , 'model' => $eqLogic->getConfiguration('model'),'sidG' => $xiaomihome2->getConfiguration('sid'), 'sid' => $eqLogic->getConfiguration('sid'), 'short_id' => $eqLogic->getConfiguration('short_id'),'switch' => $this->getConfiguration('switch'), 'request' => $option, 'vol'=> $volume ));
    xiaomihome2::sendDaemon($value);
  }

  public function executeWifi($_options = null) {
    $eqLogic = $this->getEqLogic();
    if ($eqLogic->pingHost($eqLogic->getConfiguration('ipwifi')) == false) {
      log::add('xiaomihome2', 'debug', 'Offline Wifi : ' . $eqLogic->getName());
      return;
    }
    if ($this->getLogicalId() == 'refresh') {
      $value = json_encode(array('apikey' => jeedom::getApiKey('xiaomihome2'), 'type' => 'wifi','cmd' => 'refresh', 'model' => $eqLogic->getConfiguration('model'), 'dest' => $eqLogic->getConfiguration('gateway') , 'token' => $eqLogic->getConfiguration('password') , 'devtype' => $eqLogic->getConfiguration('short_id'), 'serial' => $eqLogic->getConfiguration('sid')));
      xiaomihome2::sendDaemon($value);
      return;
    }
    $params = $this->getConfiguration('params');
    switch ($this->getSubType()) {
      case 'color':
      $option = $_options['color'];
      break;
      case 'slider':
      $option = $_options['slider'];
      $params = trim(str_replace('#slider#',$option, $params));
      break;
      case 'message':
      $option = $_options['message'];
      $params = trim(str_replace('#message#',$option, $params));
      break;
      case 'select':
      $option = $_options['select'];
      break;
      default :
      $option = '';
    }
    $value = json_encode(array('apikey' => jeedom::getApiKey('xiaomihome2'), 'type' => 'wifi','cmd' => 'send', 'model' => $eqLogic->getConfiguration('model'), 'dest' => $eqLogic->getConfiguration('gateway') , 'token' => $eqLogic->getConfiguration('password') , 'devtype' => $eqLogic->getConfiguration('short_id'), 'serial' => $eqLogic->getConfiguration('sid'), 'method' => $this->getConfiguration('request'),'param' => $params));
    xiaomihome2::sendDaemon($value);
  }

  public function executeMiio($_options = null) {

  }
}

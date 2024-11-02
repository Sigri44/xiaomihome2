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
function xiaomihome2_update() {
	foreach (eqLogic::byType('xiaomihome2') as $xiaomihome2) {
        if ($xiaomihome2->getConfiguration('type') == 'aquara') {
            $xiaomihome2Cmd = xiaomihome2Cmd::byEqLogicIdAndLogicalId($xiaomihome2->getId(),'refresh');
            if (!is_object($xiaomihome2Cmd)) {
                log::add('xiaomihome2', 'debug', 'Création de la commande Rafraichir Aqara');
                $xiaomihome2Cmd = new xiaomihome2Cmd();
                $xiaomihome2Cmd->setName(__('Rafraichir', __FILE__));
                $xiaomihome2Cmd->setEqLogic_id($xiaomihome2->getId());
                $xiaomihome2Cmd->setEqType('xiaomihome2');
                $xiaomihome2Cmd->setLogicalId('refresh');
                $xiaomihome2Cmd->setType('action');
                $xiaomihome2Cmd->setSubType('other');
                $xiaomihome2Cmd->setConfiguration('switch', 'read');
                $xiaomihome2Cmd->setIsVisible('0');
                $xiaomihome2Cmd->setDisplay('generic_type', 'DONT');
                $xiaomihome2Cmd->save();
            }
        }
        $xiaomihome2->setConfiguration('applyDevice',$xiaomihome2->getConfiguration('model'));
        $xiaomihome2->save();
	}
}
?>

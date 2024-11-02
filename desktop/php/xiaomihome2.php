<?php

if (!isConnect('admin')) {
  throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('xiaomihome2');
sendVarToJS('eqType', 'xiaomihome2');
$eqLogics = eqLogic::byType('xiaomihome2');
$eqLogicsBlea = array();
if (class_exists('blea')){
  $eqLogicsBlea = eqLogic::byType('blea');
}
?>

<div class="row row-overflow">
  <div class="col-lg-2 col-sm-3 col-sm-4" id="hidCol" style="display: none;">
    <div class="bs-sidebar">
      <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
        <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
        <?php
        foreach ($eqLogics as $eqLogic) {
          echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
        }
        ?>
      </ul>
    </div>
  </div>

  <div class="col-lg-12 eqLogicThumbnailDisplay" id="listCol">
    <legend><i class="fas fa-cog"></i>  {{Gestion}}</legend>
    <div class="eqLogicThumbnailContainer logoPrimary">
      <div class="cursor eqLogicAction logoSecondary" data-action="add">
          <i class="fas fa-plus-circle"></i>
          <br/>
        <span>Ajouter</span>
      </div>
      <div class="cursor eqLogicAction logoSecondary discover" data-action="scanyeelight">
		<i class="far fa-lightbulb"></i>
        <br/>
        <span>{{Scan Yeelight}}</span>
      </div>
      <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
          <i class="fas fa-wrench"></i>
        <br/>
        <span>{{Configuration}}</span>
      </div>
      <div class="cursor logoSecondary" id="bt_healthxiaomihome2">
          <i class="fas fa-medkit"></i>
        <br/>
        <span>{{Santé}}</span>
      </div>
    </div>

    <input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic" />

    <legend><i class="fas fa-home" id="butCol"></i>  {{Mes Aqara}}</legend>
    <?php
    $status = 0;
    foreach ($eqLogics as $eqLogicGateway) {
      $opacity = ($eqLogicGateway->getIsEnable()) ? '' : 'disableCard';
      if ($eqLogicGateway->getConfiguration('type') == 'aquara' && $eqLogicGateway->getConfiguration('model') == 'gateway') {
        echo '<legend>' . $eqLogicGateway->getHumanName(true) . ' <i class="inclusion fas fa-plus-circle cursor" title="Inclure un module sur cette gateway" data-id="' . $eqLogicGateway->getId() . '" style="color:#00979c !important"></i></legend>';
		echo '<div class="eqLogicThumbnailContainer">';
        $status = 1;
        $opacity = ($eqLogicGateway->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
        echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogicGateway->getId() . '">';
        $online = $eqLogicGateway->getCmd('info','online');
        if (is_object($online)){
          $onlinevalue= $online->execCmd();
        } else {
          $onlinevalue = '';
        }
        if ($onlinevalue !== '' && $onlinevalue == 0){
          echo '<i class="fas fa-times" style="font-size:0.9em !important;float:right" title="Offline"></i>';
        }
        if (file_exists(dirname(__FILE__) . '/../../core/config/devices/' . $eqLogicGateway->getConfiguration('model') . '/' . $eqLogicGateway->getConfiguration('model') . '.png')) {
          echo '<img src="plugins/xiaomihome2/core/config/devices/' . $eqLogicGateway->getConfiguration('model') . '/' . $eqLogicGateway->getConfiguration('model') . '.png' . '"/>';
        } else {
          echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
        }
        echo '<br/>';
        echo '<span class="name">' . $eqLogicGateway->getHumanName(true, true) . '</span>';
        echo '</div>';
        foreach ($eqLogics as $eqLogic) {
			$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
          if ($eqLogic->getConfiguration('type') == 'aquara' && $eqLogic->getConfiguration('model') != 'gateway' && $eqLogic->getConfiguration('gateway') == $eqLogicGateway->getConfiguration('gateway')) {
            echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
            if ($onlinevalue !== '' && $onlinevalue == 0){
              echo '<i class="fas fa-times" style="font-size:0.9em !important;float:right" title="Offline"></i>';
            }
            if (file_exists(dirname(__FILE__) . '/../../core/config/devices/' . $eqLogic->getConfiguration('model') . '/' . $eqLogic->getConfiguration('model') . '.png')) {
              echo '<img src="plugins/xiaomihome2/core/config/devices/' . $eqLogic->getConfiguration('model') . '/' . $eqLogic->getConfiguration('model') . '.png' . '"/>';
            } else {
              echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
            }
            echo '<br/>';
			echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
            echo '</div>';
          }
        }
        echo '</div>';
      }
    }
    if ($status == 0) {
      echo "<br/><br/><br/><center><span style='color:#767676;font-size:1.2em;font-weight: bold;'>{{Aucun équipement Aqara détecté. Démarrez un node pour en ajouter un.}}</span></center>";
    }
    ?>

    <legend><i class="icon jeedom2-bright4"></i>  {{Mes Yeelight}}</legend>
    <?php
    $status = 0;
    foreach ($eqLogics as $eqLogic) {
      if ($eqLogic->getConfiguration('type') == 'yeelight') {
        if ($status == 0) {echo '<div class="eqLogicThumbnailContainer">';}
        $status = 1;
		$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
         echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
        $online = $eqLogic->getCmd('info','online');
        if (is_object($online)){
          $onlinevalue= $online->execCmd();
        } else {
          $onlinevalue = '';
        }
        if ($onlinevalue !== '' && $onlinevalue == 0){
          echo '<i class="fas fa-times" style="font-size:0.9em !important;float:right" title="Offline"></i>';
        }
        if (file_exists(dirname(__FILE__) . '/../../core/config/devices/' . $eqLogic->getConfiguration('model') . '/' . $eqLogic->getConfiguration('model') . '.png')) {
          echo '<img src="plugins/xiaomihome2/core/config/devices/' . $eqLogic->getConfiguration('model') . '/' . $eqLogic->getConfiguration('model') . '.png' . '" />';
        } else {
          echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
        }

		echo '<br/>';
        echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
        echo '</div>';
      }
    }
    if ($status == 1) {
      echo '</div>';
    } else {
      echo "<br/><br/><br/><center><span style='color:#767676;font-size:1.2em;font-weight: bold;'>{{Aucun équipement Yeelight détecté. Lancez un Scan Yeelight.}}</span></center>";
    }
    ?>

    <legend><i class="fas fa-wifi"></i>  {{Mes Xiaomi Wifi}}</legend>
    <?php
    $status = 0;
    foreach ($eqLogics as $eqLogic) {
      if ($eqLogic->getConfiguration('type') == 'wifi') {
        if ($status == 0) {echo '<div class="eqLogicThumbnailContainer">';}
        $status = 1;
			$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
         echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
        $online = $eqLogic->getCmd('info','online');
        if (is_object($online)){
          $onlinevalue= $online->execCmd();
        } else {
          $onlinevalue = '';
        }
        if ($onlinevalue !== '' && $onlinevalue == 0){
          echo '<i class="fas fa-times" style="font-size:0.9em !important;float:right" title="Offline"></i>';
        }
		echo '<br/>';
		 if (file_exists(dirname(__FILE__) . '/../../core/config/devices/' . $eqLogic->getConfiguration('model') . '/' . $eqLogic->getConfiguration('model') . '.png')) {
				echo '<img src="plugins/xiaomihome2/core/config/devices/' . $eqLogic->getConfiguration('model') . '/' . $eqLogic->getConfiguration('model') . '.png' . '"/>';
		 } else {
          echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
        }
        echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
        echo '</div>';
      }
    }
    if ($status == 1) {
      echo '</div>';
    } else {
      echo "<br/><br/><br/><center><span style='color:#767676;font-size:1.2em;font-weight: bold;'>{{Aucun équipement Xiaomi Wifi. Ajoutez-en un.}}</span></center>";
    }
    ?>
  </div>

  <div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
    <div class="input-group pull-right" style="display:inline-flex">
			<span class="input-group-btn">
				<a class="btn btn-primary btn-sm eqLogicAction syncinfo roundedLeft" id="btn_sync"><i class="fas fa-spinner" title="{{Récupérer les infos}}"></i> {{Récupérer les infos}}</a><a class="btn btn-warning btn-sm" id="bt_autoDetectModule"><i class="fas fa-search" title="{{Recréer les commandes}}"></i>  {{Recréer les commandes}}</a><a class="btn btn-default btn-sm eqLogicAction" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancée}}</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a><a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
			</span>
		</div>
    <ul class="nav nav-tabs" role="tablist">
      <li role="presentation"><a class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
      <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
      <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Commandes}}</a></li>
    </ul>
    <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
      <div role="tabpanel" class="tab-pane active" id="eqlogictab">
      </br>
      <div class="row">
        <div class="col-sm-6">
          <form class="form-horizontal">
            <fieldset>
              <div class="form-group">
                <label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
                <div class="col-sm-6">
                  <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                  <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement xiaomihome2}}"/>
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-3 control-label" >{{Objet parent}}</label>
                <div class="col-sm-6">
                  <select class="form-control eqLogicAttr" data-l1key="object_id">
                    <option value="">{{Aucun}}</option>
                    <?php
                    foreach (jeeObject::all() as $object) {
                      echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                    }
                    ?>
                  </select>
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-3 control-label">{{Catégorie}}</label>
                <div class="col-sm-8">
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
                <label class="col-sm-3 control-label" ></label>
                <div class="col-sm-8">
                  <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
                  <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
                </div>
              </div>
              <div class="form-group" id="ipfield">
                <label class="col-sm-3 control-label">{{Adresse IP}}</label>
                <div class="col-sm-6">
                  <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="ipwifi" placeholder="Ip du device wifi"></span>
                </div>
              </div>
              <div class="form-group" id="passfield">
                <label class="col-sm-3 control-label" id="passtoken">{{Password/Token}}</label>
                <div class="col-sm-6">
                  <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="password" placeholder="Voir message en bleu"></span>
                </div>
              </div>
            </fieldset>
          </form>
        </div>
        <div class="col-sm-6">
          <form class="form-horizontal">
            <fieldset>
              <div class="form-group" id="newmodelfield2">
                <label class="col-sm-3 control-label">{{Equipement}}</label>
                <div class="col-sm-8">
                  <select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="applyDevice2">
                    <?php
                    $groups = array();

                    foreach (xiaomihome2::devicesParameters() as $key => $info) {
                      if (isset($info['groupe'])) {
                        $info['key'] = $key;
                        if (!isset($groups[$info['groupe']])) {
                          $groups[$info['groupe']][0] = $info;
                        } else {
                          array_push($groups[$info['groupe']], $info);
                        }
                      }
                    }
                    ksort($groups);
                    foreach ($groups as $group) {
                      usort($group, function ($a, $b) {
                        return strcmp($a['name'], $b['name']);
                      });
                      foreach ($group as $key => $info) {
                        if ($info['groupe'] == 'Aquara') {
                          break;
                        }
                        if ($key == 0) {
                          echo '<optgroup label="{{' . $info['groupe'] . '}}">';
                        }
                        echo '<option value="' . $info['key'] . '">' . $info['name'] . '</option>';
                      }
                      echo '</optgroup>';
                    }
                    ?>
                  </select>
                </div>
              </div>
              <div class="form-group" id="newmodelfield">
                <label class="col-sm-3 control-label">{{Equipement}}</label>
                <div class="col-sm-8">
                  <select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="applyDevice">
                    <?php
                    $groups = array();

                    foreach (xiaomihome2::devicesParameters() as $key => $info) {
                      if (isset($info['groupe'])) {
                        $info['key'] = $key;
                        if (!isset($groups[$info['groupe']])) {
                          $groups[$info['groupe']][0] = $info;
                        } else {
                          array_push($groups[$info['groupe']], $info);
                        }
                      }
                    }
                    ksort($groups);
                    foreach ($groups as $group) {
                      usort($group, function ($a, $b) {
                        return strcmp($a['name'], $b['name']);
                      });
                      foreach ($group as $key => $info) {
                        if ($key == 0) {
                          echo '<optgroup label="{{' . $info['groupe'] . '}}">';
                        }
                        echo '<option value="' . $info['key'] . '">' . $info['name'] . '</option>';
                      }
                      echo '</optgroup>';
                    }
                    ?>
                  </select>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-3 control-label">{{Type}}</label>
                <div class="col-sm-3">
                  <span class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="type" id="typefield"></span>
                </div>
                <label class="col-sm-3 control-label">{{Modèle}}</label>
                <div class="col-sm-3">
                  <span class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="model" id="modelfield"></span>
                </div>
              </div>

              <div class="form-group"  id="idfield">
                <label class="col-sm-3 control-label">{{Identifiant}}</label>
                <div class="col-sm-3">
                  <span class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="sid" id="sid"></span>
                </div>
                <label class="col-sm-3 control-label">{{Identifiant court}}</label>
                <div class="col-sm-3">
                  <span class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="short_id"></span>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-3 control-label">{{Gateway}}</label>
                <div class="col-sm-3">
                  <span class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="gateway"></span>
                </div>
                <label class="col-sm-3 control-label">{{Dernière Activité}}</label>
                <div class="col-sm-3">
                  <span class="eqLogicAttr label label-default" data-l1key="status" data-l2key="lastCommunication"></span>
                </div>
              </div>

              <center>
                <img src="core/img/no_image.gif" data-original=".jpg" id="img_device" class="img-responsive" style="max-height : 250px;"  onerror="this.src='plugins/xiaomihome2/plugin_info/xiaomihome2_icon.png'"/>
              </center>
			  <br/>
            </fieldset>
          </form>
          <div class="alert alert-info globalRemark" style="display:none">{{Veuillez renseigner l'IP, puis sauvegardez. Ensuite, il vous suffit de cliquer sur "Récupérer les infos". Si l'équipement est trouvé, les identifiants et le token seront également trouvés. Certains équipements (aspirateur, plafonnier Xiaomi, cuiseur à riz petit format ...) sont une exception : Dans ce cas, il faut récupérer le token avant. Veuillez vous référer à la doc. Pour la gateway, il suffit juste de la récupérer dans les options développeurs de Mi Home.}}</div>
        </div>
      </div>
    </div>
    <div role="tabpanel" class="tab-pane" id="commandtab">

      <table id="table_cmd" class="table table-bordered table-condensed">
        <thead>
          <tr>
            <th style="width: 50px;">#</th>
            <th style="width: 250px;">{{Nom}}</th>
            <th style="width: 100px;">{{Type}}</th>
            <th style="width: 100px;">{{Unité}}</th>
            <th style="width: 150px;">{{Paramètres}}</th>
            <th style="width: 100px;"></th>
          </tr>
        </thead>
        <tbody>

        </tbody>
      </table>

    </div>
  </div>
</div>
</div>

<?php include_file('desktop', 'xiaomihome2', 'js', 'xiaomihome2'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>

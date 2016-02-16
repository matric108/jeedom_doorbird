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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class doorbird extends eqLogic {

  public function preUpdate() {
    if ($this->getConfiguration('addr') == '') {
      throw new Exception(__('L\'adresse ne peut être vide',__FILE__));
    }
  }

  public function preSave() {
    $this->setLogicalId($this->getConfiguration('addr'));
  }


  public function postUpdate() {
    $cmd = doorbirdCmd::byEqLogicIdAndLogicalId($this->getId(),'light');
		if (!is_object($cmd)) {
			$cmd = new doorbirdCmd();
			$cmd->setLogicalId('light');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Lumière', __FILE__));
		}
		$cmd->setType('action');
		$cmd->setSubType('other');
    $cmd->setConfiguration('url','light-on.cgi');
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

    $cmd = doorbirdCmd::byEqLogicIdAndLogicalId($this->getId(),'door');
		if (!is_object($cmd)) {
			$cmd = new doorbirdCmd();
			$cmd->setLogicalId('door');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Ouverture Porte', __FILE__));
		}
		$cmd->setType('action');
		$cmd->setSubType('other');
    $cmd->setConfiguration('url','open-door.cgi');
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

    $cmd = doorbirdCmd::byEqLogicIdAndLogicalId($this->getId(),'doorbell');
		if (!is_object($cmd)) {
			$cmd = new doorbirdCmd();
			$cmd->setLogicalId('doorbell');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Sonnerie', __FILE__));
		}
		$cmd->setType('info');
		$cmd->setSubType('binary');
    $cmd->setDisplay('generic_type','PRESENCE');
    $cmd->setConfiguration('returnStateValue',0);
    $cmd->setConfiguration('returnStateTime',1);
    $cmd->setTemplate("mobile",'alert');
    $cmd->setTemplate("dashboard",'alert' );
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

    $cmd = doorbirdCmd::byEqLogicIdAndLogicalId($this->getId(),'motion');
		if (!is_object($cmd)) {
			$cmd = new doorbirdCmd();
			$cmd->setLogicalId('motion');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Mouvement', __FILE__));
		}
		$cmd->setType('info');
		$cmd->setSubType('binary');
    $cmd->setDisplay('generic_type','PRESENCE');
    $cmd->setConfiguration('returnStateValue',0);
    $cmd->setConfiguration('returnStateTime',1);
    $cmd->setTemplate("mobile",'presence');
    $cmd->setTemplate("dashboard",'presence' );
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

    $cmd = doorbirdCmd::byEqLogicIdAndLogicalId($this->getId(),'dooropen');
		if (!is_object($cmd)) {
			$cmd = new doorbirdCmd();
			$cmd->setLogicalId('dooropen');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Porte', __FILE__));
		}
		$cmd->setType('info');
		$cmd->setSubType('binary');
    $cmd->setDisplay('generic_type','LOCK_STATE');
    $cmd->setConfiguration('returnStateValue',1);
    $cmd->setConfiguration('returnStateTime',1);
    $cmd->setTemplate("mobile",'lock');
    $cmd->setTemplate("dashboard",'lock' );
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

    $url = network::getNetworkAccess('internal') . '/plugins/doorbird/core/api/jeeDoorbird.php?apikey=' . config::byKey('api') . '%26id=' . $this->getId() . '%26sensor=';
    $addr = trim($this->getConfiguration('addr'));
    $user = trim($this->getConfiguration('user'));
    $pass = trim($this->getConfiguration('pass'));

    $urlfinal = 'http://' . $addr . '/bha-api/notification.cgi?reset=1';
    doorbird::callDoor($urlfinal,$user,$pass);
    sleep(1);

    $urlfinal = 'http://' . $addr . '/bha-api/notification.cgi?url="' . $url . '"doorbell&subscribe=1&event=doorbell';
    doorbird::callDoor($urlfinal,$user,$pass);
    sleep(1);

    $urlfinal = 'http://' . $addr . '/bha-api/notification.cgi?url="' . $url . '"dooropen&subscribe=1&event=dooropen';
    doorbird::callDoor($urlfinal,$user,$pass);
    sleep(1);

    $urlfinal = 'http://' . $addr . '/bha-api/notification.cgi?url="' . $url . '"motionsensor&subscribe=1&event=motionsensor';
    doorbird::callDoor($urlfinal,$user,$pass);
    sleep(1);

  }

  public function postRemove() {
    $url = network::getNetworkAccess('internal') . '/plugins/doorbird/core/api/jeeDoorbird.php?apikey=' . config::byKey('api') . '%26id=' . $this->getId() . '%26sensor=';
    $addr = trim($this->getConfiguration('addr'));

    $addr = trim($this->getConfiguration('addr'));
    $user = trim($this->getConfiguration('user'));
    $pass = trim($this->getConfiguration('pass'));

    $urlfinal = 'http://' . $addr . '/bha-api/notification.cgi?url=' . $url . 'doorbell&subscribe=0&event=doorbell';
    doorbird::callDoor($urlfinal,$user,$pass);

    $urlfinal = 'http://' . $addr . '/bha-api/notification.cgi?url=' . $url . 'dooropen&subscribe=0&event=dooropen';
    doorbird::callDoor($urlfinal,$user,$pass);

    $urlfinal = 'http://' . $addr . '/bha-api/notification.cgi?url=' . $url . 'motionsensor&subscribe=0&event=motionsensor';
    doorbird::callDoor($urlfinal,$user,$pass);
	}

  public function callDoor($url,$user,$pass) {
    $curl = curl_init();
    log::add('doorbird', 'debug', 'Appel : ' . $url . ' avec ' . $user . ':' . $pass);

    $auth = base64_encode($user . ':' . $pass);
    $header = array("Authorization: Basic $auth");
    $opts = array( 'http' => array ('method'=>'GET',
                                               'header'=>$header));
    $ctx = stream_context_create($opts);
    $retour = file_get_contents($url,false,$ctx);

    //$temp = split("\r\n", $data[1]) ;

    //$result = unserialize( $temp[2] ) ;

    log::add('doorbird', 'debug', 'Retour : ' . $retour);
  }

}

class doorbirdCmd extends cmd {
  /*     * *************************Attributs****************************** */



  /*     * ***********************Methode static*************************** */

  /*     * *********************Methode d'instance************************* */
  public function execute($_options = null) {


            switch ($this->getType()) {
				          case 'info' :
          					return $this->getConfiguration('value');
          					break;
                case 'action' :
					          $request = $this->getConfiguration('request');
                    switch ($this->getSubType()) {
                        case 'slider':
                            $request = str_replace('#slider#', $value, $request);
                            break;
                        case 'color':
                            $request = str_replace('#color#', $_options['color'], $request);
                            break;
                        case 'message':
              							if ($_options != null)  {
              								$replace = array('#title#', '#message#');
              								$replaceBy = array($_options['title'], $_options['message']);
              								if ( $_options['title'] == '') {
              									throw new Exception(__('Le sujet ne peuvent être vide', __FILE__));
              								}
              								$request = str_replace($replace, $replaceBy, $request);

              							}
              							else
              							 $request = 1;
                            break;
						            default : $request == null ?  1 : $request;
					          }

					$eqLogic = $this->getEqLogic();
          $addr = trim($eqLogic->getConfiguration('addr'));
					$url = $this->getConfiguration('url');
          $user = trim($eqLogic->getConfiguration('user'));
          $pass = trim($eqLogic->getConfiguration('pass'));

          $urlfinal = 'http://' . $addr . '/bha-api/' . $url;
          doorbird::callDoor($urlfinal,$user,$pass);

					return true;
			}
			return true;
    }
}

?>

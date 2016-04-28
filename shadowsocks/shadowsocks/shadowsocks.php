<?php
/**
 * @author Tension
 * @modify HoshinoTouko
 * @version 1.0.0
 */
function shadowsocks_ConfigOptions() {
	$configarray = array(
	"数据库" => array("Type" => "text", "Size" => "25"),
	"加密方式" 	=> array("Type" => "text", "Size" => "25"),
	"起始端口" 	=> array("Type" => "text", "Size" => "25"),
	"节点" => array("Type" => "textarea"),
	"最大流量(GB)" => array("Type" => "textarea")
	);

	return $configarray;
}
function shadowsocks_mysql($params) {
	$mysql = mysql_connect($params['serverip'],$params['serverusername'],$params['serverpassword']);

	if (!$mysql) {
		$mysql = "Can not connect to MySQL Server".mysql_error();
	} else {
		mysql_select_db($params['configoption1'],$mysql);
		$add = mysql_query("alter table user add pid varchar(50) not null",$mysql);
		if (!$add) {
			$mysql = "Can't add.";
		}
	}

	return $mysql;
}

function shadowsocks_CreateNewPort($params) {
	if(!isset($params['configoption3']) || $params['configoption3'] == "") {
		$start = 10000;
	} else {
		$start = $params['configoption3'];
	}
	$end = 65535;
	shadowsocks_mysql($params);
	$mysql = mysql_connect($params['serverip'],$params['serverusername'],$params['serverpassword']);

	if (!$mysql) {
		$result = "Can not connect to MySQL Server".mysql_error();
	} else {
		mysql_select_db($params['configoption1'],$mysql);
		$select = mysql_query("SELECT port FROM user");
		$select = mysql_fetch_array($select);

		if (!$select == "") {
			$get_last_port = mysql_query("SELECT port FROM user order by port desc limit 1",$mysql);
			$get_last_port = mysql_fetch_array($get_last_port);
			$result = $get_last_port['port']+1;
			if ($result > $end) {
				$result = "Port exceeds the maximum value.";
			}
		} else {
			$result = $start;
		}
	}
	return $result;
}

function shadowsocks_CreateAccount($params) {
	$serviceid			= $params["serviceid"]; # Unique ID of the product/service in the WHMCS Database
    $pid 				= $params["pid"]; # Product/Service ID
    $producttype		= $params["producttype"]; # Product Type: hostingaccount, reselleraccount, server or other
    $domain 			= $params["domain"];
  	$username 			= $params["username"];
  	$password 			= $params["password"];
    $clientsdetails 	= $params["clientsdetails"]; # Array of clients details - firstname, lastname, email, country, etc...
    $customfields 		= $params["customfields"]; # Array of custom field values for the product
    $configoptions 		= $params["configoptions"]; # Array of configurable option values for the product

    # Product module option settings from ConfigOptions array above
    $configoption1 		= $params["configoption1"];
    $configoption2 		= $params["configoption2"];

    # Additional variables if the product/service is linked to a server
    $server 			= $params["server"]; # True if linked to a server
    $serverid 			= $params["serverid"];
    $serverip 			= $params["serverip"];
    $serverusername 	= $params["serverusername"];
    $serverpassword		= $params["serverpassword"];
    $serveraccesshash 	= $params["serveraccesshash"];
    $serversecure 		= $params["serversecure"]; # If set, SSL Mode is enabled in the server config
  	$adminusername		= mysql_fetch_array(mysql_query("SELECT username FROM `tbladmins`"));
  	$mysql 				= mysql_connect($params['serverip'],$params['serverusername'],$params['serverpassword']);
	$port 				= shadowsocks_CreateNewPort($params);

  	if (!$mysql) {
  		$result = "Can not connect to MySQL Server".mysql_error();
  	} else {
  		mysql_select_db($params['configoption1'],$mysql);
  		$select = mysql_query("SELECT pid FROM user WHERE pid='".$serviceid."'",$mysql);
  		$select = mysql_fetch_array($select);

  		if (!empty($select['pid'])) {
  		 	$result = "Service already exists.";
  		} else {
  			if (isset($params['customfields']['password'])) {
			    $command = "encryptpassword";
				$adminuser = $adminusername['username'];
				$values["password2"] = $params["customfields"]['password'];
				$results = localAPI($command,$values,$adminuser);
				$table = "tblhosting";
				$update = array("password"=>$results['password']);
				$where = array("id"=>$params["serviceid"]);
				update_query($table,$update,$where);
				#mysql_query("UPDATE `tblhosting` set password='".$results['password']."' where id='".$params["serviceid"]."'");
				$password = $params["customfields"]['password'];
			}
			//Create Account

			if(isset($params['configoptions']['traffic'])) {
				$traffic = $params['configoptions']['traffic']*1024*1048576;
				$query = mysql_query("INSERT INTO user(pid,passwd,port,transfer_enable) VALUES ('".$params['serviceid']."','".$password."','".$port."','".$traffic."')",$mysql);
				if ($query) {
					$result = 'success';
				} else {
					// $result = 'Error.Cloud not create a new user.';
					$result = mysql_error();
				}
			} else {

				if (!empty($params['configoption5'])) {
					$max = $params['configoption5'];
				}

				if(isset($max)) {
					$traffic = $max*1024*1048576;
				} else {
					$traffic = 1099511627776;
				}
				$query = mysql_query("INSERT INTO user(pid,passwd,port,transfer_enable) VALUES ('".$params['serviceid']."','".$password."','".$port."','".$traffic."')",$mysql);
				if ($query) {
					$result = 'success';
				} else {
					$result = 'Error.Cloud not create a new user.'.mysql_error();
				}
			}
  		}
  	}
  	return $result;
}

function shadowsocks_TerminateAccount($params) {
	$mysql = mysql_connect($params['serverip'],$params['serverusername'],$params['serverpassword']);
	$password = $params['password'];
	if (!$mysql) {
		$result = "Can not connect to MySQL Server".mysql_error();
	} else {
		mysql_select_db($params['configoption1'],$mysql);
		if(mysql_query("DELETE FROM user WHERE pid='".$params['serviceid']."'",$mysql)) {
			$result = 'success';
		} else {
			$result = 'Error. Cloud not Terminate this Account.'.mysql_error();
		}
	}
	return $result;
}
function shadowsocks_SuspendAccount($params) {
	$mysql = mysql_connect($params['serverip'],$params['serverusername'],$params['serverpassword']);
	$password = md5(time().rand(0,100));
	if (!$mysql) {
		$result = "Can not connect to MySQL Server".mysql_error();
	} else {
		mysql_select_db($params['configoption1'],$mysql);
		$select = mysql_query("SELECT pid FROM user WHERE pid='".$params['serviceid']."'",$mysql);
		$select = mysql_fetch_array($select);

		if ($select == "") {
			$result = "Can't find.";
		} else {
			if (mysql_query("UPDATE user SET  passwd='".$password."' WHERE pid='".$params['serviceid']."'",$mysql)) {
				$result = 'success';
			} else {
				$result = "Can't suspend user.".mysql_error();
			}
		}
	}

	return $result;
}

function shadowsocks_UnSuspendAccount($params) {
	$mysql = mysql_connect($params['serverip'],$params['serverusername'],$params['serverpassword']);
	if ($params['password'] == $params['customfields']['password']) {
		$password = $params['password'];
	} else {
		$password = $params['customfields']['password'];
	}
	if (!$mysql) {
		$result = "Can not connect to MySQL Server".mysql_error();
	} else {
		mysql_select_db($params['configoption1'],$mysql);
		$select = mysql_query("SELECT pid FROM user WHERE pid='".$params['serviceid']."'",$mysql);
		$select = mysql_fetch_array($select);

		if ($select == "") {
			$result = "Can't find.";
		} else {
			if (mysql_query("UPDATE user SET passwd='".$password."' WHERE pid='".$params['serviceid']."'",$mysql)) {
				$result =  'success';
			} else {
	        	$result = "Could not Suspend user:".mysql_error();
	        }
		}
	}
	return $result;
}

function shadowsocks_ChangePassword($params) {
	$mysql = mysql_connect($params['serverip'],$params['serverusername'],$params['serverpassword']);
	if (!$mysql) {
		$result = "Can not connect to MySQL Server".mysql_error();
	} else {
		mysql_select_db($params['configoption1'],$mysql);
		$select = mysql_query("SELECT pid FROM user WHERE pid='".$params['serviceid']."'",$mysql);
		$select = mysql_fetch_array($select);

		if ($select == "") {
			$result = "Can't find.";
		} else {
			if (mysql_query("UPDATE user SET passwd='".$params['password']."' WHERE pid='".$params['serviceid']."'")) {
				$table = "tblcustomfields";
				$fields = "id";
				$where = array("fieldname"=>"password|Password");
				// $where = array("fieldname"=>"password|连接密码");
				$result = select_query($table,$fields,$where);
				$data = mysql_fetch_array($result);
				$fieldid = $data['id'];
				$table = "tblcustomfieldsvalues";
				$update = array("value"=>$params["password"]);
				$where = array("fieldid"=>$fieldid,"relid"=>$params["serviceid"]);
				update_query($table,$update,$where);
				$result = 'success';
			} else {
				$result = 'Error'.mysql_error();
			}
		}
	}
	return $result;
}

function shadowsocks_ChangePackage($params) {
	$mysql = mysql_connect($params['serverip'],$params['serverusername'],$params['serverpassword']);
	if (!$mysql) {
		return mysql_error();
	} else {
		mysql_select_db($params['configoption1'],$mysql);
		if(isset($params['configoptions']['traffic'])) {
			$traffic = $params['configoptions']['traffic']*1024*1048576;
			if (mysql_query("UPDATE user SET transfer_enable='".$traffic."' WHERE pid='".$params['serviceid']."'",$mysql)) {
				return 'success';
			} else {
				return mysql_error();
			}
		} else {
			if (!empty($params['configoption5'])) {
				$max = $params['configoption5'];
			}
			if(isset($max)) {
				$traffic = $max*1024*1048576;
			} else {
				$traffic = 1099511627776;
			}
			if (mysql_query("UPDATE user SET transfer_enable='".$traffic."' WHERE pid='".$params['serviceid']."'",$mysql)) {
				return 'success';
			} else {
				return mysql_error();
			}
		}
	}
}

function shadowsocks_node($params) {
	$node = $params['configoption4'];
	if (!empty($node) || isset($node)) {
		$str = explode(';', $node);
		foreach ($str as $key => $val) {
			$html .= $str[$key].'<br>';
		}
	} else {
		$str = $params['serverip'];
		$html .= $str.'<br>';
	}
	return $html;
}

function shadowsocks_ZeroTraffic($params) {
	$mysql = mysql_connect($params['serverip'],$params['serverusername'],$params['serverpassword']);
	if(!$mysql) {
		return mysql_error();
	} else {
		mysql_select_db($params['configoption1'],$mysql);
		$query = mysql_query("UPDATE user SET u='0',d='0' WHERE pid='".$params['serviceid']."'",$mysql);
		if ($query) {
			return 'success';
		} else {
			return false;
		}
	}
}
function shadowsocks_ClientArea($params) {
	// $info = shadowsocks_UsageDetail($params);
	$mysql = mysql_connect($params['serverip'],$params['serverusername'],$params['serverpassword']);
	if(!$mysql) {
		return mysql_error();
	} else {
		mysql_select_db($params['configoption1'],$mysql);
		//Traffic
		$traffic = $params['configoptions']['traffic'];
        
		//Usage
		$Query = mysql_query("SELECT sum(u+d),port,passwd,transfer_enable FROM user WHERE pid='".$params['serviceid']."'",$mysql);
		$Query = mysql_fetch_array($Query);
		$Usage = $Query[0] / 1048576;
        
        //databaseTraffic
        $traffic = $Query['transfer_enable'] / 1048576;
		//Port 1048576 1073741824
		// $QueryPort = mysql_query("SELECT port FROM user WHERE pid='".$params['pid']."'");
		// $QUeryPort = mysql_fetch_array($QueryPort);
		$Port = $Query['port'];
		//Free
		$Free = $traffic  - $Usage;
		//Percentage
		$password = $Query['passwd'];

		$Usage = round($Usage,2);
		$Free = round($Free,2);
		$node = shadowsocks_node($params);
        
        //debug
        $decodeQuery = json_encode($Query);
	}
    if (isset( $traffic )) {
    	$html = "
    	<div class=\"row\">
			<div class=\"col-sm-4\"></div>
			<div class=\"col-sm-8\">
			<div class=\"panel-collapse collapse in\">
				<table class=\"table table-bordered table-hover tc-table\">
					<tbody>
						<tr>
							<td>流量限制</td><td>{$traffic} MBytes</td>
						</tr>
						<tr>
							<td>加密方式</td><td>{$params['configoption2']}</td>
						</tr>
						<tr>
						    <td>连接端口</td><td>{$Port}</td>
						</tr>
						<tr>
							<td>连接密码</td><td>{$password}</td>
						</tr>
                        <tr>
							<td>已经使用</td><td>{$Usage} MBytes</td>
						</tr>
						<tr>
							<td>剩余流量</td><td>{$Free} MBytes</td>
						</tr>
						<tr>
							<td>节点列表</td><td>{$node}</td>
						</tr>
					</tbody>
				</table>
				</div>
			</div>
		</div>
    	";
    } else {
    	$html = "
			<div class=\"row\">
			<div class=\"col-sm-4\"></div>
			<div class=\"col-sm-8\">
			<div class=\"panel-collapse collapse in\">
				<table class=\"table table-bordered table-hover tc-table\">
					<tbody>
						<tr>
							<td>流量限制  </td><td>Unlimited</td>
						</tr>
						<tr>
							<td>加密方式</td><td>{$params['configoption2']}</td>
						</tr>
						<tr>
							<td>节点列表</td><td>{$node}</td>
						</tr>
						<tr>
							<td>连接端口</td><td>{$Port}</td>
						</tr>
						<tr>
							<td>连接密码</td><td>{$password}</td>
						</tr>
						<tr>
							<td>已经使用 </td><td>{$Usage} MBytes</td>
						</tr>
                        <tr>
							<td>剩余流量</td><td>{$Free} MBytes</td>
							
						</tr>
					</tbody>
				</table>
			</div>
			</div>
		</div>
    	";
    }
    return $html;
}

function shadowsocks_AdminServicesTabFields($params) {
	$mysql = mysql_connect($params['serverip'],$params['serverusername'],$params['serverpassword']);
	if(!$mysql) {
		return mysql_error();
		exit;
	} else {
		mysql_select_db($params['configoption1'],$mysql);
		//Traffic
		$traffic = null;
		// $traffic = isset($params['configoptions']['traffic']) ? $params['configoptions']['traffic']*1024 : isset($params['configoption5']) ? $params['configoption5']*1024 : 1048576;
		if (isset($params['configoptions']['traffic'])) {
			$traffic = $params['configoptions']['traffic']*1024;
		} else if(!empty($params['configoption5'])) {
			$traffic = $params['configoption5']*1024;
		} else {
			$traffic = 1048576;
		}
		//Usage
		$Query = mysql_query("SELECT sum(u+d),port FROM user WHERE pid='".$params['serviceid']."'",$mysql);
		$Query = mysql_fetch_array($Query);
		$Usage = $Query[0]/1048576;
		//Port
		// $QueryPort = mysql_query("SELECT port FROM user WHERE pid='".$params['serviceid']."'");
		// $QUeryPort = mysql_fetch_array($QueryPort);
		$Port = $Query['port'];
		//Free
		$Free = $traffic - $Usage;
		//Percentage
	}
    $fieldsarray = array(
     '流量限制' => $traffic.' MBytes',
     '已经使用' => $Usage.' MBytes',
     '剩余流量' => $Free.' MBytes',
     '连接端口' => $Port,
    );
    return $fieldsarray;
}

function shadowsocks_AdminCustomButtonArray() {
    $buttonarray = array(
   "清零" => "ZeroTraffic",
  );
  return $buttonarray;
}
?>

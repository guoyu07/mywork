<?phpclass WechatAction extends Action {	public function init() {		import ( 'Wechat', APP_PATH . 'Common/Wechat', '.class.php' );		$config = M ( "Wxconfig" )->where ( array (				"id" => "1" 		) )->find ();				$options = array (				'token' => $config ["token"], // 填写你设定的key				'encodingaeskey' => $config ["encodingaeskey"], // 填写加密用的EncodingAESKey				'appid' => $config ["appid"], // 填写高级调用功能的app id				'appsecret' => $config ["appsecret"], // 填写高级调用功能的密钥				'partnerid' => $config ["partnerid"], // 财付通商户身份标识				'partnerkey' => $config ["partnerkey"], // 财付通商户权限密钥Key				'paysignkey' => $config ["paysignkey"]  // 商户签名密钥Key				);		$weObj = new Wechat ( $options );		return $weObj;	}	public function index() {		$weObj = $this->init ();		$weObj->valid ();		$type = $weObj->getRev ()->getRevType ();						include dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/Public/Conf/button_config.php'; 		switch ($type) {			case Wechat::MSGTYPE_TEXT :// 				$weObj->text ( "hello, I'm wechat" )->reply ();				$key = $weObj->getRev()->getRevContent();								$replay = M("Wxmessage")->where(array("key"=>$key))->select();				for ($i = 0; $i < count($replay); $i++) {					if ($replay[$i]["type"]==0) {						$appUrl = 'http://' . $this->_server ( 'HTTP_HOST' ) . __ROOT__;						$newsArr[$i] = array(								'Title' => $replay[$i]["title"],								'Description' => $replay[$i]["description"],								'PicUrl' => $appUrl . '/Public/Uploads/'.$replay[$i]["picurl"],								'Url' => $replay[$i]["url"].'&uid=' . $weObj->getRevFrom ()						);					}else{						$weObj->text ( $replay[$i]["description"] )->reply ();						exit ();					}				}				$marrxiaoi = M('xiaoi');	            $arrxiaoi = $marrxiaoi->find();                if (!$replay && $arrxiaoi['zt'] !=="0") {                	$xiaoi =  R ( "Api/Api/Talkxiaoi", array ($key));                	$weObj->text ($xiaoi)->reply ();                                 }								if(!empty($newsArr))				{					$weObj->getRev ()->news ( $newsArr )->reply ();				}				else				{					$weObj->transfer_customer_service()->reply();				}				exit ();								break;			case Wechat::MSGTYPE_EVENT :				$eventype = $weObj->getRev ()->getRevEvent ();				if ($eventype ['event'] == "CLICK") {					$usersresult = R ( "Api/Api/getuser", array (							$weObj->getRevFrom ()						) );								              //第一次新增写入数据库					if (!$usersresult) {						//获取头像等信息					$user = array();					$openid = $weObj->getRevFrom ();					$wx_info = $weObj->getUserInfo($openid);				$user['wx_info'] = json_encode($wx_info);				 $user ["uid"] = $openid;					$user_id = M ( "User" )->add ( $user );					if ($user_id) {					$where = array();					$where["uid"] = $weObj->getRevFrom ();					$usersresult = $m->where($where)->find ();					}					}					if( $eventype ['key']=='erweima')					{						if (($usersresult['member']==1)) {							$uid = $weObj->getRevFrom ();							$usersresult = R ( "Api/Api/getuser", array (								$uid 								) );							$ticket = R ( "Api/Api/ticket", array ($usersresult));							$data['media'] = '@'.realpath(dirname(__FILE__).'/../../../../').str_replace('/','\\',$ticket['ticket']);                                                        $data['media'] = str_replace('.\\','\\',$data['media']);							$res = $weObj->uploadMedia($data, 'image');                            if(!empty($res['media_id'])){							$weObj->getRev ()->image ( $res['media_id'] )->reply ();							}else{							$text = '我的二维码海报<a href="http://'.$_SERVER['SERVER_NAME'].'/index.php?g=App&m=Index&a=my_ticket&uid='.$usersresult['uid'].'">点击生成</a>';							}						}else{							$text = '您还不是VIP会员，部分功能无法使用，<a href="http://'.$_SERVER['SERVER_NAME'].'/index.php?g=App&m=Index&a=index">点击购买</a>成为VIP会员，即可发布自己的微信二维码，结识更多的人脉朋友';							}						$weObj->text ( $text )->reply ();						exit();					}					if( $eventype ['key']=='GET_PIC')					{						$usersresult = R ( "Api/Api/getuser", array (							$weObj->getRevFrom ()							) );																              //第一次新增写入数据库					if (!$usersresult) {						//获取头像等信息					$user = array();					$openid = $weObj->getRevFrom ();					$wx_info = $weObj->getUserInfo($openid);				$user['wx_info'] = json_encode($wx_info);				 $user ["uid"] = $openid;					$user_id = M ( "User" )->add ( $user );					if ($user_id) {					$where = array();					$where["uid"] = $weObj->getRevFrom ();					$usersresult = $m->where($where)->find ();					}					}						if($usersresult['member']==1)						{							$ticket = R ( "Api/Api/ticket", array (								$usersresult 							) );														$image = realpath(dirname(__FILE__).'/../../../../').'/imgpublic/'.$ticket['pic'];														$data['media'] = "@$image";							$res = $weObj->uploadMedia($data, 'image');							$weObj->getRev ()->image ( $res['media_id'] )->reply ();						}						else						{							$text = '您还不是VIP会员，部分功能无法使用，<a href="http://'.$_SERVER['SERVER_NAME'].'/index.php?g=App&m=Index&a=index">点击购买</a>成为VIP会员，即可发布自己的微信二维码，结识更多的人脉朋友。还能享受商城最低一折哦！--------------------------<a href="http://'.$_SERVER['SERVER_NAME'].'/hufen/list.php?uid='.$usersresult['uid'].'>点这里进入【粉丝人脉】</a>';							$weObj->text ( $text )->reply ();						}						exit ();					}					elseif( $eventype ['key']=='GET_URL')					{						$usersresult = R ( "Api/Api/getuser", array (							$weObj->getRevFrom ()							) );              //第一次新增写入数据库						if (!$usersresult) {						//获取头像等信息							$user = array();							$openid = $weObj->getRevFrom ();							$wx_info = $weObj->getUserInfo($openid);							$user['wx_info'] = json_encode($wx_info);							$user ["uid"] = $openid;							$user_id = M ( "User" )->add ( $user );							if ($user_id) {								$where = array();								$where["uid"] = $weObj->getRevFrom ();								$usersresult = $m->where($where)->find ();							}						}						if($usersresult['member']==1)						{							$url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.'g=App&m=Member&a=register&mid='.$usersresult['id'];							$weObj->text ( $url )->reply ();													}						else						{							$text = '您还不是VIP会员，部分功能无法使用，<a href="http://'.$_SERVER['SERVER_NAME'].'/index.php?g=App&m=Index&a=index">点击购买</a>成为VIP会员，即可发布自己的微信二维码，结识更多的人脉朋友。--------------------------<a href="http://'.$_SERVER['SERVER_NAME'].'//hufen/list.php?uid='.$usersresult['uid'].'>点这里进入【粉丝人脉】</a>';							$weObj->text ( $text )->reply ();						}						exit ();					}					else					{						$appUrl = 'http://' . $this->_server ( 'HTTP_HOST' ) . __ROOT__;												$news = M ( "Wxmessage" )->where ( array (								"key" => $eventype ['key'],								"type" => 0 						) )->select ();												if ($news) {							for($i = 0; $i < count ( $news ); $i ++) {								$newsArr[$i] = array(									'Title' => $news[$i]["title"],									'Description' => $news[$i]["description"],									'PicUrl' => $appUrl . '/Public/Uploads/'.$news[$i]["picurl"],									'Url' => $news[$i]["url"].'&uid=' . $weObj->getRevFrom ()								);							}							$weObj->getRev ()->news ( $newsArr )->reply ();						}																								$replay = M("Wxmessage")->where(array("key"=>$eventype ['key'],"type" => 1))->select();						if(!empty($replay[0]["description"]))						{							$weObj->text ( $replay[0]["description"] )->reply ();							exit ();					}					elseif( $eventype ['key']=='GET_UURL')					{						$usersresult = R ( "Api/Api/getuser", array (							$weObj->getRevFrom ()						) );								              //第一次新增写入数据库					if (!$usersresult) {						//获取头像等信息					$user = array();					$openid = $weObj->getRevFrom ();					$wx_info = $weObj->getUserInfo($openid);				$user['wx_info'] = json_encode($wx_info);				 $user ["uid"] = $openid;					$user_id = M ( "User" )->add ( $user );					if ($user_id) {					$where = array();					$where["uid"] = $weObj->getRevFrom ();					$usersresult = $m->where($where)->find ();					}					}						if($usersresult['member']==1)						{							$url = ' <a href="http://'.$_SERVER['SERVER_NAME'].'/hufen/list.php?uid='.$usersresult['uid'].'">点击进入【粉丝人脉】</a>							您的专属二维码海报，您可以自愿分享给您的微信好友、朋友圈和微信群.';							$weObj->text ( $url )->reply ();						}						else						{							$text = '您还不是VIP会员，部分功能无法使用，<a href="http://'.$_SERVER['SERVER_NAME'].'/index.php?g=App&m=Index&a=index">点击购买</a>成为VIP会员，即可发布自己的微信二维码，结识更多的人脉朋友。--------------------------<a href="http://'.$_SERVER['SERVER_NAME'].'/hufen/list.php?uid='.$usersresult['uid'].'">点这里进入【粉丝人脉】</a>';							$weObj->text ( $text )->reply ();						}						exit ();						}											}                }elseif ($eventype['event'] == "SCAN"){$weObj->text ( "请将推广二维码分享到你的朋友圈，只要有人从你专属二维码扫描关注进来就会成为你的代理。" )->reply ();				}elseif ($eventype['event'] == "subscribe") {					//初始化用户					$m = M ( "User" );					$where = array();					$where["uid"] = $weObj->getRevFrom ();					$result = $m->where($where)->find ();                     											$user = array();										//获取头像等信息					$openid = $weObj->getRevFrom ();					$wx_info = $weObj->getUserInfo($openid);					$user['wx_info'] = json_encode($wx_info);              //第一次新增关注写入数据库					if (!$result) {				 $user ["uid"] = $openid;					$user_id = M ( "User" )->add ( $user );					if ($user_id) {					$where = array();					$where["uid"] = $weObj->getRevFrom ();					$result = $m->where($where)->find ();					}					}                   										if(!empty($eventype['ticket']) && empty($result['l_id']) && empty($result['member']))					{						$where = array();						$where["ticket"] = $eventype['ticket'];						$results = $m->where($where)->find ();												if(!empty($results['id']))						{							$user ["l_id"] = $results['id'];														//增加分销人							$a_info = array();							$a_info['id'] = $results['id'];							$a_info['a_cnt'] = $results['a_cnt']+1;							$user_id = M ( "User" )->save ( $a_info );														if(strlen($results['uid'])>10)							{								$data = array();								$data['touser'] = $results['uid'];								$data['msgtype'] = 'text';								$data['text']['content'] = '【'.$wx_info[nickname].'】通过二维码关注了本公众号，成为您的'.$message_name.'团队成员！';								$weObj->sendCustomMessage($data);							}														if($results['l_id'])//b jibie							{								$where = array();								$where["id"] = $results['l_id'];								$b_results = $m->where($where)->find ();																if(!empty($b_results))								{									$b_info = array();									$b_info['id'] = $b_results['id'];									$b_info['b_cnt'] = $b_results['b_cnt']+1;									$user_id = M ( "User" )->save ( $b_info );																		$user["l_b"] = $b_results['id'];																		if(strlen($b_results['uid'])>10)									{										$data = array();										$data['touser'] = $b_results["uid"];										$data['msgtype'] = 'text';										$data['text']['content'] = '【'.$wx_info[nickname].'】通过二维码关注了本公众号，成为您的'.$message_name.'团队成员！';										$weObj->sendCustomMessage($data);									}																		if($b_results['l_id'])//c jibie									{										$where = array();										$where["id"] = $b_results['l_id'];										$c_results = $m->where($where)->find ();																				if(!empty($c_results))										{											$c_info = array();											$c_info['id'] = $c_results['id'];											$c_info['c_cnt'] = $c_results['c_cnt']+1;											$user_id = M ( "User" )->save ( $c_info );																						$user["l_c"] = $c_results['id'];																						if(strlen($c_results['uid'])>10)											{												$data = array();												$data['touser'] = $c_results["uid"];												$data['msgtype'] = 'text';												$data['text']['content'] = '【'.$wx_info[nickname].'】通过二维码关注了本公众号，成为您的'.$message_name.'团队成员！';												$weObj->sendCustomMessage($data);											}										}									}								}							}						}					}															if(!empty($result['id']))					{						$user['id'] = $result['id'];						$user_id = M ( "User" )->save ( $user );					}					else					{						$user ["uid"] = $openid;						$user_id = M ( "User" )->add ( $user );					}										if( !empty($result['l_id']) )					{						$user["l_id"] = $result['l_id'];					}										if(empty($result["l_id"]) && !empty($result['member']))					{						$text = '恭喜您【'.$wx_info['nickname'].'】成为【'.$message_name.'】的第【'.$result['id'].'】位粉丝!【'.$message_name.'】引领你开启“微商新模式”,现在购买【'.$message_name.'】VIP会员即可上传自己的微信二维码,结识更多的新朋友!享受商城购买1折的特权！-------------------------->> <a href="http://'.$_SERVER['SERVER_NAME'].'/index.php?g=App&m=Index&a=index">点击这里</a> 购买VIP会员，获得更多特权体验，还能生成专属二维码海报，便于分享给您的好友。-------------------------->> <a href="http://'.$_SERVER['SERVER_NAME'].'/hufen/list.php?uid='.$result['uid'].'">点击进入【粉丝人脉】</a>';R ( "Api/Api/kefu", array("content"=>'恭喜你，获得1元佣金！',"id"=>$result['id']));					}					else					{						if(!empty($user["l_id"]))						{							$user_info = M ( "User" )->where( array('id'=>$user ["l_id"]) )->find();							$user_info = json_decode($user_info['wx_info'],true);														if($result['id']>1)							{								$user_id = $result['id'];							}														$text = '恭喜您由【'.$user_info['nickname'].'】邀请成为【'.$message_name.'】的第【'.$user_id.'】位会员.现在购买【'.$message_name.'】<a href="http://'.$_SERVER['SERVER_NAME'].'/index.php?g=App&m=Index&a=index">VIP会员</a>，即可上传自己的微信二维码，开启被动加人。结识更多的新朋友！-------------------------->> <a href="http://'.$_SERVER['SERVER_NAME'].'/index.php?g=App&m=Index&a=index">点击这里</a> 购买VIP会员，获得更多特权体验，还能生成专属二维码海报，便于分享给您的好友。-------------------------->> <a href="http://'.$_SERVER['SERVER_NAME'].'/hufen/list.php?uid='.$result['uid'].'">点击进入【粉丝人脉】</a>';R ( "Api/Api/kefu", array("content"=>'恭喜你，获得1元佣金！',"id"=>$result['id']));						}						else						{							$text = '恭喜您【'.$wx_info['nickname'].'】成为【'.$message_name.'】的第【'.$result['id'].'】位粉丝!【'.$message_name.'】引领你开启“加粉新模式”,现在购买【'.$message_name.'】VIP会员即可上传自己的微信二维码,结识更多的新朋友!-------------------------->> <a href="http://'.$_SERVER['SERVER_NAME'].'/index.php?g=App&m=Index&a=index">点击这里</a> 购买VIP会员，获得更多特权体验，还能生成专属二维码海报，便于分享给您的好友。-------------------------->> <a href="http://'.$_SERVER['SERVER_NAME'].'/hufen/list.php?uid='.$result['uid'].'">点击进入【粉丝人脉】</a>';R ( "Api/Api/kefu", array("content"=>'恭喜你，获得1元佣金！',"id"=>$result['id']));						}					}										    				$weObj->text ( $text )->reply ();				}				exit ();				break;			default :				//$weObj->text ( "help info" )->reply ();			    $xiaoi =  R ( "Api/Api/Talkxiaoi", array ($key));                $weObj->text ($xiaoi)->reply ();		}	}	/*		*/	public function createMenu() {		$menu = M ( "Wxmenu" )->where("pid=0")->order('listorder asc')->select ();		$menu2count = M ( "Wxmenu" )->where("pid=0")->order('listorder asc')->count ();		$data.='{ "button":[';		foreach ($menu as $key => $value) {			$menu2 = M ( "Wxmenu" )->where("pid=".$value['menu_id'])->order('listorder asc')->select ();			$menucount = M ( "Wxmenu" )->where("pid=".$value['menu_id'])->order('listorder asc')->count ();			if ($menucount<=0) {				if ($menu2count==$key+1) {					if ($value['menu_type']=="view") {						$data.='{ "name":"'.$value['menu_name'].'", "type":"view", "url":"'.$value['view_url'].'" }';					}else{						$data.='{ "name":"'.$value['menu_name'].'", "type":"click", "key":"'.$value['event_key'].'" }';					}				}else{					if ($value['menu_type']=="view") {						$data.='{ "name":"'.$value['menu_name'].'", "type":"view", "url":"'.$value['view_url'].'" },';					}else{						$data.='{ "name":"'.$value['menu_name'].'", "type":"click", "key":"'.$value['event_key'].'" },';					}				}			}else{				$data.='{"name":"'.$value['menu_name'].'",';			}			if ($menucount>0) {					$data.='"sub_button":[';				foreach ($menu2 as $key => $value) {					if ($menucount==$key+1) {						if ($value['menu_type']=="view") {							$data.='{ "name":"'.$value['menu_name'].'", "type":"view", "url":"'.$value['view_url'].'" }]},';						}else{							$data.='{ "name":"'.$value['menu_name'].'", "type":"click", "key":"'.$value['event_key'].'" }]},';						}					}else{						if ($value['menu_type']=="view") {							$data.='{ "name":"'.$value['menu_name'].'", "type":"view", "url":"'.$value['view_url'].'" },';						}else{							$data.='{ "name":"'.$value['menu_name'].'", "type":"click", "key":"'.$value['event_key'].'" },';						}					}				}			}		}		$data.="]}";        $data = str_replace(']},]}', ']}]}', $data);		$weObj = $this->init ();		$arr = $weObj->createMenu ( $data );        if($arr){        	$this->success ( "重新创建菜单成功!24小时后生效，或者重新关注立刻生效" );        }else{        	$this->success ( "创建失败,可能存在非法关键词或者特殊字符" );        }		//print_r($arr);			}}
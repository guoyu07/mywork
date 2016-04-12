<?php
/********************************************************/
/*****                 @!!@                          ****/
/********************************************************/
/**
 *@FileName    : F.php
 *@Author      : Kilin WANG <wangkilin@126.com>
 *@Date        : 2016年4月8日
 *@Homepage    : http://www.kinful.com
 *@Version     : 0.1
 */

/**

* URL组装 支持不同URL模式

* @param string $url URL表达式，格式：'[分组/模块/操作#锚点@域名]?参数1=值1&参数2=值2...'

* @param string|array $vars 传入的参数，支持数组和字符串

* @param string $suffix 伪静态后缀，默认为true表示获取配置值

* @param boolean $redirect 是否跳转，如果设置为true则表示跳转到该URL地址

* @param boolean $domain 是否显示域名

* @return string

*/

function U($url='',$vars='',$suffix=true,$redirect=false,$domain=false) {

	// 解析URL

	$info   =  parse_url($url);

	$url    =  !empty($info['path'])?$info['path']:ACTION_NAME;

	if(isset($info['fragment'])) { // 解析锚点

		$anchor =   $info['fragment'];

		if(false !== strpos($anchor,'?')) { // 解析参数

			list($anchor,$info['query']) = explode('?',$anchor,2);

		}

		if(false !== strpos($anchor,'@')) { // 解析域名

			list($anchor,$host)    =   explode('@',$anchor, 2);

		}

	}elseif(false !== strpos($url,'@')) { // 解析域名

		list($url,$host)    =   explode('@',$info['path'], 2);

	}

	// 解析子域名

	if(isset($host)) {

		$domain = $host.(strpos($host,'.')?'':strstr($_SERVER['HTTP_HOST'],'.'));

	}elseif($domain===true){

		$domain = $_SERVER['HTTP_HOST'];

		if(C('APP_SUB_DOMAIN_DEPLOY') ) { // 开启子域名部署

			$domain = $domain=='localhost'?'localhost':'www'.strstr($_SERVER['HTTP_HOST'],'.');

			// '子域名'=>array('项目[/分组]');

			foreach (C('APP_SUB_DOMAIN_RULES') as $key => $rule) {

				if(false === strpos($key,'*') && 0=== strpos($url,$rule[0])) {

					$domain = $key.strstr($domain,'.'); // 生成对应子域名

					$url    =  substr_replace($url,'',0,strlen($rule[0]));

					break;

				}

			}

		}

	}



	// 解析参数

	if(is_string($vars)) { // aaa=1&bbb=2 转换成数组

		parse_str($vars,$vars);

	}elseif(!is_array($vars)){

		$vars = array();

	}

	if(isset($info['query'])) { // 解析地址里面参数 合并到vars

		parse_str($info['query'],$params);

		$vars = array_merge($params,$vars);

	}



	// URL组装

	$depr = C('URL_PATHINFO_DEPR');

	if($url) {

		if(0=== strpos($url,'/')) {// 定义路由

			$route      =   true;

			$url        =   substr($url,1);

			if('/' != $depr) {

				$url    =   str_replace('/',$depr,$url);

			}

		}else{

			if('/' != $depr) { // 安全替换

				$url    =   str_replace('/',$depr,$url);

			}

			// 解析分组、模块和操作

			$url        =   trim($url,$depr);

			$path       =   explode($depr,$url);

			$var        =   array();

			$var[C('VAR_ACTION')]       =   !empty($path)?array_pop($path):ACTION_NAME;

			$var[C('VAR_MODULE')]       =   !empty($path)?array_pop($path):MODULE_NAME;

			if($maps = C('URL_ACTION_MAP')) {

				if(isset($maps[strtolower($var[C('VAR_MODULE')])])) {

					$maps    =   $maps[strtolower($var[C('VAR_MODULE')])];

					if($action = array_search(strtolower($var[C('VAR_ACTION')]),$maps)){

						$var[C('VAR_ACTION')] = $action;

					}

				}

			}

			if($maps = C('URL_MODULE_MAP')) {

				if($module = array_search(strtolower($var[C('VAR_MODULE')]),$maps)){

					$var[C('VAR_MODULE')] = $module;

				}

			}

			if(C('URL_CASE_INSENSITIVE')) {

				$var[C('VAR_MODULE')]   =   parse_name($var[C('VAR_MODULE')]);

			}

			if(!C('APP_SUB_DOMAIN_DEPLOY') && C('APP_GROUP_LIST')) {

				if(!empty($path)) {

					$group                  =   array_pop($path);

					$var[C('VAR_GROUP')]    =   $group;

				}else{

					if(GROUP_NAME != C('DEFAULT_GROUP')) {

						$var[C('VAR_GROUP')]=   GROUP_NAME;

					}

				}

				if(C('URL_CASE_INSENSITIVE') && isset($var[C('VAR_GROUP')])) {

					$var[C('VAR_GROUP')]    =  strtolower($var[C('VAR_GROUP')]);

				}

			}

		}

	}



	if(C('URL_MODEL') == 0) { // 普通模式URL转换

		$url        =   __APP__.'?'.http_build_query(array_reverse($var));

		if(!empty($vars)) {

			$vars   =   urldecode(http_build_query($vars));

			$url   .=   '&'.$vars;

		}

	}else{ // PATHINFO模式或者兼容URL模式

		if(isset($route)) {

			$url    =   __APP__.'/'.rtrim($url,$depr);

		}else{

			$url    =   __APP__.'/'.implode($depr,array_reverse($var));

		}

		if(!empty($vars)) { // 添加参数

			foreach ($vars as $var => $val){

				if('' !== trim($val))   $url .= $depr . $var . $depr . urlencode($val);

			}

		}

		if($suffix) {

			$suffix   =  $suffix===true?C('URL_HTML_SUFFIX'):$suffix;

			if($pos = strpos($suffix, '|')){

				$suffix = substr($suffix, 0, $pos);

			}

			if($suffix && '/' != substr($url,-1)){

				$url  .=  '.'.ltrim($suffix,'.');

			}

		}

	}

	if(isset($anchor)){

		$url  .= '#'.$anchor;

	}

	if($domain) {

		$url   =  (is_ssl()?'https://':'http://').$domain.$url;

	}

	if($redirect) // 直接跳转URL

		redirect($url);

		else

			return $url;

}


/**
 * 获取输入参数 支持过滤和默认值
 * 使用方法:
 * <code>
 * I('id',0); 获取id参数 自动判断get或者post
 * I('post.name','','htmlspecialchars'); 获取$_POST['name']
 * I('get.'); 获取$_GET
 * </code>
 * @param string $name 变量的名称 支持指定类型
 * @param mixed $default 不存在的时候默认值
 * @param mixed $filter 参数过滤方法
 * @return mixed
 */
function I($name, $default='', $filter=null)
{
    if(strpos($name,'.')) { // 指定参数来源
        list($method,$name) =   explode('.', $name, 2);
        $method = strtoupper($method);
    }else{ // 默认为自动判断
        switch($_SERVER['REQUEST_METHOD']) {
            case 'POST':
                $method  =  'POST';
                break;
            case 'PUT':
                parse_str(file_get_contents('php://input'), $PUT);
                $method = 'PUT';
                break;
            default:
                $method  = 'GET';
                break;
        }
    }
    switch($method) {
        case 'GET'     :
        case 'POST'    :
        case 'PUT'     :
        case 'REQUEST' :
        case 'SESSION' :
        case 'COOKIE'  :
        case 'SERVER'  :
        case 'GLOBALS' :
        	$input =& ${'_'.$method};
        	break;
        default:
            return NULL;
    }

    if (isset($input[$name])) { // 取值操作
        $data       =	$input[$name];
        if($filters) {
            $filters    =   explode(',',$filters);
            foreach($filters as $filter){
                if(function_exists($filter)) {
                    $data   =   is_array($data)?array_map($filter,$data):$filter($data); // 参数过滤
                }else{
                    return	 isset($default)?$default:NULL;
                }
            }
        }
    } else { // 变量默认值
        $data = isset($default) ? $default : NULL;
    }

    return $data;
}

class F
{
	/**
	 * 判断是否为手机访问
	 * @return bool
	 */
	static public function isMobile ()
	{
		$ua = strtolower($_SERVER['HTTP_USER_AGENT']);
		// 没有强制web浏览， 并且是手机浏览器
		$isMobile = I('__ignoreMobile')!=='1'
		   && preg_match('/iemobile|mobile\ssafari|iphone\sos|android|symbian|series40/i', $ua);


		return $isMobile;
	}
}
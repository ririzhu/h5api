<?php

$local_flag = '';
if(is_file(dirname(str_replace('\\','/',dirname(__FILE__))).'/is_local.env')){
	$local_flag = '.env';
}

if($handle = opendir(__DIR__)){
  while (false !== ($file = readdir($handle))){
  	$filter_array = array('.','..');
  	$now_path = __DIR__ . DS . $file;
  	if (!in_array($file, $filter_array)) {
	  	if (is_dir($now_path)) {
	  		$include_file_arr = array();
	    	// 获取 配置文件 及 常量定义文件
	    	$include_file_arr[] = $addons_config_path = $now_path . DS . 'config' . DS . 'config.ini'.$local_flag.'.php';
	    	$include_file_arr[] = $addons_dymall_path = $now_path . DS . 'config' . DS . 'dymall'.$local_flag.'.php';
	    	foreach ($include_file_arr as $key => $value) {
		    	if (file_exists($value)) {
		    		require_once $value;
		    	}
	    	}
	  	}
  	}
  }
  closedir($handle);
}
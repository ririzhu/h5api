<?php
/**
 * 采集
 *
 */
defined('DYMall') or exit('Access Invalid!');
class collection {

    public $form_type_arr;
    public $data_state;
    public $guige_switch;
    public $cache_switch;

    public function __construct() 
    {
        $this->form_type_arr = array(
                'taobao' => array(
                    'id' => 1,
                    'name' => '淘宝'
                ),
                'tmall' => array(
                    'id' => 2,
                    'name' => '天猫'
                ),
                'alibaba' => array(
                    'id' => 3,
                    'name' => '阿里巴巴'
                ),
            );

        $this->data_state = array(
            0 => '已采集',
            1 => '已发布',
            2 => '已废弃'
        );
        $this->guige_switch = false;
        $this->cache_switch = false;
    }

    // 返回采集数据状态
    public function getCollectionStateList()
    {
        return $this->data_state;
    }

    // 检测是否允许执行 采集程序
    public function isAllowRun($url,&$message,&$item)
    {
        if(strpos($url,'taobao.com')!==false){
            $item = $this->collection_taobao($url);
            $check_flag = true;
        }elseif (strpos($url,'tmall.com')!==false) {
            $item = $this->collection_tmall($url);
            $check_flag = true;
        }elseif (strpos($url,'1688.com')!==false) {
            // detail.1688.com 替换为 m.1688.com
            $url = str_replace('detail.1688.com', 'm.1688.com', $url);
            $item = $this->collection_alibaba($url);
            $check_flag = true;
        }else{
            $message = '暂不支持此采集网址';
            $check_flag = false;
        }
        return $check_flag;
    }

    // 获取 采集数据列表
    public function getCollectionList($condition,$page=0)
    {
//        $condition['1'] = 1;
        $collection_model = Model('collection');

        $base_data = $collection_model->getBaseData($condition,'id,goods_sku,goods_name,goods_price,goods_attr,goods_images,goods_from_type,goods_from_url,goods_state,add_time,is_local',$page);
        if (!empty($base_data)) {
            // 重构返回数据
            // 时间字符串展示
            // 获取第一张图片 作为 缩略图
            $form_type_arr = low_array_column($this->form_type_arr, 'name','id');
            foreach ($base_data as $key => $value) {
                $item_data = $value;
                $item_data['add_time_str'] = $item_data['add_time'] ? date("Y-m-d", $item_data['add_time']) : '';
                $item_data['goods_from_type_str'] = $form_type_arr[$item_data['goods_from_type']] ? $form_type_arr[$item_data['goods_from_type']] : '';
                $goods_images_data = $item_data['goods_images'] ? unserialize($item_data['goods_images']) : '';

                if (is_array($goods_images_data) && !empty($goods_images_data)) {
                    $item_data['thumb_image'] = $this->getSourceItemPath($goods_images_data[0]);
                }
                if ($this->guige_switch) {
                    $goods_goods_attr_data = $item_data['goods_attr'] ? unserialize($item_data['goods_attr']) : '';
                    if (is_array($goods_images_data) && !empty($goods_goods_attr_data)) {
                        
                        if (count($goods_goods_attr_data) == 1) {
                            $item_data['guige_str'] = '单规格';
                        }else{
                            $item_data['guige_str'] = '多规格';
                        }
                    }else{
                        $item_data['guige_str'] = '无规格';
                    }
                }else{
                    $item_data['guige_str'] = '-';
                }
                $item_data['goods_state_str'] = isset($this->data_state[$item_data['goods_state']]) ? $this->data_state[$item_data['goods_state']] : '';
                unset($item_data['goods_images']);
                $base_data[$key] = $item_data;
            }
        }

        $return_last = array(
                'list' => $base_data,
                'pagination' => array(
                    'pageSize' => $page,
                    'total' => intval($collection_model->gettotalpage()),
                ),
            );

        return $return_last;
    }

    // 获取 采集信息 数据
    public function getCollectionInfo($id,$field="*")
    {
        $return = array();

        $collection_model = Model('collection');

        $info_condition = array();
        $info_condition['id'] = $id;
        $collection_info = $collection_model->getBaseInfo($info_condition, $field);

        if (!empty($collection_info)) {
            // 获取 图片资源展示
            if ($collection_info['goods_images']) {
                $goods_images = unserialize($collection_info['goods_images']);
                // 根据资源ID 获取资源信息
                if (!empty($goods_images)) {
                    $image_condition['id'] = array("IN",$goods_images);
                    $goods_images_data = $collection_model->getSourceData($image_condition,'source_url,source_local');
                    $collection_info['goods_images_arr'] = $goods_images_data = $this->getRebuildSourcePath($goods_images_data);
                }
            }
            // 获取 视频信息 展示
            if ($collection_info['goods_vdeio_source']) {
                $collection_info['goods_vdeio_source_show'] = $this->getSourceItemPath($collection_info['goods_vdeio_source'],'vedio');
                $collection_info['goods_vdeio_source_local'] = $this->getSourceItemPath($collection_info['goods_vdeio_source'],'vedio',1);
            }
            // 视频缩略图展示
            if ($collection_info['goods_vdeio_thumb']) {
                $collection_info['goods_vdeio_thumb_show'] = $this->getSourceItemPath($collection_info['goods_vdeio_thumb']);
            }
            // 规格展示
            if ($this->guige_switch && $collection_info['goods_attr']) {
                $guige_parenter_data = unserialize($collection_info['goods_attr']);

                $goods_selected_attr = $collection_info['goods_selected_attr'] ? unserialize($collection_info['goods_selected_attr']) : '';

                // 根据父级规格 获取 所有规格信息

                $spec_list = array();
                if (is_array($guige_parenter_data) && !empty($guige_parenter_data)) {
                    $array = array();
                    foreach ($guige_parenter_data as $val) {
                        // 获取用户编辑后的数据值

                        // 获取当前规格的信息
                        $item_guige_info = array();
                        $item_guige_condition = array();
                        $item_guige_condition['id'] = $val;
                        $item_guige_condition['guige_parent'] = 0;
                        $item_guige_list = $collection_model->getGuigeData($item_guige_condition);
                        if (!empty($item_guige_list) && count($item_guige_list) > 0) {
                            $item_guige_info = array_shift($item_guige_list);
                        }
                        if (!empty($item_guige_info)) {
                            $son_guige_condition = array();
                            $son_guige_condition['guige_parent'] = $val;
                            $spec_value_list = $collection_model->getGuigeData($son_guige_condition);
                            $a = array();
                            foreach ($spec_value_list as $v) {
                                $b = array();
                                $b['sp_value_id'] = $v['id'];
                                $b['sp_value_name'] = $v['guige_name'];
                                $b['is_checked'] = isset($goods_selected_attr[$val][$v['id']]) ? 1 : 0;
                                $a[] = $b;
                                // $spec_json[$val['sp_id']][$v['sp_value_id']]['sp_value_name'] = $v['sp_value_name'];
                            //     $spec_json[$val['sp_id']][$v['sp_value_id']]['sp_value_color'] = $v['sp_value_color'];

                            }
                            $array[$val]['sp_name'] = $item_guige_info['guige_name'];
                            $array[$val]['value'] = $a;
                        }

                    }
                    $spec_list = $array;
                    $collection_info['goods_attr_data'] = $spec_list;
                }

                // 获取 规格价格集合

                $spec_value = array();
                if ($collection_info['godds_attr_price']) {
                    $guige_price_data = unserialize($collection_info['godds_attr_price']);

                    $guige_price_new_data = array();
                    if (is_array($guige_price_data) && !empty($guige_price_data)) {
                        foreach ($guige_price_data as $key => $value) {
                            $item_keys = trim($key,';');
                            $item_keys_arr = explode(';', $item_keys);
                            // 获取数据库ID
                            $item_guige_condition = array();
                            $item_guige_condition['guige_parent'] = array("NEQ", 0);
                            $item_guige_condition['guige_id'] = array("IN", $item_keys_arr);
                            $guige_ids_data = $collection_model->getGuigeData($item_guige_condition,'id');
                            if (!empty($guige_ids_data)) {
                                $guige_ids_arr = low_array_column($guige_ids_data,'id');
                                asort($guige_ids_arr);
                                $guige_ids_str = implode('', $guige_ids_arr);
                                $guige_price_new_data[$guige_ids_str] = $value;
                            }
                            
                        }
                    }
                    $collection_info['guige_price_new_data'] = $guige_price_new_data;
                }

                // 获取规格 编辑后的值
                if ($collection_info['goods_selected_attr_data']) {
                    $goods_selected_attr_data = unserialize($collection_info['goods_selected_attr_data']);
                    if (!empty($goods_selected_attr_data)) {
                        $goods_selected_attr_data_arr = array();
                        foreach ($goods_selected_attr_data as $key => $value) {
                            $now_key = ltrim($key,'i_');
                            $goods_selected_attr_data_arr[$now_key] = $value;
                        }
                    }

                    $collection_info['goods_selected_attr_data'] = $goods_selected_attr_data_arr;
                }

            }

            $return = $collection_info;

        }

        return $return;

    }

    // 更新 基本信息
    public function updateBaseData($condition,$data)
    {
        $return = false;
        if (!empty($condition) && !empty($data)) {
            $collection_model = Model('collection');
            $update_flag = $collection_model->editBaseData($condition,$data);
            $return = $update_flag;
        }
        return $return;
    }

    // 获取资源列表 重构 获取 资源地址
    public function getRebuildSourcePath($sourceData,$type='images')
    {
        switch ($type) {
            case 'images':
                $upload = new UploadFile();
                $path = UPLOAD_SITE_URL.DS.ATTACH_GOODS . DS . $_SESSION ['vid'] . DS . $upload->getSysSetPath();
                break;
            case 'vedio':
                $path = UPLOAD_SITE_URL.DS.ATTACH_STORE_video.DS;
                break;
        }

        foreach ($sourceData as $key => $source_item_data) {
            $item_path = '';
            if ($source_item_data['source_local']) {
                // 本地地址
                $item_path = $path.$source_item_data['source_local'];
            }elseif($source_item_data['source_url']){
                // 网络地址
                $item_path = $source_item_data['source_url'];
            }

            $source_item_data['show_path'] = $item_path;
            $sourceData[$key] = $source_item_data;
        }

        return $sourceData;
    }

    // 获取资源地址
    // $thumb_image_id 资源ID
    // $type 资源类型
    // $data_str 是否返回数据库存储字段值
    public function getSourceItemPath($thumb_image_id,$type='images',$data_str=0)
    {
        $item_path = '';
        $collection_model = Model('collection');
        $item_image_condition = array();
        $item_image_condition['id'] = $thumb_image_id;
        $source_item_data = $collection_model->getSourceItemData($item_image_condition,'source_url,source_local');
        switch ($type) {
            case 'images':
                $upload = new UploadFile();
                $path = UPLOAD_SITE_URL.DS.ATTACH_GOODS . DS . $_SESSION ['vid'] . DS . $upload->getSysSetPath();
                break;
            case 'vedio':
                $path = UPLOAD_SITE_URL.DS.ATTACH_STORE_video.DS;
                break;
        }

        if (!empty($source_item_data)) {
            if ($source_item_data['source_local']) {
                // 本地地址
                $item_path = $data_str ? $source_item_data['source_local'] : $path.$source_item_data['source_local'];
            }elseif($source_item_data['source_url']){
                // 网络地址
                $item_path = $source_item_data['source_url'];
            }
        }

        return $item_path;
    }

    // 下载资源至服务器
    public function download_source($collection_id)
    {
        // 获取资源信息
        $collection_info = $this->getCollectionInfo($collection_id,'goods_vdeio_source,goods_vdeio_thumb,goods_images');
        $download_source_data = array();
        if (!empty($collection_info)) {
            $vedio_flag = false;
            if (isset($collection_info['goods_vdeio_source']) && $collection_info['goods_vdeio_source']) {
                // $download_source_data[] = $collection_info['goods_vdeio_source'];
                $item_source_id = $collection_info['goods_vdeio_source'];
                $vedio_flag = $this->download_item_source_update($item_source_id,'vedio');
            }else{
                $vedio_flag = true;
            }
            // if (isset($collection_info['goods_vdeio_thumb']) && $collection_info['goods_vdeio_thumb']) {
            //     // $download_source_data[] = $collection_info['goods_vdeio_thumb'];
            //     $item_source_id = $collection_info['goods_vdeio_thumb'];
            //     $this->download_item_source_update($item_source_id);
            // }
            $images_flag = false;
            if (isset($collection_info['goods_images']) && $collection_info['goods_images']) {
                $goods_images = unserialize($collection_info['goods_images']);
                // $download_source_data = array_merge($download_source_data,$goods_images);
                $all_image_num = count($goods_images);
                $localed_num = 0;
                foreach ($goods_images as $key => $value) {
                    $item_source_id = $value;
                    $save_image_item_flag = $this->download_item_source_update($item_source_id);
                    if ($save_image_item_flag) {
                        $localed_num++;
                    }
                }
                if ($localed_num == $all_image_num) {
                    $images_flag = true;
                }
            }else{
                $images_flag = true;
            }
        }
        if ($vedio_flag && $images_flag) {
            $collection_model = Model('collection');
            $base_condition['id'] = $collection_id;
            $base_data['is_local'] = 1;
            $collection_model->editBaseData($base_condition,$base_data);
            return 'ok';
        }

    }

    // 下载并更新
    public function download_item_source_update($source_id,$type='images')
    {
        $return = false;

        switch ($type) {
            case 'images':
                $upload = new UploadFile();
                $path = BASE_UPLOAD_PATH.DS.ATTACH_GOODS . DS . $_SESSION ['vid'] . DS . $upload->getSysSetPath();
                $url_path = UPLOAD_SITE_URL . '/' . ATTACH_GOODS . DS . $_SESSION['vid'] . DS . $upload->getSysSetPath();
                $other_path = 'data/upload' . '/' . ATTACH_GOODS . DS . $_SESSION['vid'] . DS . $upload->getSysSetPath();
                $file_name = $_SESSION ['vid']."_".mt_rand(10,90).time().sprintf('%03d', microtime() * 1000);
                $upload->set('default_dir', $path);
                $upload->set('thumb_width', GOODS_IMAGES_WIDTH);
                $upload->set('thumb_height', GOODS_IMAGES_HEIGHT);
                $upload->set('thumb_ext', GOODS_IMAGES_EXT);
                $create_thumb_flag = true;
                break;
            case 'vedio':
                $path = BASE_UPLOAD_PATH.DS.ATTACH_STORE_video.DS;
                $other_path = 'data/upload'.DS.ATTACH_STORE_video.DS;
                $file_name = mt_rand(10,90).time().sprintf('%03d', microtime() * 1000);
                break;
        }

        // 下载 数据并更新数据库
        $collection_model = Model('collection');
        $image_condition['id'] = $source_id;
        $goods_images_data = $collection_model->getSourceData($image_condition,'source_url,source_local');
        foreach ($goods_images_data as $key => $value) {
            if (!$value['source_local']) {
                $last_ext_arr = explode('.',$value['source_url']);
                $ext_name = array_pop($last_ext_arr);
                $last_file_name = $file_name.'.'.$ext_name;
                $item_saved_path = $path.$last_file_name;
                // 检查文件夹是否存在
                is_dir($path) ? '' : mkdir($path,0777,true);

                $save_flag = @copy($value['source_url'],$item_saved_path);
                chmod($item_saved_path , 0777);
                if(QINIU_ENABLE){
                    $re=qiniu_uploaded_file($other_path.$last_file_name,$item_saved_path);
                }else if(OSS_ENABLE){
                    $re=new_uploaded_file($other_path.$last_file_name,$item_saved_path);
                }
                if($save_flag) {
                    if ($create_thumb_flag && $upload && !(QINIU_ENABLE || OSS_ENABLE)) {
                        $upload->create_thumb($item_saved_path);
                    }
                    // 更新 数据库
                    $item_condition['id'] = $source_id;
                    $update_source_data['source_local'] = $last_file_name;
                    $data_flag = $collection_model->editSourceData($item_condition,$update_source_data);

                    if ($data_flag) {
                        if ($create_thumb_flag && $upload) {

                            $model_album = Model('imagespace');
                            $album_limit = $this->store_grade['sg_album_limit'];
                            $album_count = $model_album->getCount(array('vid' => $_SESSION['vid']));

                            $class_info = $model_album->getOne(array('vid' => $_SESSION['vid'], 'is_default' => 1), 'imagespace_cat');

                            // 取得图像大小
                            list($width, $height, $type, $attr) = getimagesize($url_path.$last_file_name);
                            
                            // 存入相册
                            $insert_array = array();
                            $insert_array['apic_name'] = $file_name;
                            $insert_array['apic_tag'] = '';
                            $insert_array['aclass_id'] = $class_info['aclass_id'];
                            $insert_array['apic_cover'] = $last_file_name;
                            $insert_array['apic_size'] = intval(filesize($item_saved_path));
                            $insert_array['apic_spec'] = $width . 'x' . $height;
                            $insert_array['upload_time'] = TIMESTAMP;
                            $insert_array['vid'] = $_SESSION['vid'];
                            $model_album->addPic($insert_array);
                        }
                    }

                    $return = $data_flag;
                }else{
                    
                }
            }else{
                $return = true;
            }
        }

        return $return;
    }

    // 采集
    // $url 采集地址
    // $extedn_data 扩展参数
    public function collection_run($url,$extend_data=array())
    {
        $state = 255;
        $data = '';
        $message = '失败';

        $url = htmlspecialchars_decode(trim($url));

        if ($url) {
            if(!preg_match('/((http|ftp|https):\/\/).*?/',$url)){
                $message = '采集地址格式错误';
            }else{
                $check_flag = false;

                // 检查是否 该链接是否 已经采集入库
                $collection_model = Model('collection');
                $check_condition = array();
                $check_condition['goods_from_url'] = $url;
                if ($extend_data['vid']) {
                    $check_condition['vid'] = $extend_data['vid'];
                }
                $check_condition['goods_state'] = array("NEQ",2);
                $check_data = $collection_model->getBaseData($check_condition);
                if (!empty($check_data)) {
                    $message = '该链接已被采集入库';
                }else{
                    $check_flag = true;
                }

                if ($check_flag) {
                    $item = array();
                    // 检测是否允许执行 采集程序
                    $check_flag = $this->isAllowRun($url,$message,$item);
                    if ($check_flag) {
                        if (!empty($item)) {
                            // 入库操作
                            $save_flag = $this->collection_save($item, $extend_data);
                            if ($save_flag == 'OK') {
                                $state = 200;
                                $message = '成功';
                            }else{
                                $message = $save_flag;
                            }
                        }else{
                            $message = '抓取失败';  
                        }
                    }
                }
            }
        }else{
            $message = '采集地址不能为空';
        }

        $return_last = array(
                'state' => $state,
                'data' => $data,
                'msg' => $message,
            );

        return $return_last;

    }

    // 获取页面内容
    public function getHtmlContent($url)
    {
        if ($this->cache_switch) {
            $cache_name = base64_encode($url);
            $cache_file = "../data/".$cache_name.".log";
            if (file_exists($cache_file)) {
                $text = file_get_contents($cache_file);
            }else{
                $text = $this->getHtmlContentRun($url);
                file_put_contents($cache_file, $text);
            }
        }else{
            $text = $this->getHtmlContentRun($url);
        }

        return $text;
    }

    public function getHtmlContentRun($url)
    {
        $text = file_get_contents($url);
        if ($text == '') {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch,CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)");

            $text = curl_exec($ch);
            curl_close($ch);
        }

        return $text;
    }

    // 淘宝采集
    public function collection_taobao($url)
    {
        // 获取页面内容
        $text = $this->getHtmlContent($url);
        $text = mb_convert_encoding($text, "UTF-8", "GBK");
        if ($text) {
            $item = array();//定义储存商品属性数组
            $urlArr = explode('&',$url);
            foreach ($urlArr as $key => $value) {
                if(strpos($value,'id=') === 0)
                    $item['itemId'] = substr($value,3);
            }
            $item['url'] = $url;
            $item['source'] = 'taobao';

            // 获取SKU编号
            $sku_num = '';
            preg_match('/\<input type=\"hidden\" name=\"item_id\" value=\"(.*?)\"/is',$text,$sku_data);
            if (!empty($sku_data) && isset($sku_data[1]) && $sku_data[1]) {
                $sku_num = $sku_data[1];
            }
            $item['sku'] = $sku_num;

            // 获取子标题
            $sub_title = '';
            preg_match('/\<div class=\"jiyoujia-subtitle\">(.*?)\<\/p>/is',$text,$sub_title_data);
            if (!empty($sub_title_data) && isset($sub_title_data[1]) && $sub_title_data[1]) {
                $sub_title = trim(strip_tags($sub_title_data[1]));
            }
            $item['sub_title'] = $sub_title;

            preg_match('/<meta name=\"keywords\" content=\"(.*?)\"/is',$text,$keyWords);//抓取关键词
            $item['keyWords'] = $keyWords[1];

            preg_match('/<div id=\"J_Title\".*?<h3 class=\"tb-main-title\" data-title=\"(.*?)">(.*?)<\/h3>/is',$text,$title);//抓取标题
            $item['title'] = trim($title[1]);

            preg_match('/<ul id=\"J_UlThumb\"(.*?)<\/ul>/is',$text,$img);//匹配左侧大图下面的小图片
            preg_match_all('/<img data-src=\"(.*?)\" \/>/is',$img[1],$imgList);//将小图片匹配成数组
            $item['attrArr'] = array();
            foreach ($imgList[1] as $key => $value) {
                $newImg = (strpos($value,'https')===false || strpos($value,'https')!=0) ? 'https:'.str_replace('.jpg_50x50','',$value) : str_replace('.jpg_50x50','',$value);
                $item['imgArr'][$key] = $newImg;
                // //方便数据插库，定义 $item['imgArr_data']
                // if($item['imgArr_data']==''){
                //     $item['imgArr_data'] = $newImg;
                // }else{
                //     $item['imgArr_data'] = $item['imgArr_data'].','.$newImg;
                // }
            }
            preg_match('/<div id=\"J_isku\"(.*?)<dl class=\"tb-amount tb-clear\"/is', $text, $itemAttrStr); //匹配可选属性区域
            preg_match_all("/<dl(.*?)<\/dl>/is", $itemAttrStr[1], $itemAttrStrList); //获取可选属性字符串组
            $item['attrArr'] = array();
            if (!empty($itemAttrStrList) && count($itemAttrStrList) > 1) {
                foreach ($itemAttrStrList[1] as $key => $value) {
                    preg_match('/<ul data-property=\"(.*?)\" class=\"/is', $value, $attrName);//获取可选属性名
                    if($attrName[1]){
                        $item['attrArr'][$key]['attrName'] =  $attrName[1];
                    }else{
                        continue;
                    }
                    preg_match_all('/<li(.*?)<span>(.*?)<\/span>.*?<\/a>/is',$value, $attrList);///获取每条可选属性
                    foreach ($attrList[1] as $k => $value) {
                        preg_match('/style=\"background:url\((.*?)\) center/is',$value,$imgSrc);
                        $tmp = $imgSrc[1] ? 'https:'.str_replace('.jpg_30x30','',$imgSrc[1]) : '';

                        // 获取当前规格值的ID标示
                        preg_match('/data-value=\"(.*?)\"/is',$value,$attr_id_data);
                        $attr_id = (!empty($attr_id_data) && isset($attr_id_data[1]) && $attr_id_data[1]) ? $attr_id_data[1] : '';
                        $attr_data_arr = array(
                                $attrList[2][$k],
                                $tmp,
                                $attr_id
                            );

                        $item['attrArr'][$key]['attrList'][$k] = implode('@#@',$attr_data_arr);
                    }
                }   
            }

            // 获取所有规格价格信息（用于入产品库时进行价格填充）
            if (!empty($item['attrArr'])) {
                // 获取规格价格
                preg_match('/skuMap     :(.*?),propertyMemoMap/is',$text,$guige_price_data);
                if (!empty($guige_price_data) && $guige_price_data[1]) {
                    $guige_price_data_arr = json_decode(trim($guige_price_data[1]),true);
                    $item['attrPriceArr'] = $guige_price_data_arr;
                }
            }

            // 获取售价
            $goods_price = '';
            preg_match('/\<input type=\"hidden\" name=\"current_price\" value= \"(.*?)\"/is',$text,$current_price_data);
            if (!empty($current_price_data) && isset($current_price_data[1]) && $current_price_data[1]) {
                $goods_price = $current_price_data[1];
            }
            $item['goods_price'] = $goods_price;

            preg_match('/<div id=\"attributes\" class=\"attributes\">(.*?)<div id=\"service\"/is',$text,$attributes);//匹配静态属性div
            preg_match_all('/<li title=\".*?>(.*?)<\/li>/is',$attributes[1],$attributArr);//匹配每一条静态属性
            $item['attributArr'] = $attributArr[1];
            // foreach ($attributArr[1] as $key => $value) {
            //     $one = explode(':',$value);
            //     $item['attributArr'][$key]['name'] = $one[0];
            //     $item['attributArr'][$key]['attr'] = $one[1];
            // }
            $attr_describe = '';
            if (!empty($item['attributArr'])) {
                $attr_html = '<table style="width: 95%;margin:0 auto;">';
                foreach ($item['attributArr'] as $key => $value) {
                    $item_html = '';
                    if ($key%3 == 0) {
                        $item_html .= '<tr>';   
                    }

                    $item_html .= '<td height="25px";>';
                    $item_html .= $value;
                    $item_html .= '</td>';

                    if ($key%3 == 2 || $key == count($item['attributArr'])-1) {
                        $item_html .= '</tr>';   
                    }
                    $attr_html .= $item_html;
                }
                $attr_html .= '</table>';
            }
            $attr_describe = $attr_html ? $attr_html : '';

            // 获取 商品详情
            $describe = '';
            preg_match('/\'\/\/desc\.alicdn\.com\/(.*?)\'/is',$text,$describe_url);// 商品详情地址
            if (!empty($describe_url)) {
                $desc_base_url = "https:";
                $desc_url = $desc_base_url.trim($describe_url[0],"'");
                $desc_url = htmlspecialchars_decode(trim($desc_url));
                $desc_data = $this->getHtmlContent($desc_url);
                $desc_data = mb_convert_encoding($desc_data, "UTF-8", "GBK");
                if ($desc_data) {
                    preg_match('/=\'(.*?)\'/is',$desc_data,$describe_data);// 商品详情内容
                    if (!empty($describe_data) && $describe_data[1]) {
                        $describe = $describe_data[1];
                    }
                }
            }
            $item['describe'] = $attr_describe.$describe;

            // 获取视频地址 及缩略图
            // https://cloud.video.taobao.com/play/u/3174443183/p/1/e/6/t/1/50091728011.mp4
            $video_thumb_url = '';
            $video_url = '';
            preg_match('/\"picUrl\":\"(.*?)\"/is',$text,$video_thumn_data);// 视频缩略图地址
            if (!empty($video_thumn_data) && $video_thumn_data[1]) {
                $video_thumb_url = 'https:'.$video_thumn_data[1];
                // 链接中的u 及 视频ID
                preg_match('/\"videoId\":\"(.*?)\"/is',$text,$video_param_u);// 视频缩略图地址参数u
                preg_match('/\"videoOwnerId\":\"(.*?)\"/is',$text,$video_param_o);// 视频缩略图地址 视频所有者ID
                if (!empty($video_param_u) && $video_param_u[1] && !empty($video_param_o) && $video_param_o[1]) {
                    $video_url = 'https://cloud.video.taobao.com/play/u/'.$video_param_o[1].'/p/1/e/6/t/1/'.$video_param_u[1].'.mp4';
                }
            }
            if ($video_thumb_url && $video_url) {
                $item['video_thumb'] = $video_thumb_url;
                $item['video_url'] = $video_url;
            }

            // 检查是否抓取失败
            if(!$item['title'] or !$item['imgArr']){
                $item = array();
            }
            return $item;
        }else{
            return array();
        }
    }

    // 天猫采集
    public function collection_tmall($url)
    {
        // 获取页面内容
        $text = $this->getHtmlContent($url);
        $text = mb_convert_encoding($text, "UTF-8", "GBK");
        if ($text) {
            $item = array();
            $now_url = str_replace('?', '&', $url);
            $urlArr = explode('&',$now_url);
            foreach ($urlArr as $key => $value) {
                if(strpos($value,'id=') === 0)
                    $item['itemId'] = substr($value,3);
            }
            $item['url'] = $url;
            $item['source'] = 'tmall';


            // 获取SKU编号
            $item['sku'] = $item['itemId'] ? $item['itemId'] : $sku_num;

            // 获取子标题
            $sub_title = '';
            preg_match('/<div class="tb-detail-hd">(.*?)<p>(.*?)<\/p>/is',$text,$sub_title_data);
            if (!empty($sub_title_data) && isset($sub_title_data[2]) && $sub_title_data[2]) {
                $sub_title = trim(strip_tags($sub_title_data[2]));
            }
            $item['sub_title'] = $sub_title;

            preg_match('/<meta name=\"keywords\" content=\"(.*?)\"/is',$text,$keyWords);//抓取关键词
            $item['keyWords'] = $keyWords[1];

            preg_match('/<title>(.*?)<\/title>/is',$text,$title);//抓取标题
            $item['title'] = trim($title[1]);

            preg_match('/<ul id=\"J_UlThumb\"(.*?)<\/ul>/is',$text,$img);//匹配左侧大图下面的小图片
            preg_match_all('/<a href=\"#\"><img src=\"(.*?)\"/is',$img[1],$imgList);//将小图片匹配成数组
            $item['imgArr'] = array();
            foreach ($imgList[1] as $key => $value) {
                if (strpos($value,'https')===false || strpos($value,'https') != 0) {
                    $newImg = 'https:'.str_replace('.jpg_60x60q90','',$value);
                }else{
                    $newImg = str_replace('.jpg_60x60q90','',$value);
                }
                $item['imgArr'][$key] = $newImg;
            }
            preg_match('/<div class=\"tb-key\">(.*?)<div class=\"tm-ser tm-clear\">/is', $text, $itemAttrStr); //匹配可选属性区域
            preg_match_all("/<dl(.*?)<\/dl>/is", $itemAttrStr[1], $itemAttrStrList); //获取可选属性字符串组
            $item['attrArr'] = array();
            foreach ($itemAttrStrList[1] as $key => $value) {
                // echo '*****';
                // print_r( $value);
                preg_match('/<ul data-property=\"(.*?)\" class=\"/is', $value, $attrName);//获取可选属性名
                if($attrName[1]){
                    $item['attrArr'][$key]['attrName'] =  $attrName[1];
                }else{
                    continue;
                }
                // echo '****';
                // echo $attrName[1];
                preg_match_all('/<a href=\"(.*?)<span>(.*?)<\/span>.*?<\/a>/is',$value, $attrList);///获取属性列表
                foreach ($attrList[1] as $k => $value) {
                    // echo '****';
                    // echo $value;
                    preg_match('/style=\"background:url\((.*?)\) center/is',$value,$imgSrc);
                    if($imgSrc[1]){
                        $tmp = strpos($imgSrc[1],'https')===false ? 'https:'.str_replace('.jpg_40x40q90','',$imgSrc[1]) : str_replace('.jpg_40x40q90','',$imgSrc[1]);
                        $item['attrArr'][$key]['attrList'][$k] = $attrList[2][$k].'@#@'.$tmp;
                    }else{
                        $item['attrArr'][$key]['attrList'][$k] = $attrList[2][$k];
                    }
                }
            }

            // 获取所有规格价格信息（用于入产品库时进行价格填充）
            if (!empty($item['attrArr'])) {
                // 获取规格价格
                preg_match('/\"skuMap\":(.*?),\"salesProp\"/is',$text,$guige_price_data);
                if (!empty($guige_price_data) && $guige_price_data[1]) {
                    $guige_price_data_arr = json_decode(trim($guige_price_data[1]),true);
                    $item['attrPriceArr'] = $guige_price_data_arr;
                }
            }

            preg_match('/<div id=\"attributes\" class=\"attributes\">(.*?)<div id=\"mall-banner\">/is',$text,$attributes);//匹配静态属性div
            preg_match_all('/<li title=\".*?>(.*?)<\/li>/is',$attributes[1],$attributArr);//匹配每一条静态属性
            $item['attributArr'] = $attributArr[1];
            $attr_describe = '';
            if (!empty($item['attributArr'])) {
                $attr_html = '<table style="width: 95%;margin:0 auto;">';
                foreach ($item['attributArr'] as $key => $value) {
                    $item_html = '';
                    if ($key%3 == 0) {
                        $item_html .= '<tr>';   
                    }

                    $item_html .= '<td height="25px";>';
                    $item_html .= $value;
                    $item_html .= '</td>';

                    if ($key%3 == 2 || $key == count($item['attributArr'])-1) {
                        $item_html .= '</tr>';   
                    }
                    $attr_html .= $item_html;
                }
                $attr_html .= '</table>';
            }
            $attr_describe = $attr_html ? $attr_html : '';

            // 获取售价
            $goods_price = '';
            // preg_match('/\<input type=\"hidden\" name=\"current_price\" value= \"(.*?)\"/is',$text,$current_price_data);
            // if (!empty($current_price_data) && isset($current_price_data[1]) && $current_price_data[1]) {
            //     $goods_price = $current_price_data[1];
            // }
            // 获取规格价格中的第一个价格
            if (!empty($item['attrPriceArr'])) {
                $first_sku_data = array_pop($item['attrPriceArr']);
                $goods_price = (!empty($first_sku_data) && isset($first_sku_data['price'])) ? $first_sku_data['price'] : '';
            }
            $item['goods_price'] = $goods_price;

            // 获取 商品详情
            $describe = '';
            preg_match('/\"\/\/desc\.alicdn\.com\/(.*?)\"/is',$text,$describe_url);// 商品详情地址
            if (!empty($describe_url)) {
                $desc_base_url = "https:";
                $desc_url = $desc_base_url.trim($describe_url[0],"\"");
                $desc_url = htmlspecialchars_decode(trim($desc_url));
                $desc_data = $this->getHtmlContent($desc_url);
                $desc_data = mb_convert_encoding($desc_data, "UTF-8", "GBK");
                if ($desc_data) {
                    preg_match('/=\'(.*?)\'/is',$desc_data,$describe_data);// 商品详情内容
                    if (!empty($describe_data) && $describe_data[1]) {
                        $describe = $describe_data[1];
                    }
                }
            }
            $item['describe'] = $attr_describe.$describe;

            // 获取视频地址 及缩略图
            https://cloud.video.taobao.com/play/u/3174443183/p/1/e/6/t/1/50091728011.mp4
            $video_thumb_url = '';
            $video_url = '';
            preg_match('/\"imgVedioPic\":\"(.*?)\"/is',$text,$video_thumn_data);// 视频缩略图地址
            if (!empty($video_thumn_data) && $video_thumn_data[1]) {
                $video_thumb_url = $video_thumn_data[1];
                // 链接中的u 及 视频ID
                preg_match('/\"imgVedioUrl\":\"(.*?)\"/is',$text,$video_data);// 视频地址
                if (!empty($video_data) && $video_data[1]) {

                    // 获取 u 参数
                    preg_match('/\/u\/(.*?)\/p/is',$video_data[1],$video_param_u);// 视频地址 U
                    $video_param_o_data = explode('/', $video_data[1]);// 视频地址 名称
                    $video_param_o = rtrim(end($video_param_o_data),'.swf');
                    // 获取文件名
                    if (!empty($video_param_u) && $video_param_u[1] && !empty($video_param_o)) {
                        $video_url = 'https://cloud.video.taobao.com/play/u/'.$video_param_u[1].'/p/1/e/6/t/1/'.$video_param_o.'.mp4';
                    }
                }
            }
            if ($video_thumb_url && $video_url) {
                $item['video_thumb'] = $video_thumb_url;
                $item['video_url'] = $video_url;
            }

            // 检查是否抓取失败
            if(!$item['title'] or !$item['imgArr']){
                $item = array();
            }

            return $item;
        }else{
            return array();
        }
    }

    // 阿里巴巴采集
    public function collection_alibaba($url)
    {
        // 获取页面内容
        $text = $this->getHtmlContent($url);
        // $text = mb_convert_encoding($text, "UTF-8", "GBK");
        if ($text) {
            $item = array();

            $url = str_replace('m.1688.com', 'detail.1688.com', $url);

            $now_url = $url;
            $urlArr = explode('/',$now_url);
            foreach ($urlArr as $key => $value) {
                if(strpos($value,'.html') != false){
                    $item['itemId'] = substr($value,0,strpos($value,'.html'));
                }
            }

            $item['url'] = $url;
            $item['source'] = 'alibaba';


            // 获取SKU编号
            $item['sku'] = $item['itemId'] ? $item['itemId'] : time();

            // 获取子标题
            $sub_title = '';
            $item['sub_title'] = $sub_title;

            preg_match('/<meta name=\"keywords\" content=\"(.*?)\"/is',$text,$keyWords);//抓取关键词
            $item['keyWords'] = $keyWords[1];

            preg_match('/\"subject\":\"(.*?)\",\"tpLogoUrl/is',$text,$title);//抓取标题
            $item['title'] = trim($title[1]);
            
            // 图片组合
            // <div id=\"d-swipe\" class=\"d-swipe\"><div class=\"swipe-content\">(.*?)<\/div><ul class=\"swipe-nav\">
            preg_match('/<div id=\"d-swipe\" class=\"d-swipe\">(.*?)<ul class=\"swipe-nav\">/is',$text,$img);//匹配图片列表
            preg_match_all('/<img src=\"(.*?)\" swipe-lazy-src=\"(.*?)\"/is',$img[1],$imgList);//将图片匹配成数组
            $item['imgArr'] = array();
            foreach ($imgList[2] as $key => $value) {
                if (strpos($value,'https')===false || strpos($value,'https') != 0) {
                    $newImg = 'https:'.str_replace('.460x460xz','',$value);
                }else{
                    $newImg = str_replace('.460x460xz','',$value);
                }
                $item['imgArr'][$key] = $newImg;
            }

            preg_match('/\"productFeatureList\":(.*?),\"promotionDetails\"/is', $text, $itemAttrStrList); //静态属性区域
            $attr_data = json_decode($itemAttrStrList[1]);
            $attributArr = array();
            foreach ($attr_data as $key => $value) {
                $attributArr[] = $value->name.':'.$value->value;
            }
            $item['attributArr'] = $attributArr;
            $attr_describe = '';
            if (!empty($item['attributArr'])) {
                $attr_html = '<table style="width: 95%;margin:0 auto;">';
                foreach ($item['attributArr'] as $key => $value) {
                    $item_html = '';
                    if ($key%3 == 0) {
                        $item_html .= '<tr>';   
                    }

                    $item_html .= '<td height="25px";>';
                    $item_html .= $value;
                    $item_html .= '</td>';

                    if ($key%3 == 2 || $key == count($item['attributArr'])-1) {
                        $item_html .= '</tr>';   
                    }
                    $attr_html .= $item_html;
                }
                $attr_html .= '</table>';
            }
            $attr_describe = $attr_html ? $attr_html : '';

            // 获取售价
            $goods_price = '';
            preg_match('/\"discountPrice\":\"(.*?)\"/is',$text,$current_price_data);
            if (!empty($current_price_data) && isset($current_price_data[1]) && $current_price_data[1]) {
                $goods_price = $current_price_data[1];
            }
            if (!$goods_price) {
                // 无当前价格时  获取第一个阶梯价格
                // 获取阶梯价格
                preg_match('/\"discountPriceRanges\":(.*?),\"freeDeliverFee/is',$text,$current_more_data);
                if (!empty($current_more_data) && isset($current_more_data[1]) && $current_more_data[1]) {
                    $more_price_data = json_decode($current_more_data[1]);
                    $goods_price = $more_price_data[0]->price;
                }
            }
            $item['goods_price'] = $goods_price;

            // 获取 商品详情
            $describe = '';
            preg_match('/\"detailUrl\":\"(.*?)\",\"discount/is',$text,$describe_url);// 商品详情地址
            if (!empty($describe_url[1])) {
                $desc_base_url = "https:";
                $desc_url = $desc_base_url.trim($describe_url[1],"\"");
                $desc_url = htmlspecialchars_decode(trim($desc_url));
                $desc_data = $this->getHtmlContent($desc_url);
                $desc_data = mb_convert_encoding($desc_data, "UTF-8", "GBK");
                if ($desc_data) {
                    preg_match('/\"content\":\"(.*?)\"};/is',$desc_data,$describe_data);// 商品详情内容
                    if (!empty($describe_data) && $describe_data[1]) {
                        $describe = $describe_data[1];
                    }
                }
            }
            $item['describe'] = $attr_describe.$describe;

            // 获取视频地址 及缩略图
            $video_thumb_url = '';
            $video_url = '';
            preg_match('/\"wirelessVideoInfo\":(.*?),\"catids/is',$text,$video_data);// 视频信息
            if (!empty($video_data) && $video_data[1]) {
                $video_all_data = json_decode($video_data[1]);

                if (!empty($video_all_data)) {
                    if ($video_all_data->coverUrl) {
                        $video_thumb_url = $video_all_data->coverUrl;
                    }
                    if (!empty($video_all_data->videoUrl)) {
                        $video_url = isset($video_all_data->videoUrl->ios) ? $video_all_data->videoUrl->ios : '';
                    }
                }
            }
            if ($video_thumb_url && $video_url) {
                $item['video_thumb'] = $video_thumb_url;
                $item['video_url'] = $video_url;
            }

            // 检查是否抓取失败
            if(!$item['title'] or !$item['imgArr']){
                $item = array();
            }

            return $item;
        }else{
            return array();
        }
    }

    // 采集完成后的数据入库操作
    public function collection_save($data, $extend_data=array())
    {
        $result = false;
        $run_flag = false;

        if (isset($this->form_type_arr[$data['source']])) {
            $run_flag = true;
        }
        if ($run_flag) {
            $collection_model = Model('collection');
            try {
                $collection_model->beginTransaction();

                // 构建 基础数据需要保存的数组

                $source_save_data['imgArr'] = '';
                $source_save_data['video_thumb'] = '';
                $source_save_data['video_url'] = '';
                $source_save_data['attrArr'] = '';

                // 资源表数据存储
                if (!empty($data['imgArr'])) {
                    $source_save_data['imgArr'] = $this->source_save($data['imgArr'],$extend_data);
                }
                if (!empty($data['video_thumb'])) {
                    $source_save_data['video_thumb'] = $this->source_save($data['video_thumb'],$extend_data);
                }
                if (!empty($data['video_url'])) {
                    $source_save_data['video_url'] = $this->source_save($data['video_url'],$extend_data);
                }
                // 规格扩展数据存储
                if (!empty($data['attrArr']) && $this->guige_switch) {
                    $source_save_data['attrArr'] = $this->guige_save($data['attrArr'],$extend_data);
                }

                $base_data = array();
                $base_data['goods_sku'] = $data['sku'];
                $base_data['goods_name'] = $data['title'];
                $base_data['goods_sub_title'] = $data['sub_title'];
                $base_data['goods_en_name'] = '';
                $base_data['goods_keywords'] = $data['keyWords'];
                $base_data['goods_images'] = (isset($source_save_data['imgArr']) && !empty($source_save_data['imgArr'])) ? serialize($source_save_data['imgArr']) : ''; // 存储资源表
                $base_data['goods_vdeio_thumb'] = $source_save_data['video_thumb'];  // 存储资源表
                $base_data['goods_vdeio_source'] = $source_save_data['video_url'];  // 存储资源表
                $base_data['goods_content'] = $data['describe'];
                $base_data['goods_category'] = 0;
                $base_data['goods_price'] = $data['goods_price'];
                if ($this->guige_switch) {
                    $base_data['goods_attr'] = (isset($source_save_data['attrArr']) && !empty($source_save_data['attrArr'])) ? serialize($source_save_data['attrArr']) : ''; // 存储规格表
                    $base_data['godds_attr_price'] = (isset($data['attrPriceArr']) && !empty($data['attrPriceArr'])) ? serialize($data['attrPriceArr']) : '';
                }
                $base_data['goods_sub_attr'] = (isset($data['attributArr']) && !empty($data['attributArr'])) ? serialize($data['attributArr']) : '';
                $base_data['goods_from_type'] = $this->form_type_arr[$data['source']]['id'];
                $base_data['goods_from_url'] = $data['url'];
                $base_data['vid'] = (isset($extend_data['vid']) && $extend_data['vid']) ? $extend_data['vid'] : '';
                $base_data['user_id'] = (isset($extend_data['user_id']) && $extend_data['user_id']) ? $extend_data['user_id'] : '';
                $base_data['add_time'] = time();
                $base_data['goods_state'] = 0;
                $base_data['is_local'] = 0;
                if($extend_data['cct_limit'] > 0){
                    $base_data['cct_state'] = 2;
                }


                $save_base_data_flag = $collection_model->saveBaseData($base_data);
                if (!$save_base_data_flag) {
                    throw new Exception('采集入库添加操作失败');
                }else{
                    $this->asyncCct($data['sku'],$save_base_data_flag,$extend_data['cct_limit']);
                    $result = 'OK';
                }

                $collection_model->commit();
            } catch (Exception $e) {
                $collection_model->rollback();
                $run_flag = false;
                $result = $e->getMessage();
                // throw new Exception($e->getMessage());
            }
        }

        return $result;

    }
    /*
     * 异步采集评论信息
     */
    public function asyncCct($id,$insertId,$limit='')
    {
        if(empty($id) || empty($insertId)){
            return false;
        }
        $url = C('base_vendor_url').'/index.php?app=cct_class&mod=cct_function&id='.$id.'&insertId='.$insertId.'&limit='.$limit;
        //	登录cookie
        $name = 'vendor_key';
        $key = defined('COOKIE_PRE') ? COOKIE_PRE . $name : strtoupper(substr(md5(MD5_KEY), 0, 4)) . '_' . $name;
        $value = cookie('vendor_key');
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);//$url后面要加传递的参数
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);//1表示返回的值不直接
        curl_setopt($ch,CURLOPT_HEADER,0);//声明header头,此时是不用声明,如果写成1会在返回值里打印header头
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);//验证证书,可以跨https访问,此时不验证
        curl_setopt($ch,CURLOPT_TIMEOUT_MS,100);//限制连接时间,异步时可以使用
//		curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        curl_setopt($ch, CURLOPT_COOKIE,$key.'='.$value);
        curl_exec($ch);
        curl_close($ch);
    }

    public function cct()
    {
        set_time_limit(0);
        $all = 0;
        $begin = 1;
        $goods_id = 582867301157;
        $goods_id = 571327874248;
        $goods_id = 580269011518;
//            $goods_id = 583983864019;
//            $goods_id = 581737298075;
        while ($begin) {
            $url = 'https://rate.taobao.com/feedRateList.htm?auctionNumId='.$goods_id.'&currentPageNum='.$begin.'&pageSize=20&orderType=feedbackdate';
            $headers = [
                'Upgrade-Insecure-Requests:1',
                'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                // 'Host:rate.tmall.com',
                'User-Agent:Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36 SE 2.X MetaSr 1.0',
                'Referer:https://item.taobao.com/item.htm?t=1&spm=a1z10.5-c-s.w4002-18863916469.23.21d931c9WM07MJ&id=582867301157',
                'Cookie:_m_h5_tk=02003af02da43f015c58b68ae723152b_1546528631749; _m_h5_tk_enc=c3b87dea14ae329fdcfce2f7e6b64a8e; cna=2sG0FHDANkwCAdIMRULY44zC; thw=cn; t=341f729d63e34712fd202a6e9956267b; cookie2=14e2dea3d2225fce557bbf986ecc0210; _cc_=V32FPkk/hw==; tg=0; enc=YGzBLPIs7ULfOXcNQnEBViPwrTlq1wkWvNeHUU3QzV6ink0D75jIgKy3mbJZUH/b1madTnlUdARMubQkqnDTjw==; hng=CN|zh-CN|CNY|156; mt=ci=0_0; v=0; _tb_token_=f68636b30eeb3; x5sec=7b22726174656d616e616765723b32223a223534383835343130633837326138653161306564313937663132633038666335434a6d54792b4546454d4c49384e3646672b797162673d3d227d; l=aB0iN6n_yuz5IQ2KsMa_mWpUo707mN5PjCOIANsm0TEhNziL1saC6ehhB_IBkSWSV1k0t0vMAtL-W6w..; isg=BC4uZ7_3_iv9wArQInOgovGccYQwh_XAqAOsqlj9aTJZO9mVybzeOK119-dy-OpB',
//                'Cookie:x5sec=7b22726174656d616e616765723b32223a223133616264336166646261666264353165616332613838366131643235643565434d2f4d754f4546454f6a75334a61523735653049686f4d4d6a4d344e6a6b314d6a55334d447378227d;'
//                'Cookie:x5sec=7b22726174656d616e616765723b32223a223332356266376132613539356564643330646566363535363262646466326631434c4359742b4546454a2f626b6157497035726d63413d3d227d'
            ];
            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL,$url);//$url后面要加传递的参数
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);//1表示返回的值不直接

            curl_setopt($ch,CURLOPT_HEADER,0);//声明header头,此时是不用声明,如果写成1会在返回值里打印header头
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);//验证证书,此时不验证
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//同上
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            // curl_setopt($ch, CURLOPT_COOKIE,'x5sec=7b22726174656d616e616765723b32223a223332356266376132613539356564643330646566363535363262646466326631434c4359742b4546454a2f626b6157497035726d63413d3d227d;');
            $json_res = curl_exec($ch);
            curl_close($ch);
            $res = strchr($json_res,'{');
            $res = substr($res,0,strrpos($res,'}')+1);


            $arr_res = json_decode($res,true);
//             dd($arr_res );
            $comment = $arr_res['comments'];
            if(!$comment){
                break;
            }
            $all += intval(count($comment));
            foreach($comment as $k=>$v){
                echo $v['date'].'<br>'.$v['content'].'------------------------来自'.$v['user']['nick'];
//                echo '<img src='.$v['user']['avatar'].'>';
                echo '<br>';
                foreach($v['photos'] as $kk=>$vv){
//                    echo '<img src='.$vv['url'].'>';
                }

//                echo '<br>';
//                echo '<br>';
            }
            $begin++;
        }
        echo '<hr>';
        echo '总计'.$all.'条评论';


    }
    // 采集的数据 编辑操作
    public function collection_update($collection_id,$form_data, $extend_data=array())
    {

        $state = 255;
        $data = '';
        $message = '失败';

        $collection_model = Model('collection');

        // 整理选中的规格数据
        $spec_value_data = is_array($form_data['spec']) ? serialize($form_data['sp_val']) : serialize(null);
        $spec_data = is_array($form_data['spec']) ? serialize($form_data['spec']) : serialize(null);

        try {
            $collection_model->beginTransaction();

            $sp_names = $form_data['sp_name'];
            if (!empty($sp_names)) {
                // 更新规格名称
                foreach ($sp_names as $key => $value) {
                    $item_guige_condition = array();
                    $item_guige_condition['id'] = $key;
                    $update_guige_item_data = array();
                    $update_guige_item_data['guige_name'] = $value;
                    $update_guige_item_flag = $collection_model->editGuigeData($item_guige_condition,$update_guige_item_data);
                    if ($update_guige_item_flag === false) {
                        throw new Exception('规格更新操作失败');
                    }
                }
            }

            // 更新规格值的内容
            $sp_vals = $form_data['sp_val'];
            if (!empty($sp_vals)) {
                // 更新规格值名称
                foreach ($sp_vals as $key => $value) {
                    foreach ($value as $k => $val) {
                        $item_guige_condition = array();
                        $item_guige_condition['id'] = $k;
                        $update_guige_item_data = array();
                        $update_guige_item_data['guige_name'] = $val;
                        $update_guige_item_flag = $collection_model->editGuigeData($item_guige_condition,$update_guige_item_data);
                        if ($update_guige_item_flag === false) {
                            throw new Exception('规格更新操作失败');
                        }
                    }
                }
            }

            // 更新基础数据
            $base_data = array();
            $base_data['goods_sku'] = $form_data['goods_sku'];
            $base_data['goods_name'] = $form_data['goods_name'];
            $base_data['goods_sub_title'] = $form_data['goods_sub_title'];
            $base_data['goods_price'] = $form_data['goods_price'];
            $base_data['goods_content'] = $form_data['g_body'];

            $base_data['goods_selected_attr'] = $spec_value_data;
            $base_data['goods_selected_attr_data'] = $spec_data;

            $edit_base_condition = array();
            $edit_base_condition['id'] = $collection_id;
            $base_flag = $collection_model->editBaseData($edit_base_condition,$base_data);
            if ($base_flag === false) {
                throw new Exception('采集数据更新操作失败');
            }else{
                $state = 200;
                $message = '更新成功';
            }

            $collection_model->commit();
        } catch (Exception $e) {
            $collection_model->rollback();
            $result = $e->getMessage();
        }

        $return_last = array(
                'state' => $state,
                'data' => $data,
                'msg' => $message,
            );

        return $return_last;
    }

    // 资源存储
    public function source_save($source,$extend_data=array())
    {
        $return = '';

        if (!empty($source)) {
            $collection_model = Model('collection');

            if (is_array($source)) {
                $condition['source_url'] = array('IN',$source);
                if ($extend_data['vid']) {
                    $condition['vid'] = $extend_data['vid'];
                }

                $all_insert_data = array();
                foreach ($source as $key => $value) {
                    $all_insert_data[$key]['source_url'] = $value;
                    if ($extend_data['vid']) {
                        $all_insert_data[$key]['vid'] = $extend_data['vid'];
                    }
                    $all_insert_data[$key]['add_time'] = time();
                }
                // 多条存储
                $save_flag = $collection_model->saveSourceData($all_insert_data, true);

                if ($save_flag) {
                    // 获取资源ID集合
                    //只查刚刚插入的条件
                    $condition['add_time'] = ['in',low_array_column($all_insert_data,'add_time')];
                    $data = $collection_model->getSourceData($condition,'id');
                    $source_ids = '';
                    if (!empty($data)) {
                        $source_ids = low_array_column($data, 'id');
                    }
                    $return = $source_ids;
                }else{
                    $return = false;
                }
            }else{
                // 单条存储
                $insert_data = array();
                $insert_data['source_url'] = $source;
                $insert_data['add_time'] = time();
                if ($extend_data['vid']) {
                    $insert_data['vid'] = $extend_data['vid'];
                }

                $save_flag = $collection_model->saveSourceData($insert_data);

                if ($save_flag) {
                    $return = $save_flag;
                }else{
                    $return = false;
                }
            }
        }

        return $return;
    }

    // 规格存储
    public function guige_save($guige_data,$extend_data=array())
    {
        $return = '';

        if (!empty($guige_data)) {

            $collection_model = Model('collection');

            $guige_parent_data = array();

            foreach ($guige_data as $key => $value) {
                $itemAttrName = (isset($value['attrName']) && $value['attrName']) ? $value['attrName'] : '';
                $itemAttrValues = (isset($value['attrList']) && is_array($value['attrList']) && !empty($value['attrList'])) ? $value['attrList'] : '';
                if ($itemAttrName && $itemAttrValues) {
                    // 插入规格名称
                    $parent_data = array();
                    $parent_data['guige_name'] = $itemAttrName;
                    $parent_data['guige_parent'] = 0;
                    $parent_data['add_time'] = time();
                    $parent_id = $collection_model->saveGuigeData($parent_data);

                    if ($parent_id) {
                        // 重组规格值数组
                        $itemAttrRebuildedValues = array();
                        foreach ($itemAttrValues as $key => $value) {
                            $source_id = 0;
                            $item_value = explode('@#@', $value);
                            if(!empty($item_value[1])){
                                // 将图片存粗进资源表中
                                $source_id = $this->source_save($item_value[1],$extend_data);
                            }
                            $itemAttrRebuildedValues[$key]['guige_name'] = $item_value[0];
                            $itemAttrRebuildedValues[$key]['guige_image'] = $source_id;
                            $itemAttrRebuildedValues[$key]['guige_id'] = $item_value[2];
                            $itemAttrRebuildedValues[$key]['guige_parent'] = $parent_id;
                            $itemAttrRebuildedValues[$key]['add_time'] = time();
                        }
                        $save_flag = $collection_model->saveGuigeData($itemAttrRebuildedValues,true);
                        if ($save_flag) {
                            array_push($guige_parent_data,$parent_id);
                        }else{
                            throw new Exception('规格添加失败');
                        }
                    }else{
                        throw new Exception('规格添加失败');
                    }

                    if (!empty($guige_parent_data)) {
                        $return = $guige_parent_data;
                    }

                }
            }
        }

        return $return;

    }

    // 添加规格值
    public function guige_value_add($guige_data)
    {
        $collection_model = Model('collection');

        return $collection_model->saveGuigeData($guige_data);
    }

}
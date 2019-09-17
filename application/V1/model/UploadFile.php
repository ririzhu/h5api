<?php
namespace app\v1\model;

use think\Model;

class UploadFile extends Model
{
    /**
     * 文件存储路径
     */
    private $save_path;
    /**
     * 允许上传的文件类型
     */
    private $allow_type=array();
    private $allow_img_type=array('jpg','png','jpeg','gif','bmp','tiff','tga');
    /**
     * 允许的最大文件大小，单位为KB
     */
    private $max_size = '1024';
    /**
     * 改变后的图片宽度
     */
    private $thumb_width = 0;
    /**
     * 改变后的图片高度
     */
    private $thumb_height = 0;
    /**
     * 生成扩缩略图后缀
     */
    private $thumb_ext = false;
    /**
     * 允许的图片最大高度，单位为像素
     */
    private $upload_file;
    /**
     * 是否删除原图
     */
    private $ifremove = false;
    /**
     * 上传文件名
     */
    public $file_name;
    /**
     * 上传文件后缀名
     */
    private $ext;
    /**
     * 上传文件新后缀名
     */
    private $new_ext;
    /**
     * 默认文件存放文件夹
     */
    private $default_dir = ATTACH_PATH;
    /**
     * 错误信息
     */
    public $error = '';
    /**
     * 生成的缩略图，返回缩略图时用到
     */
    public $thumb_image;
    /**
     * 是否立即弹出错误提示
     */
    private $if_show_error = false;
    /**
     * 是否只显示最后一条错误
     */
    private $if_show_error_one = false;
    /**
     * 文件名前缀
     *
     * @var string
     */
    private $fprefix;

    /**
     * 是否允许填充空白，默认允许
     *
     * @var unknown_type
     */
    private $filling = true;

    private $config;
    /**
     * 初始化
     *
     *	$upload = new UploadFile();
     *	$upload->set('default_dir','upload');
     *	$upload->set('max_size',1024);
     *	//生成4张缩略图，宽高依次如下
     *	$thumb_width	= '300,600,800,100';
     *	$thumb_height	= '300,600,800,100';
     *	$upload->set('thumb_width',	$thumb_width);
     *	$upload->set('thumb_height',$thumb_height);
     *	//4张缩略图名称扩展依次如下
     *	$upload->set('thumb_ext',	'_small,_mid,_max,_tiny');
     *	//生成新图的扩展名为.jpg
     *	$upload->set('new_ext','jpg');
     *	//开始上传
     *	$result = $upload->upfile('file');
     *	if (!$result){
     *		echo '上传成功';
     *	}
     *
     */
    function __construct(){
        $this->config['thumb_type'] = Config('thumb.cut_type');
        //加载语言包
        //Language::read('core_lang_index');
    }
    /**
     * 设置
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function set($key,$value){
        $this->$key = $value;
    }
    /**
     * 读取
     */
    public function get($key){
        return $this->$key;
    }
    /**
     * 上传操作
     *
     * @param string $field 上传表单名
     * @return bool
     */
    public function upfile($field,$video_type=''){

        //上传文件
        $this->upload_file = $_FILES[$field];
        if ($this->upload_file['tmp_name'] == ""){
            $this->setError(lang('找不到临时文件'));
            return false;
        }

        //对上传文件错误码进行验证
        $error = $this->fileInputError();
        if (!$error){
            return false;
        }
        //验证是否是合法的上传文件
        if(!is_uploaded_file($this->upload_file['tmp_name'])){
            $this->setError(lang('非法上传文件'));
            return false;
        }

        //验证文件大小
        if ($this->upload_file['size']==0){
            $error = lang('禁止上传空文件');
            $this->setError($error);
            return false;
        }
        if($this->upload_file['size'] > $this->max_size*1024*2){
            $error = lang('上传文件大小不能超过').$this->max_size.'KB';
            $this->setError($error);
            return false;
        }

        //文件后缀名
        $tmp_ext = explode(".", $this->upload_file['name']);
        $tmp_ext = $tmp_ext[count($tmp_ext) - 1];
        $this->ext = strtolower($tmp_ext);

        //不是视频时候
        if(empty($video_type)){
            //验证文件格式是否为系统允许
            if(count($this->allow_type)>0 && !in_array($this->ext,$this->allow_type)){
                $error = lang('不允许上传，允许类型为').implode(',',$this->allow_type);
                $this->setError($error);
                return false;
            }

            if(in_array($this->ext,$this->allow_img_type)) {
                //检查是否为有效图片
                if (!$image_info = @getimagesize($this->upload_file['tmp_name'])) {
                    $error = lang('非法图像文件');
                    $this->setError($error);
                    return false;
                }
            }
        }

        //设置图片路径
        $this->save_path = rtrim($this->setPath(),DS);

        //设置文件名称
        if(empty($this->file_name)){
            $this->setFileName();
        }
        if(in_array($this->ext,$this->allow_img_type)) {
            //是否需要生成缩略图
            $ifresize = false;
            if ($this->thumb_width && $this->thumb_height && $this->thumb_ext) {
                $thumb_width = explode(',', $this->thumb_width);
                $thumb_height = explode(',', $this->thumb_height);
                $thumb_ext = explode(',', $this->thumb_ext);
                if (count($thumb_width) == count($thumb_height) && count($thumb_height) == count($thumb_ext)) $ifresize = true;
            }

            //计算缩略图的尺寸
            if ($ifresize){
                for ($i=0;$i<count($thumb_width);$i++){
                    $imgscaleto = ($thumb_width[$i] == $thumb_height[$i]);
                    if ($image_info[0] < $thumb_width[$i]) $thumb_width[$i] = $image_info[0];
                    if ($image_info[1] < $thumb_height[$i]) $thumb_height[$i] = $image_info[1];
                    $thumb_wh = $thumb_width[$i]/$thumb_height[$i];
                    $src_wh	 = $image_info[0]/$image_info[1];
                    if ($thumb_wh <= $src_wh){
                        $thumb_height[$i] = $thumb_width[$i]*($image_info[1]/$image_info[0]);
                    }else{
                        $thumb_width[$i] = $thumb_height[$i]*($image_info[0]/$image_info[1]);
                    }
                    if ($imgscaleto){
                        $scale[$i]  = $src_wh > 1 ? $thumb_width[$i] : $thumb_height[$i];
//					if ($this->config['thumb_type'] == 'gd'){
//						$scale[$i]  = $src_wh > 1 ? $thumb_width[$i] : $thumb_height[$i];
//					}else{
//						$scale[$i]  = $src_wh > 1 ? $thumb_width[$i] : $thumb_height[$i];
//					}
                    }else{
                        $scale[$i] = 0;
                    }
//				if ($thumb_width[$i] == $thumb_height[$i]){
//					$scale[$i] = $thumb_width[$i];
//				}else{
//					$scale[$i] = 0;
//				}
                }
            }

        }




        //是否立即弹出错误
        if($this->if_show_error){
            echo "<script type='text/javascript'>alert('". ($this->if_show_error_one ? $error : $this->error) ."');history.back();</script>";
            die();
        }
        if ($this->error != '') return false;

        if(OSS_ENABLE){
            $re=new_uploaded_file('data/upload/'.$this->save_path.DS.$this->file_name,$this->upload_file['tmp_name']);
        }else if(QINIU_ENABLE){
            $re=qiniu_uploaded_file('data/upload/'.$this->save_path.DS.$this->file_name,$this->upload_file['tmp_name']);
        }else{
            $re=@move_uploaded_file($this->upload_file['tmp_name'],BASE_UPLOAD_PATH.DS.$this->save_path.DS.$this->file_name);
        }

        if($re){

            if(!empty($video_type)){
                return $this->file_name;
            }

            if(OSS_ENABLE){
                $this->thumb_image = $this->file_name;
                return $this->file_name;
            }else if(QINIU_ENABLE){
                $this->thumb_image = $this->file_name;
                return $this->file_name;
            }

            //产生缩略图
            if ($ifresize){
                $resizeImage	= new ResizeImage();
                $save_path = rtrim(BASE_UPLOAD_PATH.DS.$this->save_path,'/');
                for ($i=0;$i<count($thumb_width);$i++){
                    $resizeImage->newImg(
                        $save_path.DS.$this->file_name,
                        $thumb_width[$i],
                        $thumb_height[$i],
                        $scale[$i],
                        $thumb_ext[$i].'.',
                        $save_path,
                        $this->filling
                    );
                    if ($i==0) {
                        $resize_image = explode('/',$resizeImage->relative_dstimg);
                        $this->thumb_image = $resize_image[count($resize_image)-1];
                    }
                }
            }
            //删除原图
            if ($this->ifremove && is_file(BASE_UPLOAD_PATH.DS.$this->save_path.DS.$this->file_name) && OSS_ENABLE!=1) {
                delete_file(BASE_UPLOAD_PATH.DS.$this->save_path.DS.$this->file_name);
            }
            return true;
        }else{
            $this->setError(Language::get('没有copy操作权限'));
            return false;
        }
//		$this->setErrorFileName($this->upload_file['tmp_name']);
        return $this->error;
    }

    /**
     * 裁剪指定图片
     *
     * @param string $field 上传表单名
     * @return bool
     */
    public function create_thumb($pic_path){
        if (!file_exists($pic_path)) return ;

        //是否需要生成缩略图
        $ifresize = false;
        if ($this->thumb_width && $this->thumb_height && $this->thumb_ext){
            $thumb_width 	= explode(',',$this->thumb_width);
            $thumb_height 	= explode(',',$this->thumb_height);
            $thumb_ext 		= explode(',',$this->thumb_ext);
            if (count($thumb_width) == count($thumb_height) && count($thumb_height) == count($thumb_ext)) $ifresize = true;
        }
        $image_info = @getimagesize($pic_path);
        //计算缩略图的尺寸
        if ($ifresize){
            for ($i=0;$i<count($thumb_width);$i++){
                $imgscaleto = ($thumb_width[$i] == $thumb_height[$i]);
                if ($image_info[0] < $thumb_width[$i]) $thumb_width[$i] = $image_info[0];
                if ($image_info[1] < $thumb_height[$i]) $thumb_height[$i] = $image_info[1];
                $thumb_wh = $thumb_width[$i]/$thumb_height[$i];
                $src_wh	 = $image_info[0]/$image_info[1];
                if ($thumb_wh <= $src_wh){
                    $thumb_height[$i] = $thumb_width[$i]*($image_info[1]/$image_info[0]);
                }else{
                    $thumb_width[$i] = $thumb_height[$i]*($image_info[0]/$image_info[1]);
                }
                if ($imgscaleto){
                    $scale[$i]  = $src_wh > 1 ? $thumb_width[$i] : $thumb_height[$i];
                }else{
                    $scale[$i] = 0;
                }
            }
        }
        //产生缩略图
        if ($ifresize){
            $resizeImage	= new ResizeImage();
            $save_path = rtrim(BASE_UPLOAD_PATH.DS.$this->save_path,'/');
            for ($i=0;$i<count($thumb_width);$i++){
//				$resizeImage->newImg($save_path.DS.$this->file_name,$thumb_width[$i],$thumb_height[$i],$scale[$i],$thumb_ext[$i].'.',$save_path,$this->filling);
                $resizeImage->newImg($pic_path,$thumb_width[$i],$thumb_height[$i],$scale[$i],$thumb_ext[$i].'.',dirname($pic_path),$this->filling);
            }
        }
    }
    /**
     * 获取上传文件的错误信息
     *
     * @param string $field 上传文件数组键值
     * @return string 返回字符串错误信息
     */
    private function fileInputError(){
        switch($this->upload_file['error']) {
            case 0:
                //文件上传成功
                return true;
                break;

            case 1:
                //上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值
                $this->setError(Language::get('文件大小超出系统设置'));
                return false;
                break;

            case 2:
                //上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值
                $this->setError(Language::get('文件大小超出系统设置'));
                return false;
                break;

            case 3:
                //文件只有部分被上传
                $this->setError(Language::get('文件仅部分被上传'));
                return false;
                break;

            case 4:
                //没有文件被上传
                $this->setError(Language::get('没有文件被上传'));
                return false;
                break;

            case 6:
                //找不到临时文件夹
                $this->setError(Language::get('找不到临时文件夹'));
                return false;
                break;

            case 7:
                //文件写入失败
                $this->setError(Language::get('文件写入失败'));
                return false;
                break;

            default:
                return true;
        }
    }

    /**
     * 设置存储路径
     *
     * @return string 字符串形式的返回结果
     */
    public function setPath(){

        if(!OSS_ENABLE) {
            /**
             * 判断目录是否存在，如果不存在 则生成
             */

            if (!is_dir(BASE_UPLOAD_PATH . DS . $this->default_dir)) {
                $dir = $this->default_dir;
                $dir_array = explode(DS, $dir);
                $tmp_base_path = BASE_UPLOAD_PATH;
                foreach ($dir_array as $k => $v) {
                    $tmp_base_path = $tmp_base_path . DS . $v;
                    if (!is_dir($tmp_base_path)) {
                        if (!@mkdir($tmp_base_path, 0755,true)) {
                            $this->setError(Language::get('创建目录(') . $tmp_base_path . Language::get(')失败'));
                            return false;
                        }
                    }
                }
                unset($dir, $dir_array, $tmp_base_path);
            }

            //设置权限
            @chmod(BASE_UPLOAD_PATH . DS . $this->default_dir, 0755);

            //判断文件夹是否可写
            if (!is_writable(BASE_UPLOAD_PATH . DS . $this->default_dir)) {
                $this->setError(Language::get('目录(') . $this->default_dir . Language::get(')不能创建文件，请修改权限后再进行上传'));
                return false;
            }
        }
        return $this->default_dir;
    }

    /**
     * 设置文件名称 不包括 文件路径
     *
     * 生成(从2000-01-01 00:00:00 到现在的秒数+微秒+四位随机)
     */
    private function setFileName(){
        $tmp_name = sprintf('%010d',time() - 946656000)
            . sprintf('%03d', microtime() * 1000)
            . sprintf('%04d', mt_rand(0,9999));
        $this->file_name = (empty ( $this->fprefix ) ? '' : $this->fprefix . '_')
            . $tmp_name . '.' . ($this->new_ext == '' ? $this->ext : $this->new_ext);
    }

    /**
     * 设置错误信息
     *
     * @param string $error 错误信息
     * @return bool 布尔类型的返回结果
     */
    private function setError($error){
        $this->error = $error;
    }

    /**
     * 根据系统设置返回商品图片保存路径
     */
    public function getSysSetPath(){
        switch(C('image_dir_type')){
            case "1":
                //按文件类型存放,例如/a.jpg
                $subpath = "";
                break;
            case "2":
                //按上传年份存放,例如2011/a.jpg
                $subpath = date("Y",time()) . "/";
                break;
            case "3":
                //按上传年月存放,例如2011/04/a.jpg
                $subpath = date("Y",time()) . "/" . date("m",time()) . "/";
                break;
            case "4":
                //按上传年月日存放,例如2011/04/19/a.jpg
                $subpath = date("Y",time()) . "/" . date("m",time()) . "/" . date("d",time()) . "/";
        }
        return $subpath;
    }
}
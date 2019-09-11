<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/12/19
 * Time: 22:19
 */
class share_image
{
    public function __construct()
    {
    }
    public function makeimg($data)
    {
        $gData = [
            'pic' => $data['pic'],
            'title' =>$data['title'],
            'price' => $data['price'],
            'detail'=>$data['detail']
        ];
        //直接输出
//        $this->createSharePng($gData,$data['qr_code']);
        //输出到图片
        $res = $this->createSharePng($gData,$data['qr_code'],BASE_UPLOAD_PATH.'/mobile/share/'.$data['gid'].'.png');
        if(!$res){
            return false;
        }
        return $res;
    }
    /**
     * 分享图片生成
     * @param $gData  商品数据，array
     * @param $codeName 二维码图片
     * @param $fileName string 保存文件名,默认空则直接输入图片
     */
    public function createSharePng($gData,$codeName,$fileName = ''){
    //创建画布
    $im = imagecreatetruecolor(750, 1300);

    //填充画布背景色
    $color = imagecolorallocate($im, 255, 255, 255);
    imagefill($im, 0, 0, $color);

    //字体文件
    $font_file = BASE_ROOT_PATH.'/cmobile'.'/wryh.TTF';
    //设定字体的颜色
    $font_color_2 = ImageColorAllocate ($im, 0, 0, 0);
    $font_color_3 = ImageColorAllocate ($im, 255, 255, 255);
    $font_color_4 = ImageColorAllocate ($im, 170, 170, 170);

    //商品图片
    list($g_w,$g_h) = getimagesize($gData['pic']);
    $goodImg = $this->createImageFromFile($gData['pic']);
    imagecopyresampled($im, $goodImg, 0, 0, 0, 0, 750, 700, $g_w, $g_h);


    //商品描述
    if(mb_strlen($gData['title']) > 13){
        $theTitle = mb_substr($gData['title'], 0,13,'utf-8').'...';
    }
    //商品名称居中处理
    $font_size = 30;
    $fontbox = imagettfbbox($font_size, 0, $font_file, $theTitle);
    $width = imagesx($im);
    imagettftext($im,$font_size,0, ceil(($width-$fontbox[2])/2),758, $font_color_2 ,$font_file, $theTitle);
    //价格居中处理
    $font_size = 30;
    $fontbox = imagettfbbox($font_size, 0, $font_file, $gData['price']);
    $width = imagesx($im);

    //圆角处理
    $box = $fontbox[2]+60;
    $x1 = ceil(($width-$box)/2);
    $x2 = $box + $x1;
    imagerectangle ($im, $x1 , 800 , $x2, 860 , $font_color_3);
    imagefilledrectangle ($im, $x1+1 , 801 , $x2+1 , 861, $font_color_3);
    //矩形上面加圆角
    $this->imagebackgroundmycard($im, $x1 , 800 ,$box, 80, 40);
    imagettftext($im, $font_size,0, ceil(($width-$fontbox[2])/2),857, $font_color_3 ,$font_file, $gData['price']);

    //二维码
    list($code_w,$code_h) = getimagesize($codeName);
    $codeImg = $this->createImageFromFile($codeName);
    imagecopyresampled($im, $codeImg, 284, 930, 0, 0, 181, 181, $code_w, $code_h);
    //底部说明文字
    $font_size = 18;
    $fontbox = imagettfbbox($font_size, 0, $font_file, $gData['detail']);
    $width = imagesx($im);
    imagettftext($im,$font_size,0, ceil(($width-$fontbox[2])/2),1200, $font_color_4 ,$font_file, $gData['detail']);
    //输出图片
    if($fileName){
        $res = imagepng ($im,$fileName);
    }else{
        Header("Content-Types: image/png");
        $res = imagepng ($im);
    }

        //释放空间
        imagedestroy($im);
        imagedestroy($goodImg);
        imagedestroy($codeImg);
        if($res){
            return $fileName;
        }else{
            return false;
        }
}

/**
 * 从图片文件创建Image资源
 * @param $file 图片文件，支持url
 * @return bool|resource    成功返回图片image资源，失败返回false
 */
    public function createImageFromFile($file){
    if(preg_match('/http(s)?:\/\//',$file)){
        $fileSuffix = $this->getNetworkImgType($file);
    }else{
        $fileSuffix = pathinfo($file, PATHINFO_EXTENSION);
    }

    if(!$fileSuffix) return false;

    switch ($fileSuffix){
        case 'jpeg':
            $theImage = @imagecreatefromjpeg($file);
            break;
        case 'jpg':
            $theImage = @imagecreatefromjpeg($file);
            break;
        case 'png':
            $theImage = @imagecreatefrompng($file);
            break;
        case 'gif':
            $theImage = @imagecreatefromgif($file);
            break;
        default:
            $theImage = @imagecreatefromstring(file_get_contents($file));
            break;
    }

    return $theImage;
}

/**
 * 获取网络图片类型
 * @param $url  网络图片url,支持不带后缀名url
 * @return bool
 */
    public function getNetworkImgType($url){
    $ch = curl_init(); //初始化curl
    curl_setopt($ch, CURLOPT_URL, $url); //设置需要获取的URL
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);//设置超时
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //支持https
    curl_exec($ch);//执行curl会话
    $http_code = curl_getinfo($ch);//获取curl连接资源句柄信息
    curl_close($ch);//关闭资源连接

    if ($http_code['http_code'] == 200) {
        $theImgType = explode('/',$http_code['content_type']);

        if($theImgType[0] == 'image'){
            return $theImgType[1];
        }else{
            return false;
        }
    }else{
        return false;
    }
}
/**
 * 画一个带圆角的背景图
 * @param $im  底图
 * @param $dst_x 画出的图的（0，0）位于底图的x轴位置
 * @param $dst_y 画出的图的（0，0）位于底图的y轴位置
 * @param $image_w 画的图的宽
 * @param $image_h 画的图的高
 * @param $radius 圆角的值
 */
    public function imagebackgroundmycard($im, $dst_x, $dst_y, $image_w, $image_h, $radius)
{
    $resource = imagecreatetruecolor($image_w, $image_h);
    $bgcolor = imagecolorallocate($resource, 165, 129, 100);//该图的背景色

    imagefill($resource, 0, 0, $bgcolor);
    $lt_corner = $this->get_lt_rounder_corner($radius, 255, 255, 255);//圆角的背景色

    // lt(左上角)
    imagecopymerge($resource, $lt_corner, 0, 0, 0, 0, $radius, $radius, 100);
    // lb(左下角)
    $lb_corner = imagerotate($lt_corner, 90, 0);
    imagecopymerge($resource, $lb_corner, 0, $image_h - $radius, 0, 0, $radius, $radius, 100);
    // rb(右上角)
    $rb_corner = imagerotate($lt_corner, 180, 0);
    imagecopymerge($resource, $rb_corner, $image_w - $radius, $image_h - $radius, 0, 0, $radius, $radius, 100);
    // rt(右下角)
    $rt_corner = imagerotate($lt_corner, 270, 0);
    imagecopymerge($resource, $rt_corner, $image_w - $radius, 0, 0, 0, $radius, $radius, 100);

    imagecopy($im, $resource, $dst_x, $dst_y, 0, 0, $image_w, $image_h);
}

/** 画圆角
 * @param $radius 圆角位置
 * @param $color_r 色值0-255
 * @param $color_g 色值0-255
 * @param $color_b 色值0-255
 * @return resource 返回圆角
 */
    public function get_lt_rounder_corner($radius, $color_r, $color_g, $color_b)
{
    // 创建一个正方形的图像
    $img = imagecreatetruecolor($radius, $radius);
    // 图像的背景
    $bgcolor = imagecolorallocate($img, $color_r, $color_g, $color_b);
    $fgcolor = imagecolorallocate($img, 0, 0, 0);
    imagefill($img, 0, 0, $bgcolor);
    // $radius,$radius：以图像的右下角开始画弧
    // $radius*2, $radius*2：已宽度、高度画弧
    // 180, 270：指定了角度的起始和结束点
    // fgcolor：指定颜色
    imagefilledarc($img, $radius, $radius, $radius * 2, $radius * 2, 180, 270, $fgcolor, IMG_ARC_PIE);
    // 将弧角图片的颜色设置为透明
    imagecolortransparent($img, $fgcolor);
    return $img;
}


}
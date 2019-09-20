<?php


namespace app\admin\model;
use Firebase\JWT\JWT;

use think\Exception;
use think\Model;

class Token extends  Model
{
    private $key = 'pasawuruowohaihuijiandaoniwogairuhezhuheni';
    /**
     * 签发token
     * @param $user_id 用户ID
     * @param int $exp_minute token过期时间分钟
     * @return string
     */
    public function signToken($user_id, $exp_minute = 1)
    {
        $token = [
            "iss" => "http://www.horizou.cn",//request()->domain(), //签发者
            "aud" => request()->ip(), //面向的用户
            "iat" => time(), //签发时间
            "nbf" => time() + 3, //在什么时候jwt开始生效
            "exp" => time() +  60 * $exp_minute, //token 过期时间
            'user_id' => $user_id,
        ];
        $jwt = JWt::encode($token, $this->key);
        return $jwt;
    }
    //验证token
    public function checkToken($token)
    {
        $token=str_replace("Bearer ","",$token);
        try {
            $decoded = JWT::decode($token, $this->key, array('HS256'));
            return $this->object2array($decoded);
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            return false;
        }
    }
    //获取用户id
    public function getUserId(){
        $info = $this->checkToken();
        return $info->user_id;
    }
    function object2array($object) {
        if (is_object($object)) {
            foreach ($object as $key => $value) {
                $array[$key] = $value;
            }
        }
        else {
            $array = $object;
        }
        return $array;
    }
}
<?php
namespace app\v1\model;

use think\Model;
use think\db;
class Stats extends Model
{
    public function __construct(){
        parent::__construct('stats_visitor');
    }


    /**
     * 插入商品曝光数据
     *
     * @param int/array $gids 商品id
     * @param string $type 插入类型  views,favorite,cart其中之一
     */

    public function put_goods_stats($end,$gids,$type,$ukey,$num=1,$memberId) {

        //获取用户id


        $date = TIMESTAMP;

        if(!is_array($num) && $num!=1){
            $num = array(0=>$num);
        }

        if(!is_array($gids)){
            $gids = explode(',',$gids);
        }


        foreach ($gids as $k=>$v){
            $no = $num[$k]?$num[$k]:1;
            if($v>0) {
                $insert_array[] = array('stat_end'=>$end,'uid' => $memberId, 'gid' => $v, 'arrive' => $date, $type => $no,'ukey'=>$ukey);
            }
        }
        $re = DB::name('stats_goods');
        $re->insertAll($insert_array);
    }

    public function getField($str = ''){
        $arr = explode(',',$str);
        foreach ($arr as $k=>$v){
            switch ($v){
                case 'pv' : $arr[$k] = 'count(sv.id) as pv' ; break ; //访问量
                case 'uv' : $arr[$k] = 'count(distinct sv.ukey) as uv' ; break ; //访客
                case 'pu' : $arr[$k] = 'FORMAT(count(sv.id)/count(distinct sv.ukey),2) as pu' ; break ; //人均浏览量
                case 'new_uv' : $arr[$k] = 'count(distinct CASE WHEN sv.is_new = 1 THEN sv.ukey END) as new_uv' ; break ; //新访客数
                case 'old_uv' : $arr[$k] = '( count(distinct sv.ukey) - count(distinct CASE WHEN sv.is_new = 0 THEN sv.ukey END)) as old_uv' ; break ; //新访客数
                case 'stay' : $arr[$k] = 'FORMAT(avg(sv.stay/1000),2) as stay' ; break ; //页面停留时长 秒
                case 'leave_rate' : $arr[$k] = 'FORMAT( sum( CASE WHEN sv.stay < 30000 THEN 1 ELSE 0 END ) / count( sv.id ) * 100, 2 ) AS leave_rate' ; break ; //跳出率 %
                case 'goods_leave_rate' : $arr[$k] = 'FORMAT( sum( CASE WHEN sv.stay < 30000 and sv.gid >0 THEN 1 ELSE 0 END ) / count( CASE WHEN sv.gid >0 THEN 1 ELSE 0 END ) * 100, 2 ) AS goods_leave_rate' ; break ; //跳出率 %
                case 'goods_pv' : $arr[$k] = 'sum( CASE WHEN sv.gid >0 THEN 1 ELSE 0 END )  AS goods_pv' ; break ; //商品访问量
                case 'goods_u' : $arr[$k] = 'count(distinct CASE WHEN sv.gid >0 THEN sv.ukey END )  AS goods_u' ; break ; //商品访客量
                case 'goods_uv' : $arr[$k] = 'count(distinct sv.gid )  AS goods_uv' ; break ; //被访问商品数量
                case 'buyer' : $arr[$k] = 'count(distinct CASE WHEN o.payment_time <> 0 THEN o.buyer_id END) as buyer' ; break ; //付款人数
                case 'buy_order_c' : $arr[$k] = 'sum(CASE WHEN o.payment_time <> 0 THEN 1 ELSE 0 END) as buy_order_c' ; break ; //付款单数
                case 'buy_order_goods_c' : $arr[$k] = 'sum(og.goods_num) as buy_order_goods_c' ; break ; //付款商品件数
                case 'buy_money' : $arr[$k] = 'sum(CASE WHEN o.payment_time <> 0 THEN o.goods_amount ELSE 0 END) as buy_money' ; break ; //付款金额
                case 'order_people' : $arr[$k] = 'count(distinct o.buyer_id) as order_people' ; break ; //下单人数
                case 'order_c' : $arr[$k] = 'sum(o.order_id) as order_c' ; break ; //下单单数
                case 'order_money' : $arr[$k] = 'sum(o.goods_amount) as order_money' ; break ; //下单金额
                case 'dan' : $arr[$k] = 'FORMAT( sum(CASE WHEN o.payment_time <> 0 THEN o.goods_amount ELSE 0 END)/count(distinct CASE WHEN o.payment_time <> 0 THEN o.buyer_id END) ,2) as dan' ; break ; //客单价
                case 'buy_rate' : $arr[$k] = 'FORMAT(count(distinct CASE WHEN o.payment_time <> 0 THEN o.buyer_id END  ) / count(distinct sv.ukey )*100,2) AS buy_rate' ; break ; //付款顾客/访客 比率
                case 'order_rate' : $arr[$k] = 'FORMAT(count(distinct o.buyer_id) / count(distinct sv.ukey )*100,2) AS order_rate' ; break ; //下单顾客/访客 比率
                case 'pay_rate' : $arr[$k] = 'FORMAT(count(distinct CASE WHEN o.payment_time <> 0 THEN o.buyer_id END  ) / FORMAT(count(distinct o.buyer_id)*100,2) AS pay_rate' ; break ; //付款顾客/下单顾客 比率
                case 'H' : $arr[$k] = "FROM_UNIXTIME( arrive, '%Y-%m-%d %H' )AS H" ; break ; //时间戳 按小时
                case 'D' : $arr[$k] = "FROM_UNIXTIME( arrive, '%Y-%m-%d' )AS H" ; break ; //时间戳 按天
            }
        }
        return join(',',$arr);
    }

    public function getFilter($get){
        $data['todaystr'] = date('Y-m-d',TIMESTAMP);
        //获取筛选条件
        $data['sdate'] = $get['sdate']?strtotime($get['sdate']):strtotime($data['todaystr']);
        $data['edate'] = $get['edate']?strtotime($get['edate'])+86399:$data['sdate']+86399;
        //时间条件
        $data['where_today']['arrive'] = array('between',array($data['sdate'],$data['edate']));
        $data['where_yesterday']['arrive'] =array('between',array($data['sdate']-($data['edate']-$data['sdate'])-1,$data['edate']-($data['edate']-$data['sdate'])-1));
        //访问端口

        if((isset($get['end']) || isset($get['stat_end']) ) && ($get['end']!=''  || $get['stat_end']!='')) {
            $end = $get['end'] || $get['stat_end'];
            $data['end'] = intval($end);
        }else{
            $data['end'] = -1;
        }
        if($data['end']>-1){
            $data['where_today']['stat_end'] = $data['where_yesterday']['stat_end'] = $data['end'];
        }
        return $data;
    }

    //涨幅
    public function getRate($today,$yesterday){

        if(empty($today) &&empty($yesterday)){
            return array();
        }

        foreach ($today as $k=>$v){
            if($yesterday[$k] == 0){
                $data[$k.'_rate'] = floatval(0)*100;
            }else{
                $data[$k.'_rate'] = floatval(round(($v - $yesterday[$k])/$yesterday[$k],2))*100;
            }
        }
        return $data;
    }

    //占比
    public function getPercent($mobile,$all){
        foreach ($mobile as $k=>$v){
            $data[$k.'_percent'] = floatval(round(($v / $all[$k])*100,2));
        }
        return $data;
    }
}
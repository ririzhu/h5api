<?php
namespace app\v1\controller;
use think\Queue;
use think\queue\Job;
class Jobs extends  Base{
    public function index(){
//生成任务
        $data=input();
        Queue::push('app\home\controller\Jobs@UserVIP', $data, $queue = null);
        //三个参数依次为：需要执行的方法，传输的数据，任务名默认为default
    }
}
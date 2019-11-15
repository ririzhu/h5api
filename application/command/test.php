<?php


namespace app\command;
use app\v1\controller\SwooleServe;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\swoole\Server;


class test extends Command
{
    protected function configure()
    {
        $this->setName('test')
            ->setDescription('test tp5 cli mode');
    }

    protected function execute(Input $input, Output $output)
    {
        $swoole = new SwooleServe();
        print_r(1111111);
        $output->writeln("hello test!");
    }
}
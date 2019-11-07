<?php
namespace app\v1\operate;
use think\console\Command;
use think\console\Input;
use think\console\Output;
class Swooles extends Command
{
    protected function configure()
    {
        $this->setName('test')->setDescription('Here is the remark ');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("TestCommand:");
    }
}
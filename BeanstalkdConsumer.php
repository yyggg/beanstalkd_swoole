<?php
// +----------------------------------------------------------------------
// | [ 队列消费 ]
// | 命令：php think beanstalkdConsumer start|restart|stop
// +----------------------------------------------------------------------
// | Author: 杨雁
// +----------------------------------------------------------------------
// | Date: 2020/10/26 10:06
// +----------------------------------------------------------------------

namespace app\crontab\command;

use app\crontab\service\AsyncTaskService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use Pheanstalk\Pheanstalk;
use think\Exception;
use think\facade\Env;
use think\facade\Log;

class BeanstalkdConsumer extends Command
{
    private $beanstalksServer = null;
    private $taskService = null; // 多进程处理任务服务
    private $workerNum = 5; // 开多少个进程处理任务
    private $pool = null; // 当前进程池
    private $saveWorkerPidPath = ''; // 保存当前进程ID路径

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->beanstalksServer = Pheanstalk::create('127.0.0.1', 11300, 30); // 连接队列服务器
        $this->taskService = new AsyncTaskService();
        $this->pool = new \Swoole\Process\Pool($this->workerNum);
        $this->saveWorkerPidPath = Env::get('root_path') . 'runtime/log/consumerMasterWorkerPid.log';
    }

    /**
     * 配置控制台
     */
    protected function configure()
    {
        $this->setName('beanstalkdConsumer')
            ->addArgument('cmd', Argument::OPTIONAL, '请输入命令 restart 或 stop 或 start')
            ->setDescription('消费队列');
    }

    /**
     * 接收控制台参数并执行
     *
     * @param Input $input
     * @param Output $output
     * @return int|void|null
     */
    protected function execute(Input $input, Output $output)
    {
        $command = $input->getArgument('cmd');
        switch ($command) {
            case 'stop':
                if (!file_exists($this->saveWorkerPidPath)) {
                    echo "停止失败，保存进程文件不存在，请手动停止\n";
                }
                $pid = file_get_contents($this->saveWorkerPidPath);
                if (!$pid) {
                    echo "停止失败，进程ID不存在，请手动停止\n";
                }
                $exist = \Swoole\Process::kill($pid, 0);

                if ($exist) {
                    $res = \Swoole\Process::kill($pid, SIGTERM);
                    if ($res) {
                        echo "停止成功！\n";
                    }else{
                        echo "停止失败，可能进程正在处理任务中，稍候再试~\n";
                    }
                }else {
                    echo "停止成功！\n";
                }
                break;
            case 'restart':
                if (!file_exists($this->saveWorkerPidPath)) {
                    echo "重启失败，保存进程文件不存在，请手动停止\n";
                }
                $pid = file_get_contents($this->saveWorkerPidPath);
                if (!$pid) {
                    echo "重启失败，进程ID不存在，请手动停止\n";
                }

                $exist = \Swoole\Process::kill($pid, 0);
                if ($exist) {
                    \Swoole\Process::kill($pid, SIGTERM);
                }

                $this->consumer();
                break;
            default:
                $this->consumer();
        }
        return;
    }

    /**
     * 消费任务
     */
    protected function consumer()
    {
        $this->pool->on('WorkerStart', function ($pool, $workerId) {
            $this->beanstalksServer->watchOnly('default');
            // 保存进程ID 供关闭进程使用
            file_put_contents($this->saveWorkerPidPath, $pool->master_pid);

            while (true) {
                try {
                    $job = $this->beanstalksServer->reserveWithTimeout(60);
                    if ($job !== null) {
                        // 投递任务
                        // echo '开始投递任务ID：' . $job->getId();
                        $data = unserialize($job->getData());
                        $callback = $data['callback'];
                        // 设置回调，任务分发，任务调度
                        $this->taskService->$callback($data, $job, $this->beanstalksServer);
                    }
                } catch (Exception $e) {
                    Log::record('BeanstalkdConsumer 运行出错：' . $e->getMessage(), 'beanstalks'); // 记录错误日志
                    die($e->getMessage());
                }
                //sleep(1);
            }
        });

        $this->pool->on('WorkerStop', function ($pool, $workerId) {
            echo "Worker#{$workerId} 已经停止运行\n";
        });

        $this->pool->start();
    }
}

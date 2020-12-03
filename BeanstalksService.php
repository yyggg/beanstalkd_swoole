<?php
// +----------------------------------------------------------------------
// | [ 队列服务生产者 ]
// +----------------------------------------------------------------------
// | Author: 杨雁
// +----------------------------------------------------------------------
// | Date: 2020/11/20 10:33
// +----------------------------------------------------------------------

namespace app\api\service;

use app\crontab\service\AsyncTaskService;
use Pheanstalk\Pheanstalk;
use think\facade\Env;

class BeanstalksService
{
    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = Pheanstalk::create(
                Env::get('beanstalks.host'),
                Env::get('beanstalks.port'),
                Env::get('beanstalks.timeout')
            );
        }
        return self::$instance;
    }

    /**
     * 添加任务
     *
     * @param array $data 数据
     * @param string $tube 管道名称
     * @param int $priority 权重，越小越优先执行
     * @param int $delay // 延迟多少秒执行
     * @param int $ttr // 最大任务运行时间，超过设定值异常
     * @return \Pheanstalk\Job
     */
    public static function producer($data, $tube = 'default', $priority = 1024, $delay = 0, $ttr = 3600)
    {
        // 数据必须包含要执行的任务名称
        if (!isset($data['callback'])) {
            return false;
        }
        // 任务名称是否存在
        if (!method_exists(AsyncTaskService::class, $data['callback'])) {
            return false;
        }
        // 添加消息
        return self::getInstance()
            ->useTube($tube)
            ->put(serialize($data), $priority, $delay, $ttr);
    }
}

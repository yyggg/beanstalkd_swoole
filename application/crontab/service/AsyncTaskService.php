<?php
// +----------------------------------------------------------------------
// | [ 生产消息队列时数据必需包含要执行的函数并对应上 格式为['callback' => 要执行的函数名, 参数, 参数...]]
// | 改动文件后需要重启服务: php think beanstalkdConsumer restart
// +----------------------------------------------------------------------
// | Author: 杨雁
// +----------------------------------------------------------------------
// | Date: 2020/12/1 19:35
// +----------------------------------------------------------------------

namespace app\crontab\service;


use think\Exception;
use think\facade\Log;

class AsyncTaskService
{
    /**
     * 粟子
     * @param array $data
     * @param object $job
     * @param object $beanstalksServer
     */
    public function demo($data, $job, $beanstalksServer = null)
    {
        try {
            $res = true;
            if ($res) {
                Log::record('任务名：demo 成功，任888务ID：' . $job->getId(), 'beanstalks', $data);
                $beanstalksServer->delete($job); // 删除任务
            }
        } catch (Exception $e) {
            Log::record('任务：demo 失败，任务ID：' . $job->getId() . ' 错误：'. $e->getMessage(), 'beanstalks');
            $beanstalksServer->release($job); // 回滚任务
        }
    }

    public function demo1($data, $job, $beanstalksServer = null)
    {
        try {
            $res = true;
            if ($res) {
                Log::record('执行任务：demo1 成功，123任务ID：' . $job->getId(), 'beanstalks', $data);
                $beanstalksServer->delete($job); // 删除任务
            }
        } catch (Exception $e) {
            Log::record('执行任务：demo1 失败，任务ID：' . $job->getId() . ' 错误信息：'. $e->getMessage(), 'beanstalks');
            $beanstalksServer->release($job); // 回滚任务
        }
    }
}

# beanstalkd_swoole
单台服务器，beanstalkd + swoole多进程消费

百度谷歌了好久 全都没有提到如何异步处理任务，处理完之后如何删除这条任务 的相关粟子（想做个伸手党^_^
无奈自己太笨，php菜，linux也菜，基础差好多想不明白为什么。

看过kafka，本人比较笨，觉得上手难了点，笨重了点。所以选择 beanstalkd。

需求：
	项目太多的耗时任务，想要及时返回影响给用户使用端，耗时任务放后台异步慢慢执行。

问题：

	1. 太多的各种各样的耗时任务要处理，如果区分（不同的主题消息）？

	2. 项目中上百个耗时任务，全放在同一个topic 里，那是不是要开多个进程监听去消费？好像有点不实际，

	3. 只有一台服务器，没什么集群之类的
	
	4. 消费时阻塞的，一条一条消费，一台服务的情况下如何消费得更加有效率？第一个耗时任务要执行10分钟，第二个只能等待（10分钟后执行）
	
	5. 使用 beanstalkd 时发现 不同进程之间删除不了任务（可能姿势不对），如：使用swoole的异步任务，独立开来跑两个php 一个投递任务用，
	   一个是异步处理任务用（异步这边连接上 beanstalkd，根据投递进来的job删除不了
	
	6. swoole 常驻内存跑的，本来想搭个能实时改动文件 AsyncTaskService.php立马生效的，发现不可行，每次改动只能重启了，但是重启又遇到之前的进程如何杀掉，杀掉如何不影响正在处理的任务？
	
	
最终：
	使用Swoole\Process\Pool 进程池，workerStart 时分发任务到  AsyncTaskService.php 处理，把beanstalkd对象，job带过来直接调用删除发现可行。

	

为了方面大佬们查看代码，直接拷贝了一份放在外面， application 里的都是tp5对应的目录结构放的文件。

外面的 AsyncTaskService.php 是处理任务的函数。

BeanstalkdConsumer.php 是客户消费任务端。

BeanstalksService.php 是任务生产端装简单封装。

command.php 是控制台命令配置。

Yy.php 是测试生产消息的控制器。

环境情况：
php7.3 swoole4.5 nginx1.9

beanstalkd 队列服务自行安装 相关文档这里有一篇：https://www.kancloud.cn/vson/php-message-queue/891904

php 的扩展 Beanstalkd 自行安装，并启动 sudo beanstalkd -l 127.0.0.1 -p 11300 -b /var/log/beanstalkd/binlog &

现在整个逻辑总感觉哪里不对，很多地方没考虑到。希望有大佬指点一下，哪里写得不好，有没有更好的方案。

<?php

return [

   /*
    * Server host
    *
    * The ip address of the server.
    */
    'host' => '0.0.0.0',

    /*
     * Server port
     *
     * The port of the server.
     */
    'port' => '8099',

    /*
     * Server configurations
     *
     * @see https://www.swoole.co.uk/docs/modules/swoole-server/configuration
     */
    'options' => [
        'pid_file' => base_path("storage/logs/rpc.pid"),
        'worker_num' => 2,   //worker进程数,生产环境下可以根据需求设置
        'reactor_num' => 4,   //通过此参数来调节主进程内事件处理线程的数量，以充分利用多核。默认会启用CPU核数相同的数量。一般设置为CPU核数的1-4倍
        'daemonize' => 0,
        'backlog' => 1000,  //Listen队列长度，
        'task_worker_num' => 2,     //设置此参数后，服务器会开启异步task功能。此时可以使用task方法投递异步任务。
        'max_request' => 1000000,
        'dispatch_mode' => 2,  //数据包分发策略  默认为2 固定模式
        'open_eof_check' => true, //打开EOF检测
//        'open_eof_split'=>true,
        'package_eof' => "\r\n", //设置EOF
        'package_max_length' => 10485760,   //所能接收的包最大长度 根据实际情况自行配置
        'task_max_request' => 100000,  //最大task进程请求数
        'heartbeat_idle_time' => 120,  //表示连接最大允许空闲的时间
        'heartbeat_check_interval' => 60,  //轮询检测时间
        'log_file' => storage_path("logs/rpc.log")
    ],

    /*
     * 服务热启动监控目录
     *
     * 可根据源码自定义增加功能,如监听多目录、过滤不监听目录、指定监听后缀文件。如:只监听.php后缀的文件
     */
    'inotify' => [
        'directories' => base_path('app/Http/Controllers'),
    ],

];
# Laravel-Swoole-Wheel

一个基于[Swoole](http://www.swoole.com/)的高性能HTTP || TCP || WebSocket Server，帮助你大幅度地提高网站的并发能力。

## 安装

1、在安装Package之前，请确认自己的环境是否满足条件：

| Laravel | Lumen | Swoole  | PHP     |
|:-------:|:-----:|:-------:|:-------:|
| >=5.2    | >=5.2  | >=1.9.5 | >=7.0 |

2、请根据需求，确认以下PHP拓展是否已安装：

| 拓展名 | 必选 | 说明 |
|:-----:|:---:|:---:|
| swoole | true | 该package基于swoole开发，所以此拓展必须安装 |

> 注意：PHP拓展可以选择编译安装，或者使用`pecl`命令快速安装，例如`pecl install swoole`。PHP拓展安装完成后需要在`php.ini`中添加配置。

3、然后使用composer安装package：

```
$ composer require xiongchao/laravel-swoole-wheel
```

## 样例使用

**1、添加配置**

在`config/`文件夹下新建一配置文件,可直接将案例中的拷贝过去加以修改

```php
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
```

**2、 新增服务**

将`Libs`目录拷贝至`app`目录下

**2、 新增命令**

* 将`Console/Commands/`目录下文件拷贝至`app/Console/Commands/`目录下
* 在`app/Console/Kernel.php` 文件中注册Command

**3、启动服务**

可以使用`php artisan rpc:server`来管理服务，[这里](#commands)可以获取更多关于该命令的说明。

在这里，我们只需要简单执行`php artisan rpc:server`即可快速启动服务。

## Commands

该package为开发者提供了便捷的Artisan命令来管理服务：`php artisan swoole:server`。该命令接收一个`action`参数：

| Action | 说明 |
|:------:|:---:|
| start | 启动服务，该值为默认值，可缺省 |
| stop | 停止服务 |
| reload | 重载服务。此命令可以帮你平滑地重启服务器 |
| restart | 重启服务 |

> 注意：Swoole Server只能在cli模式下运行。

## Tables

由于进程间的内存是相互隔离的，我们可以借助Swoole Table实现进程间的共享数据。


更多关于Swoole Table的操作方法，可以查看[官方文档](https://wiki.swoole.com/wiki/page/p-table.html)。

> 注意：Swoole Table必须在Swoole Server启动之前创建好，所以请不要在应用程序中创建Swoole Table。

## Task

在Swoole中，Task是异步非阻塞的。如果开发者遇到一些耗时的工作，我们可以创建一个Task，将其投递到task worker进程中进行异步处理。

新建的Task必须实现`XiongChao\Swoole\Contracts\TaskContract`合约：

```php
<?php

use XiongChao\Swoole\Contracts\TaskContract;
use Illuminate\Support\Facades\Mail;

class SendMailTask implements TaskContract
{
    /**
     * @var array $mail
     */
    protected $mail;

    /**
     * Mail task
     * 
     * @var array $mail
     * @return void
     */
    public function __construct(array $mail)
    {
        $this->mail = $mail;
    }

    /**
     * Task handler.
     *
     * @param \Swoole\Server $server
     * @param int $taskId
     * @param int $srcWorkerId
     * @return void
     */
    public function handle($server, $taskId, $srcWorkerId)
    {
        Mail::to($this->mail['to'])->send($this->mail['view'], $this->mail['data']);
    }
}

```

投递任务：

```php
<?php

$task = new SendMailTask([
    'to' => 'bob@mail.com',
    'view' => 'mail',
    'data' => [],
]);
// 可将swoole_server 注册成laravel管理服务
$server->task($task);

```

> 注意：启用Task进程，必须将配置项`swoole.options.task_worker_num`配置为大于0的数值。

## Nginx

由于Swoole对HTTP协议的支持并不完整，建议仅作为应用服务器，开发者需要使用Nginx做反向代理。

```nginx
server {
    listen 80;
    server_name your.domain;
    root /path/to/laravel/public;
    index index.php;

    location = /index.php {
        # Ensure that there is no such file named "not_exists" in your "public" directory.
        try_files /not_exists @swoole;
    }

    location / {
        try_files $uri $uri/ @swoole;
    }

    location @swoole {
        set $suffix "";
        
        if ($uri = /index.php) {
            set $suffix "/";
        }

        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        proxy_pass http://127.0.0.1:1215$suffix;
    }
}
```

> 注意：请将swoole-server的IP（默认是127.0.0.1）添加到`App\Http\Middleware\TrustProxies`中间件，这样`Request::ip()`和`Request::url()`才能获取到正确的值。

## 编程须知

- 这些函数不应该出现在程序中（Artisan Command除外）：`sleep()`、`exit()`、`die()`。
- 谨慎使用单例。

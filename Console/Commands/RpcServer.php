<?php

namespace App\Console\Commands;

use App\Libs\Swoole\RpcServer;
use XiongChao\Swoole\Reload\Reload;
use Illuminate\Console\Command;
use Swoole\Process;

class HawkeyeRpcServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rpc:server {action=start : start|stop|restart|reload}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'swoole rpc server';
    /**
     *
     * The pid.
     *
     * @var int
     */
    protected $pid;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->detectSwoole();
        $action = $this->getAction();
        $this->$action();
    }

    /**
     * Get command action.
     *
     * @return string
     */
    protected function getAction()
    {
        $action = $this->argument('action');
        if (!in_array($action, ['start', 'stop', 'restart', 'reload', 'watch'])) {
            $this->error("Invalid argument '$action'. ");
            exit(1);
        }
        return $action;
    }

    /**
     * Start server.
     *
     * @return void
     */
    protected function start()
    {
        if ($this->isRunning($this->getPid())) {
            $this->error('Failed! The rpc process is already running.');
            exit(1);
        }
        $this->info('Starting server...');
        $this->info('> (Run this command to ensure the rpc process is ' .
            'running: ps -ef|grep "rpc")');
        $server = new RpcServer(
            $this->laravel['config']['rpc.host'],
            $this->laravel['config']['rpc.port'],
            $this->laravel['config']['rpc.options']
        );
        // add warm start
        $process = new Process(function (Process $process) {
            $reload = new Reload($this->laravel['config']['rpc.inotify.directories']);
            $reload->setInterval(5);
            $reload->setPidPath($this->getPidPath());
            $reload->run();
        }, false, 0);
        $server->addProcess($process);

        $server->start();
    }

    /**
     * Stop server.
     *
     * @return void
     */
    protected function stop()
    {
        $pid = $this->getPid();
        if (!$this->isRunning($pid)) {
            $this->error("Failed! There is no server process running.");
            exit(1);
        }
        $this->info('Stopping server...');
        $isRunning = $this->killProcess($pid, SIGTERM, 15);
        if ($isRunning) {
            $this->error('Unable to stop the server process.');
            exit(1);
        }
        $this->removePidFile();
        $this->info('> success');
    }

    /**
     * Restart server.
     *
     * @return void
     */
    protected function restart()
    {
        $pid = $this->getPid();
        if ($this->isRunning($pid)) {
            $this->stop();
        }
        $this->start();
    }

    /**
     * Reload server.
     *
     * @return void
     */
    protected function reload()
    {
        $pid = $this->getPid();
        if (!$this->isRunning($pid)) {
            $this->error("Failed! There is no server process running.");
            exit(1);
        }
        $this->info('Reloading server...');
        $isRunning = $this->killProcess($pid, SIGUSR1);
        if (!$isRunning) {
            $this->error('> failure');
            exit(1);
        }
        $this->info('> success');
    }

    protected function watch()
    {
        // TODO watch server
    }

    /**
     * If swoole process is running.
     *
     * @param int $pid
     * @return bool
     */
    protected function isRunning($pid)
    {
        if (!$pid) {
            return false;
        }
        Process::kill($pid, 0);
        return !swoole_errno();
    }

    /**
     * Kill process.
     *
     * @param int $pid
     * @param int $sig
     * @param int $wait
     * @return bool
     */
    protected function killProcess($pid, $sig, $wait = 0)
    {
        Process::kill($pid, $sig);
        if ($wait) {
            $start = time();
            do {
                if (!$this->isRunning($pid)) {
                    break;
                }
                usleep(100000);
            } while (time() < $start + $wait);
        }
        return $this->isRunning($pid);
    }

    /**
     * Get pid.
     *
     * @return int|null
     */
    protected function getPid()
    {
        if ($this->pid) {
            return $this->pid;
        }
        $pid = null;
        $path = $this->getPidPath();
        if (file_exists($path)) {
            $pid = (int)file_get_contents($path);
            if (!$pid) {
                $this->removePidFile();
            } else {
                $this->pid = $pid;
            }
        }
        return $this->pid;
    }

    /**
     * Get Pid file path.
     *
     * @return string
     */
    protected function getPidPath()
    {
        return $this->laravel['config']['rpc.options.pid_file'];
    }

    /**
     * Remove Pid file.
     *
     * @return void
     */
    protected function removePidFile()
    {
        if (file_exists($this->getPidPath())) {
            unlink($this->getPidPath());
        }
    }

    /**
     * Detect if ext-swoole exists.
     *
     * @return void
     */
    protected function detectSwoole()
    {
        if (!extension_loaded('swoole')) {
            $this->error('The ext-swoole is required! (pecl install swoole)');
            exit(1);
        }
    }
}

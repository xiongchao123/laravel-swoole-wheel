<?php

namespace App\Libs\Swoole;

use XiongChao\Swoole\Server;

class RpcServer extends Server
{
    /**
     * Server events.
     *
     * @var array
     */
    protected $events = ['connect', 'receive', 'finish', 'close'];

    /**
     * Define swoole server class.
     *
     * @return string
     */
    public function swooleServer()
    {
        return \Swoole\Server::class;
    }

    /**
     * The listener of "workerStart" event.
     *
     * @param \Swoole\Server $server
     * @param int $workerId
     * @return void
     */
    public function onWorkerStart($server, $workerId)
    {
        parent::onWorkerStart($server, $workerId);
        $this->clearCache();
    }

    /**
     * @param $server
     * @param int $fd
     * @param int $reactorId
     */
    public function onConnect($server, int $fd, int $reactorId)
    {
        //echo "connect:" . $fd . PHP_EOL;
    }

    /**
     * @param \Swoole\Server $server
     * @param int $fd
     * @param int $reactor_id
     * @param string $data
     */
    public function onReceive($server, int $fd, int $reactor_id, string $data)
    {
        // heart beat
        if (strlen($data) === 6) {
            $pack = pack("C", 1) . pack("C", 4) . pack("V", 6);
            $this->server->send($fd, $pack);
        }else{
            $this->server->task(RpcTask::make($fd, substr($data, 6)));
        }
    }

    /**
     * @param \Swoole\Server $server
     * @param int $task_id
     * @param string $data
     */
    public function onFinish($server, int $task_id, string $data)
    {
        // TODO ...
    }

    /**
     * @param Server $server
     * @param int $fd
     * @param int $reactorId
     */
    public function onClose($server, int $fd, int $reactorId)
    {
        // TODO
    }

    /**
     * Get server name
     *
     * @return string
     */
    protected function getServerName()
    {
        return 'rpc-hawkeye';
    }
}
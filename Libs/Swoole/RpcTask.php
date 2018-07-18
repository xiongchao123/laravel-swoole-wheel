<?php

namespace App\Libs\Swoole;

use XiongChao\Swoole\Contracts\TaskContract;
use Illuminate\Http\Request;

class RpcTask implements TaskContract
{

    /**
     * Task data
     * @var mixed
     */
    protected $data;

    /**
     * connection fd
     * @var integer
     */
    protected $fd;

    protected $baseUri;

    /**
     * Make a new task.
     *
     * @param integer $fd
     * @param mixed $data
     * @return static
     */
    public static function make($fd, $data = null)
    {
        return new static($fd, $data);
    }

    public function __construct($fd, $data = null)
    {
        $this->fd = $fd;
        $this->data = $data;
        $this->baseUri = env("APP_URL","http://localhost");
    }

    public function handle($server, $taskId, $srcWorkerId)
    {
        $this->data = rtrim($this->data);
        $data = json_decode($this->data, true);
        if (!is_array($data) || !isset($data["path"]) || !isset($data["method"])) {
            $server->send($this->fd, $this->pack("invalid request"));
            return;
        }
        $method = strtoupper($data["method"]);
        // check request type
        if (!in_array($method, ["GET", "POST", "PUT", "PATCH", "DELETE"])) {
            $server->send($this->fd, $this->pack("invalid request method"));
            return;
        }
        $parameters = array();
        if (isset($data["parameters"]) && is_array($data["parameters"])) {
            $parameters = $data["parameters"];
        }
        $request = Request::create($this->baseUri . $data["path"], $method, $parameters);
        $response = app()->handle($request);
        $server->send($this->fd, $this->pack($response->getContent()));
        return;
    }

    private function pack($content)
    {
        // TODO ...
        return $content."\r\n";
    }
}
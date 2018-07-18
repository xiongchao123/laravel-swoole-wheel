<?php
/**
 * Created by PhpStorm.
 * User: xiongchao
 * Date: 2018/7/18
 * Time: 14:49
 */

namespace XiongChao\Swoole\Reload;

class Reload
{
    /**
     * 监听文件变化的路径
     *
     * @var string
     */
    private $watchDir;
    /**
     * the lasted md5 of dir
     *
     * @var string
     */
    private $md5File = '';
    /**
     * the interval of scan
     *
     * @var int
     */
    private $interval = 3;

    /**
     * @var string
     */
    private $pidPath;

    /**
     * @param string $pidPath
     */
    public function setPidPath(string $pidPath): void
    {
        $this->pidPath = $pidPath;
    }

    /**
     * @param int $interval
     */
    public function setInterval(int $interval): void
    {
        $this->interval = $interval;
    }

    /**
     * Reload constructor.
     * @param $dir
     */
    public function __construct($dir)
    {
        $this->watchDir = $dir;
        $this->md5File = FileHelper::md5File($this->watchDir);
    }

    /**
     * 启动监听
     */
    public function run()
    {
        while (true) {
            sleep($this->interval);
            $md5File = FileHelper::md5File($this->watchDir);
            if (strcmp($this->md5File, $md5File) !== 0) {
                echo "Start reloading...\n";
                if ($pid = $this->getPid()) {
                    posix_kill($pid, SIGUSR1);
                }
                echo "Reloaded\n";
            }
            $this->md5File = $md5File;
        }
    }

    /**
     * Get pid.
     *
     * @return int|null
     */
    protected function getPid()
    {
        if (file_exists($this->pidPath)) {
            $pid = (int)file_get_contents($this->pidPath);
            if ($pid) {
                return $pid;
            }
        }
        return null;
    }

}
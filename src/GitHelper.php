<?php

namespace tourhunter\devUpdater;

use Yii;
use yii\base\BaseObject;
use yii\helpers\FileHelper;

/**
 *
 * @property string $head
 * @property array $lastCommitTime
 * @property null|string $errors
 */
class GitHelper extends BaseObject
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var null|string
     */
    protected $_head = null;

    /**
     * @var null|int
     */
    protected $_lastCommitTime = null;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->path = FileHelper::normalizePath(Yii::getAlias('@app/.git')) . DIRECTORY_SEPARATOR;
    }

    /**
     * @return bool|string
     */
    public function getErrors()
    {
        $error = false;
        if (!file_exists($this->path)) {
            $error = 'Git main folder not found.';
        } elseif (!file_exists($this->path . 'HEAD')) {
            $error = "HEAD file not found.";
        }

        return $error;
    }

    /**
     * @return mixed|null|string
     */
    public function getHead()
    {
        if (is_null($this->_head)) {
            $filename = $this->path . 'HEAD';
            $this->_head = str_replace('ref: ', '', file_get_contents($filename));
        }

        return $this->_head;
    }

    /**
     * @return bool|int|null
     */
    public function getLastCommitTime()
    {
        if (is_null($this->_lastCommitTime)) {
            $time = 0;
            $logHeadFilename = $this->path . 'logs/HEAD';
            if (file_exists($logHeadFilename) && $fp = @fopen($logHeadFilename, 'r')) {
                $pos = -2;
                $line = '';
                $c = '';
                $size = filesize($logHeadFilename) * -1;
                do {
                    $line = $c . $line;
                    fseek($fp, $pos--, SEEK_END);
                    $c = fgetc($fp);
                } while ($c != "\n" && $c != "\r" && $pos > $size);
                fclose($fp);

                $parts = explode("\t", $line);
                $parts = explode(" ", $parts[0]);
                $time = (int)$parts[count($parts) - 2];
            } else {
                $time = filemtime($this->path . 'HEAD');
            }
            $this->_lastCommitTime = $time;
        }

        return $this->_lastCommitTime;
    }
}
<?php
namespace Sogedial\SiteBundle\Service;

abstract class AbstractLogger
{
    protected $logger;

    public function setLogger($value)
    {
        $this->logger = $value;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    protected function warningLog($msg)
    {
        if (null !== $this->logger) {
            $this->getLogger()->warn($this->getMsg($msg));
        }
        return $this;
    }

    protected function errorLog($msg)
    {
        if (null !== $this->logger) {
            $this->getLogger()->err($this->getMsg($msg));
        }
        return $this;
    }

    protected function log($msg)
    {
        if (null !== $this->logger) {
            $this->getLogger()->notice($this->getMsg($msg));
        }
        return $this;
    }

    protected function getMsg($msg)
    {
        $className = get_class($this);
        return '[SOGEDIAL][' . $className . '] '. $msg;
    }

}

<?php

namespace Droid\Plugin\Ssh;

class DroidPlugin
{
    public function __construct($droid)
    {
        $this->droid = $droid;
    }

    public function getCommands()
    {
        $commands = [];
        $commands[] = new \Droid\Plugin\Ssh\Command\SshExecCommand();
        $commands[] = new \Droid\Plugin\Ssh\Command\ScpCommand();
        return $commands;
    }
}

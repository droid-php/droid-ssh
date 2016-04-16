<?php

namespace Droid\Plugin\Ssh\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;
use phpseclib\Net\SSH2;

abstract class BaseSshCommand extends Command
{
    public function baseConfigure()
    {
        $this
            ->addArgument(
                'hostname',
                InputArgument::REQUIRED,
                'Hostname'
            )
            ->addOption(
                'port',
                'p',
                InputOption::VALUE_REQUIRED,
                'Port number'
            )
            ->addOption(
                'username',
                'u',
                InputOption::VALUE_REQUIRED,
                'Login as username'
            )
            ->addOption(
                'password',
                null,
                InputOption::VALUE_REQUIRED,
                'Use password based authentication'
            )
            ->addOption(
                'timeout',
                't',
                InputOption::VALUE_REQUIRED,
                'Timeout in seconds, defaults to 1'
            )
            ->addOption(
                'agent',
                'a',
                InputOption::VALUE_NONE,
                'Enable ssh-agent authentication'
            )
            ->addOption(
                'keyfile',
                'k',
                InputOption::VALUE_REQUIRED,
                'Use ssh-key filename'
            )
            ->addOption(
                'passphrase',
                null,
                InputOption::VALUE_REQUIRED,
                'Use passphrase for keyfile'
            )
        ;
    }
    
    protected function getSshConnection(InputInterface $input, OutputInterface $output)
    {
        $hostname = $input->getArgument('hostname');
        $command = $input->getArgument('cmd');

        $username = 'root';
        if ($input->getOption('username')) {
            $username = $input->getOption('username');
        }
        $port = $input->getOption('port');
        if (!$port) {
            $port = 22;
        }

        $output->writeLn(" - Connecting: <info>$username@$hostname:$port</info>");
        
        $ssh = new \phpseclib\Net\SSH2($hostname);

        $res = null;
        
        if ($input->getOption('keyfile')) {
            // Load a private key
            $keyName = $input->getOption('keyfile');
            $key = new \phpseclib\Crypt\RSA();
            $passphrase = $input->getOption('passphrase');
            if ($passphrase) {
                $key->setPassword($passphrase);
            }

            if (!$key->loadKey(file_get_contents($keyName))) {
                throw new RuntimeException("Loading key failed: " . $keyName);
            }
            $res = $ssh->login($username, $key);
        }
        
        if ($input->getOption('agent')) {
            $agent = new \phpseclib\System\SSH\Agent();
            $res = $ssh->login($username, $agent);
        }
        
        if ($input->getOption('password')) {
            $res = $ssh->login($username, $input->getOption('password'));
        }
        
        if (!$res) {
            throw new RuntimeException("Login failed: " . $hostname . ' as ' . $username);
        }
        $ssh->enableQuietMode();
        
        $timeout = $input->getOption('timeout');
        if (!$timeout) {
            $timeout = 1;
        }
        
        $ssh->setTimeout($timeout);

        return $ssh;
    }
}

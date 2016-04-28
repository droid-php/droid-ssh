<?php

namespace Droid\Plugin\Ssh\Command;

use SSHClient\ClientBuilder\ClientBuilder;
use SSHClient\ClientConfiguration\ClientConfiguration;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class SshExecCommand extends BaseSshCommand
{
    public function configure()
    {
        $this
            ->setName('ssh:exec')
            ->setDescription('Execute a command over ssh')
            ->baseConfigure()
            ->addArgument(
                'hostname',
                InputArgument::REQUIRED,
                'Host name, alias or IP address'
            )
            ->addArgument(
                'cmd',
                InputArgument::REQUIRED,
                'Command to execute on the remote host'
            )
            ->addArgument(
                'args',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'Command arguments'
            )
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $formatter = $this->getHelper('formatter');
        $outputter = function($type, $buf) use ($formatter, $input, $output) {
            $style = 'info';
            $section = '<comment>' . $input->getArgument('hostname') . '</comment>:';
            if (Process::ERR === $type) {
                $style = 'error';
                $section .= 'ERR';
            } else {
                $section .= 'Out';
            }
            $this->writeOutput($output, $formatter, $buf, $section, $style);
        };
        $outputter->bindTo($this);

        $builder = new ClientBuilder($this->getClientConfig($input, $output));
        $execArgs = array_merge(
            array($input->getArgument('cmd')),
            $input->getArgument('args')
        );

        $client = $builder->buildClient();
        $client->exec($execArgs, $outputter);
    }

    protected function getClientConfig(InputInterface $input, OutputInterface $output)
    {
        $config = new ClientConfiguration(
            $input->getArgument('hostname'),
            $input->getOption('username')
        );

        $config
            ->setOptions($this->getOptions($input))
            ->setSSHOptions($this->getProgramOptions($input, $output))
        ;

        return $config;
    }
}

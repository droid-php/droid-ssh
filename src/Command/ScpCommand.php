<?php

namespace Droid\Plugin\Ssh\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use SSHClient\ClientBuilder\ClientBuilder;
use SSHClient\ClientConfiguration\ClientConfiguration;

class ScpCommand extends BaseSshCommand
{
    /**
     * Maps option names to those understood by scp.
     *
     * @var array
     */
    protected $programOptions = array(
        'recursion' => 'r',
    );

    public function configure()
    {
        $this
            ->setName('ssh:copy')
            ->setAliases(array('scp'))
            ->setDescription('Copy files to or from a remote host')
            ->addArgument(
                'from',
                InputArgument::REQUIRED,
                'Path to file(s) at the source'
            )
            ->addArgument(
                'to',
                InputArgument::REQUIRED,
                'Path at the destination'
            )
            ->addOption(
                'recursion',
                'r',
                InputOption::VALUE_NONE,
                'Recursively copy entire directories.'
            )
            ->baseConfigure()
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $formatter = $this->getHelper('formatter');
        $outputter = function($type, $buf) use ($formatter, $input, $output) {
            $style = 'info';
            $section = '<comment>Secure Copy</comment>:';
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
        $client = $builder->buildSecureCopyClient();

        $client->copy(
            $input->getArgument('from'),
            $input->getArgument('to'),
            $outputter
        );
    }

    protected function getClientConfig(InputInterface $input, OutputInterface $output)
    {
        $config = new ClientConfiguration(
            '',
            $input->getOption('username')
        );

        return $config
            ->setOptions($this->getOptions($input))
            ->setSCPOptions($this->getProgramOptions($input, $output))
        ;
    }
}

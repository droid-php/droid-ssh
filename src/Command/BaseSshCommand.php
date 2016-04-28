<?php

namespace Droid\Plugin\Ssh\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseSshCommand extends Command
{
    /**
     * Maps option names to those understood by the underlying program.
     *
     * @var array
     */
    protected $programOptions = array();

    /**
     * Maps option names to the ssh_option names understood by ssh programs.
     *
     * @var array
     */
    protected $sshOptionOptions = array(
        'port' => 'Port',
        'timeout' => 'ConnectTimeout',
    );

    public function baseConfigure()
    {
        return $this
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
                'keyfile',
                'k',
                InputOption::VALUE_REQUIRED,
                'Path to private key file'
            )
            ->addOption(
                'password',
                null,
                InputOption::VALUE_NONE,
                'Force password based authentication'
            )
            ->addOption(
                'timeout',
                't',
                InputOption::VALUE_REQUIRED,
                'Connect timeout in seconds',
                1
            )
        ;
    }

    public function writeOutput(
        OutputInterface $output,
        FormatterHelper $formatter,
        $buffer,
        $section,
        $style = null
    ) {
        foreach (explode("\n", $buffer) as $line) {
            if ($line === '') {
                return;
            }
            $output->writeln($formatter->formatSection($section, $line, $style));
        }
    }

    /**
     * Get the configuration for an SSH Client.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return \SSHClient\ClientConfiguration\ClientConfigurationInterface
     */
    abstract protected function getClientConfig(
        InputInterface $input, OutputInterface $output
    );

    /**
     * Convert console command options to ssh_options.
     *
     * @param InputInterface $input
     *
     * @return array
     */
    protected function getOptions(InputInterface $input)
    {
        $opts = array();

        foreach ($this->sshOptionOptions as $key => $option) {
            if ($input->getOption($key)) {
                $opts[$option] = $input->getOption($key);
            }
        }

        if ($input->getOption('keyfile')) {
            $opts['PasswordAuthentication'] = 'no';
            $opts['PubkeyAuthentication'] = 'yes';
            $opts['IdentitiesOnly'] = 'yes';
            $opts['IdentityFile'] = $input->getOption('keyfile');
        } else if ($input->getOption('password')) {
            $opts['PasswordAuthentication'] = 'yes';
            $opts['PubkeyAuthentication'] = 'no';
        }

        return $opts;
    }

    /**
     * Convert console command options to ssh program options.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return array
     */
    protected function getProgramOptions(
        InputInterface $input,
        OutputInterface $output
    ) {
        $opts = array();

        foreach ($this->getDefinition()->getOptions() as $o) {
            $name = $o->getName();
            if (! array_key_exists($name, $this->programOptions )) {
                continue;
            }
            $value = $input->getOption($name);
            if ($o->acceptValue() && $value !== null) {
                $opts[$this->programOptions[$name]] = $value;
            } else if (! $o->acceptValue() && $value === true) {
                $opts[] = $this->programOptions[$name];
            }
        }

        return array_merge(
            $opts,
            $this->getVerbosityOptions($output->getVerbosity())
        );
    }

    private function getVerbosityOptions($level)
    {
        switch($level) {
            case OutputInterface::VERBOSITY_VERBOSE:
                return array('v');
            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                return array('v', 'v');
            case OutputInterface::VERBOSITY_DEBUG:
                return array('v', 'v', 'v');
        }
        return array();
    }
}

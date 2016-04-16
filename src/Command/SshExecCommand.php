<?php

namespace Droid\Plugin\Ssh\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;
use phpseclib\Net\SSH2;
use Symfony\Component\Process\ProcessBuilder;

class SshExecCommand extends BaseSshCommand
{
    public function configure()
    {
        $this->setName('ssh:exec')
            ->setDescription('Execute a command over ssh')
        ;
        $this->baseConfigure();
        
        $this
            ->addArgument(
                'cmd',
                InputArgument::REQUIRED,
                'Command to execute on the remote host'
            )
        ;
    }
    
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $command = $input->getArgument('cmd');

        $output->writeLn("Ssh exec: <comment>$command</comment> ");

        $ssh = $this->getSshConnection($input, $output);
        $out = $ssh->exec($command);
        
        $formatter = $this->getHelper('formatter');
        
        foreach (explode("\n", $out) as $line) {
            if ($line!='') {
                $formattedLine = $formatter->formatSection(
                    '<comment>' . $input->getArgument('hostname') . '</comment>:Out',
                    $line,
                    'info'
                );
                $output->writeln($formattedLine);
            }
        }
        //echo $out;
        
        $err = $ssh->getStdError();
        if ($err) {
            foreach (explode("\n", $err) as $line) {
                if ($line!='') {
                    $formattedLine = $formatter->formatSection(
                        '<comment>' . $input->getArgument('hostname') . '</comment>:Err',
                        $line,
                        'error'
                    );
                    $output->writeln($formattedLine);
                }
            }
        }

        if ($ssh->isTimeout()) {
            $output->writeLn("Connection time-out");
        }

        //print_r($ssh);
    }
}

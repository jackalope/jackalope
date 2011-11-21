<?php

namespace Jackalope\Tools\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Jackalope\Tools\Console\Helper\JackrabbitHelper;

/**
 * @author Daniel Barsotti <daniel.barsotti@liip.ch>
 */
class JackrabbitCommand extends Command
{
    /**
     * Path to Jackrabbit jar file
     * @var string
     */
    protected $jackrabbit_jar;

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('jackalope:run:jackrabbit')
            ->addArgument('cmd', InputArgument::REQUIRED, 'Command to execute (start | stop | status)')
            ->addOption('jackrabbit_jar', null, InputOption::VALUE_OPTIONAL, 'Path to the Jackrabbit jar file')
            ->setDescription('Start and stop the Jackrabbit server')
            ->setHelp(<<<EOF
The <info>jackalope:run:jackrabbit</info> command allows to have a minimal
control on the Jackrabbit server from within a command.

If the <info>jackrabbit_jar</info> option is set, it will be used as the
Jackrabbit server jar file.
EOF
);
    }

    protected function setJackrabbitPath($jackrabbit_jar)
    {
        $this->jackrabbit_jar = $jackrabbit_jar;
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return integer 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cmd = $input->getArgument('cmd');

        if (! in_array(strtolower($cmd), array('start', 'stop', 'status'))) {
            $output->writeln($this->asText());
            return 1;
        }

        $jar = $input->getOption('jackrabbit_jar')?: $this->jackrabbit_jar;

        if (! $jar) {
            throw new \InvalidArgumentException('Either specify the path to the jackrabbit jar file or configure the command accordingly');
        }

        if (!file_exists($jar)) {
            throw new \Exception("Could not find the specified Jackrabbit .jar file '$jar'");
        }

        $helper = new JackrabbitHelper($jar);

        switch(strtolower($cmd)) {
            case 'start':
                $helper->startServer();
                break;
            case 'stop':
                $helper->stopServer();
                break;
            case 'status':
                $output->writeln("Jackrabbit server " . ($helper->isServerRunning() ? 'is running' : 'is not running'));
                break;
        }

        return 0;
    }
}

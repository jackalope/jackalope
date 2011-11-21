<?php

namespace Jackalope\Tools\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Init doctrine dbal
 *
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 */
class InitDoctrineDbalCommand extends Command
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('jackalope:init:dbal')
            ->setDefinition(array(
                new InputOption(
                    'dump-sql', null, InputOption::VALUE_NONE,
                    'Instead of try to apply generated SQLs to the database, output them.'
                )
            ))
            ->setHelp(<<<EOT
Processes the schema and either create it directly in the database or generate the SQL output.
EOT
    );
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $session = $this->getHelper('phpcr')->getSession();
        if (!$session instanceof \Jackalope\Session) {
            $output->write(PHP_EOL.'<error>The session option did not point to an instance of Jackalope.</error>'.PHP_EOL);
            throw new \InvalidArgumentException('The session option did not point to an instance of Jackalope.');
        }

        $transport = $session->getTransport();
        if (!$transport instanceof \Jackalope\Transport\DoctrineDBAL\Client) {
            $output->write(PHP_EOL.'<error>The session option did not point to an instance of Jackalope Doctrine DBAL Transport.</error>'.PHP_EOL);
            throw new \InvalidArgumentException('The session option did not point to an instance of Jackalope Doctrine DBAL Transport.');
        }

        if (true !== $input->getOption('dump-sql')) {
            $output->write('ATTENTION: This operation should not be executed in a production environment.' . PHP_EOL . PHP_EOL);
        }

        $connection = $transport->getConnection();
        $schema = \Jackalope\Transport\DoctrineDBAL\RepositorySchema::create();
        foreach ($schema->toSql($connection->getDatabasePlatform()) as $sql) {
            if (true === $input->getOption('dump-sql')) {
                $output->writeln($sql);
            } else {
                $connection->exec($sql);
            }
        }

        if (true !== $input->getOption('dump-sql')) {
            $session->getWorkspace()->createWorkspace('default');
            $output->writeln("Jackalope Doctrine DBAL tables have been initialized successfully and 'default' workspace created.");
        }
    }
}

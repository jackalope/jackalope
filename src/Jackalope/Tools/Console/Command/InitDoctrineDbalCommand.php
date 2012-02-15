<?php

namespace Jackalope\Tools\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;

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
            ->setDescription('Prepare the database for Jackalope Doctrine-Dbal.')
            ->setDefinition(array(
                new InputOption(
                    'dump-sql', null, InputOption::VALUE_NONE,
                    'Instead of try to apply generated SQLs to the database, output them.'
                )
            ))
            ->setHelp(<<<EOT
Prepare the database for Jackalope Doctrine-DBAL transport.
Processes the schema and either creates it directly in the database or generate the SQL output.
EOT
    );
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = $this->getHelper('jackalope-doctrine-dbal')->getConnection();
        if (!$connection instanceof Connection) {

            $output->write(PHP_EOL.'<error>The provided connection is not an instance of the Doctrine DBAL connection.</error>'.PHP_EOL);
            throw new \InvalidArgumentException('The provided connection is not an instance of the Doctrine DBAL connection.');
        }

        if (true !== $input->getOption('dump-sql')) {
            $output->write('ATTENTION: This operation should not be executed in a production environment.' . PHP_EOL . PHP_EOL);
        }

        $schema = \Jackalope\Transport\DoctrineDBAL\RepositorySchema::create();
        try {
            foreach ($schema->toSql($connection->getDatabasePlatform()) as $sql) {
                if (true === $input->getOption('dump-sql')) {
                    $output->writeln($sql);
                } else {
                    $connection->exec($sql);
                }
            }
        } catch (\PDOException $e) {
            if ("42S01" == $e->getCode()) {
                $output->write(PHP_EOL.'<error>The tables already exist. Nothing was changed.</error>'.PHP_EOL.PHP_EOL); // TODO: add a parameter to drop old scheme first
                return;
            }
            throw $e;
        }

        if (true !== $input->getOption('dump-sql')) {
            $output->writeln("Jackalope Doctrine DBAL tables have been initialized successfully and 'default' workspace created.");
        }
    }
}

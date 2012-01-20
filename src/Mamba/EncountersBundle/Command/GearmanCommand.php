<?php
// src/Acme/DemoBundle/Command/GreetCommand.php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GearmanCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('demo:greet')
            ->setDescription('Greet someone')
            ->addArgument('name', InputArgument::OPTIONAL, 'Who do you want to greet?')
            ->addOption('yell', null, InputOption::VALUE_NONE, 'If set, the task will yell in uppercase letters')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
//        $name = $input->getArgument('name');
//        if ($name) {
//            $text = 'Hello '.$name;
//        } else {
//            $text = 'Hello';
//        }
//
//        if ($input->getOption('yell')) {
//            $text = strtoupper($text);
//        }
//
//        $output->writeln("<info>$text</info>");
//        $output->writeln('<comment>foo</comment>');
//        // black text on a cyan background
//        $output->writeln('<question>foo</question>');
//
//        // white text on a red background
//        $output->writeln('<error>foo</error>');

        $worker= new \GearmanWorker();

        # Add default server (localhost).
        $worker->addServer();

        # Register function "reverse" with the server.
        $class = $this;
        $worker->addFunction("reverse", function($job) use($class) {return $class->reverse_fn($job);});

        while (1)
        {
          print "Waiting for job...\n";

          $ret= $worker->work();
          if ($worker->returnCode() != GEARMAN_SUCCESS)
            break;
        }

        # A much simple reverse function

    }

    function reverse_fn($job)
            {
              $workload= $job->workload();
              echo "Received job: " . $job->handle() . "\n";
              echo "Workload: $workload\n";
              $result= strrev($workload);
              echo "Result: $result\n";
              return $result;
            }
}
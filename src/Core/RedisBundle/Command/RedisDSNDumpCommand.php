<?php
namespace Core\RedisBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Core\ScriptBundle\Script;

/**
 * RedisDSNDumpCommand
 *
 * @package RedisBundle
 */
class RedisDSNDumpCommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Redis DSN dumper",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "redis:dsn:dump"
    ;

    /**
     * Конфигурирование крон-скрипта
     *
     *
     */
    protected function configure() {
        parent::configure();

        $this
            ->addOption('mode', 'm', InputOption::VALUE_OPTIONAL, 'Output mode: l(ist), r(aw)', 'r')
        ;
    }

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $mode = $this->input->getOption('mode');
        if (in_array($mode, array('list', 'raw', 'l', 'r'))) {
            $nodes = array();
            foreach ($this->getRedis()->getNodes() as $node) {
                $nodes[] = (string) $node;
            }

            if ($mode == 'list' || $mode == 'l') {
                echo implode(PHP_EOL, $nodes) . PHP_EOL;
            } else {
                echo implode(";", $nodes) . PHP_EOL;
            }
        } else {
            throw new \Core\ScriptBundle\ScriptException("Invalid mode value: '" . $mode . "'");
        }
    }
}
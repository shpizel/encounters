<?php
namespace Core\RedisBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Core\ScriptBundle\Script;

/**
 * RedisMigrationCleanupCommand
 *
 * @package RedisBundle
 */
class RedisMigrationCleanupCommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Redis migration cleanup tool",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "redis:migration:cleanup",

        /**
         * Размер fork
         *
         * @var int
         */
        CHUNK_SIZE = 32
    ;

    /**
     * Конфигурирование крон-скрипта
     *
     *
     */
    protected function configure() {
        parent::configure();

        $this
            ->addOption('dir', null, InputOption::VALUE_REQUIRED, "Migration data path", null)
            ->addOption('forks', null, InputOption::VALUE_REQUIRED, "Forks block size", self::CHUNK_SIZE)
        ;
    }

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $this->prepare();

        while (true) {
            $this->files = glob($this->dir . DIRECTORY_SEPARATOR . RedisMigrationPrepareCommand::KEYS_DIR . DIRECTORY_SEPARATOR . RedisMigrationPrepareCommand::KEYS_CHUNKS_DIR . DIRECTORY_SEPARATOR . "*.keys");
            $this->files = array_filter($this->files, function($filename) {
                $filename = basename($filename, ".keys");
                $parts = explode("-", $filename);
                return
                    count($parts) == 4 &&
                    file_exists($this->dir . DIRECTORY_SEPARATOR . RedisMigrationPrepareCommand::COMPLETED_DIR . DIRECTORY_SEPARATOR . $filename . ".completed") &&
                    !file_exists($this->dir . DIRECTORY_SEPARATOR . RedisMigrationPrepareCommand::CLEANUP_DIR . DIRECTORY_SEPARATOR . $filename . ".cleanup")
                ;
            });

            if (count($this->files) > 0) {
                try {
                    $this->processFiles();
                } catch (\Exception $e) {
                    $this->log($e->getMessage(), 16);
                }
            } else {
                $this->log("Completed", 64);
                break;
            }
        }
    }

    /**
     * processFiles
     *
     * @return null
     */
    public function processFiles() {
        $chunks = array_chunk($this->files, $this->forks);

        $counter = 0;
        foreach ($chunks as $chunk) {
            $pids = array();

            foreach ($chunk as $filename) {
                $pid = pcntl_fork();
                if ($pid == -1) {
                    throw new \Core\ScriptBundle\ScriptException("Could not fork process");
                } else if ($pid) {
                    $pids[] = $pid;
                } else {
                    exit($this->processFile($this->currentFilename = $filename));
                }
            }

            while(count($pids) > 0){
                $finishedPid = pcntl_waitpid(-1, $status, WNOHANG);
                if ($status !== 0) {
                    throw new \Core\ScriptBundle\ScriptException("Process {$finishedPid} returns " . pcntl_wexitstatus($status) . " code");
                }

                foreach($pids as $key => $pid) {
                    if($finishedPid == $pid) {
                        unset($pids[$key]);
                        $counter++;

                        $this->log("{$counter}/" . count($this->files), -1);
                    }
                }

                usleep(250);
            }
        }
    }

    /**
     * FileProcessor
     *
     * @param $filename
     * @return int
     */
    private function processFile($filename) {
        $keys = array_map(function($key) {
            return trim($key);
        }, file($filename));
        $filename = basename($filename, ".keys");

        list($sourceDSNNumber, $destinationDSNNumber, $resourceType) = explode("-", $filename);

        $sourceConnection = $this->getRedis()->getNodeConnection($this->src[$sourceDSNNumber]);
        if ($count = $sourceConnection->del($keys) != count($keys)) {
            //$this->log($filename . "\t" . "{$count}/" . count($keys) , 48);
        }

        file_put_contents($this->dir . DIRECTORY_SEPARATOR . RedisMigrationPrepareCommand::CLEANUP_DIR . DIRECTORY_SEPARATOR . "{$filename}.cleanup", time());
    }

    private function prepare() {
        if (!$this->dir = $this->input->getOption('dir')) {
            throw new \Core\ScriptBundle\ScriptException("Please provide output directory");
        } else {
            if (file_exists($this->dir) && is_dir($this->dir)) {
                $this->dir = rtrim($this->dir, DIRECTORY_SEPARATOR);
            } else {
                throw new \Core\ScriptBundle\ScriptException("Invalid output directory {$this->dir}");
            }
        }

        if (!file_exists($sourceDSNFilename = $this->dir . DIRECTORY_SEPARATOR . "source.dsn")) {
            throw new \Core\ScriptBundle\ScriptException("Source DSN file does not exists");
        }

        if (!$this->src = file_get_contents($sourceDSNFilename)) {
            throw new \Core\ScriptBundle\ScriptException("Please provide source DSN");
        } else {
            $this->src = explode(";", $this->src);
            foreach ($this->src as &$node) {
                $node = \Core\RedisBundle\RedisDSN::getDSNFromString($node);
            }
        }

        if (!file_exists($destinationDSNFilename = $this->dir . DIRECTORY_SEPARATOR . "destination.dsn")) {
            throw new \Core\ScriptBundle\ScriptException("Destination DSN file does not exists");
        }

        if (!$this->dst = file_get_contents($destinationDSNFilename)) {
            throw new \Core\ScriptBundle\ScriptException("Please provide destination DSN");
        } else {
            $this->dst = explode(";", $this->dst);
            foreach ($this->dst as &$node) {
                $node = \Core\RedisBundle\RedisDSN::getDSNFromString($node);
            }
        }

        if (!$this->forks = intval($this->input->getOption('forks'))) {
            throw new \Core\ScriptBundle\ScriptException("Invalid forks count");
        }
    }
}
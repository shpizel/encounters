<?php
namespace Core\RedisBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Core\ScriptBundle\Script;

/**
 * RedisMigrationPrepareCommand
 *
 * @package RedisBundle
 */
class RedisMigrationPrepareCommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Redis migration prepare",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "redis:migration:prepare",

        /**
         * Размер блока
         *
         * @var int
         */
        CHUNK_SIZE = 1024
    ;

    /**
     * Конфигурирование крон-скрипта
     *
     *
     */
    protected function configure() {
        parent::configure();

        $this
            ->addOption('src', null, InputOption::VALUE_REQUIRED, "Source DSN", null)
            ->addOption('dst', null, InputOption::VALUE_REQUIRED, "Destination DSN", null)
            ->addOption('dir', null, InputOption::VALUE_REQUIRED, "Output directory", null)
        ;
    }

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $this->prepare();

        /**
         * устанавливаем редису нужные ноды, взамен тех, которые он получил из настроек приложения
         *
         * @author shpizel
         */
        $this->getRedis()->setNodes($this->dst);

        /**
         * Составляем сценарий миграции:
         * {destination}-{source}-{datatype}.keys
         *
         * @author shpizel
         */
        $keysCounter = 0;
        foreach ($this->src as $number=>$dsn) {
            $Redis = $this->getRedis()->getNodeConnection($dsn);
            foreach ($this->getWords()/*array("a")*/ as $word) {
                if ($keys = $Redis->keys("{$word}*")) {

                    if ($prefix = $dsn->getPrefix()) {
                        foreach ($keys as $n=>$key) {
                             $keys[$n] = substr($key, strlen($prefix));
                        }
                    }

                    /** Отфильтруем ключи, которые нам не нужны */
                    $keys = array_filter($keys, function($key) use($dsn) {
                        return (string) $this->getRedis()->getDSNByKey($key) != (string) $dsn;
                    });

                    if (!$keys) {
                        continue;
                    }
                    /**
                     * Готовим структуру данных
                     * array(
                     *     '{src}-{dst}' => array(
                     *         '{datatype}' => array(key1, .., keyN)
                     *      )
                     * )
                     *
                     * @author shpizel
                     */
                    $keys = array_chunk($keys, self::CHUNK_SIZE);
                    foreach ($keys as $chunk) {
                        $_keys = array();

                        $Redis->multi();
                        foreach ($chunk as $key) {
                            $Redis->type($key);
                        }
                        $types = $Redis->exec();

                        foreach ($chunk as $index=>$key) {
                            $type = $types[$index];
                            $_key = $number . "-" . $this->getRedis()->getNodeNumberByKey($key);

                            if (!isset($_keys[$_key])) {
                                $_keys[$_key] = array();
                            }

                            if (!isset($_keys[$_key][$type])) {
                                $_keys[$_key][$type] = array();
                            }

                            $_keys[$_key][$type][] = $key;
                            $keysCounter++;

                            $this->log("<info>" . number_format($keysCounter) . "</info>", -1);
                        }

                        foreach ($_keys as $_key => $chunk) {
                            foreach ($chunk as $type => $data) {
                                file_put_contents($this->dir . DIRECTORY_SEPARATOR . "{$_key}-{$type}.keys", implode(PHP_EOL, $data) . PHP_EOL, FILE_APPEND);
                            }
                        }
                    }
                }
            }
        }

        $files = glob($this->dir . "/*.keys");
        $keysCounter = 0;
        foreach ($files as $number=>$keysFile) {
            if ($filePointer = @fopen($keysFile, 'r')) {
                $counter = 0;
                $chunk = array();
                while (($key = fgets($filePointer)) !== false) {
                    $key = trim($key);
                    $chunk[] = $key;
                    $keysCounter++;
                    $this->log("<info>" . ($number + 1) . "/" . count($files) . "</info>, {$keysCounter} keys processed", -1);

                    if (count($chunk) >= self::CHUNK_SIZE) {
                        /** Получаем имя файла */
                        $filename = basename($keysFile, ".keys") . "-{$counter}.keys";
                        file_put_contents($this->dir . DIRECTORY_SEPARATOR . $filename, implode(PHP_EOL, $chunk) . PHP_EOL);
                        $chunk = array();
                        $counter++;
                    }
                }

                if (!feof($filePointer)) {
                    throw new \Core\ScriptBundle\ScriptException("Unexpected fgets() failed");
                }

                fclose($filePointer);

                if ($chunk) {
                    /** Получаем имя файла */
                    $filename = basename($keysFile, ".keys") . "-{$counter}.keys";
                    file_put_contents($this->dir . DIRECTORY_SEPARATOR . $filename, implode(PHP_EOL, $chunk) . PHP_EOL);
                }

                /** @todo: посчитать число строк в этих файлах и в остальных (правильно ли работают чанки) */
                //@unlink($keysFile);
            } else {
                throw new \Core\ScriptBundle\ScriptException("Could not open {$keysFile}");
            }
        }
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

        foreach (glob($this->dir . "/*") as $filename) {
            @unlink($filename);
        }

        if (!$this->src = $this->input->getOption('src')) {
            throw new \Core\ScriptBundle\ScriptException("Please provide source DSN");
        } else {
            file_put_contents($this->dir . "/source.dsn", $this->src);

            $this->src = explode(";", $this->src);
            foreach ($this->src as &$node) {
                $node = \Core\RedisBundle\RedisDSN::getDSNFromString($node);
            }
        }

        if (!$this->dst = $this->input->getOption('dst')) {
            throw new \Core\ScriptBundle\ScriptException("Please provide destination DSN");
        } else {
            file_put_contents($this->dir . "/destination.dsn", $this->src);

            $this->dst = explode(";", $this->dst);
            foreach ($this->dst as &$node) {
                $node = \Core\RedisBundle\RedisDSN::getDSNFromString($node);
            }
        }
    }

    private function getWords() {
        return
            array_merge(
                range("a", "z"),
                range("A", "Z"),
                range(0, 9)
            )
        ;
    }
}
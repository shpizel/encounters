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
        CHUNK_SIZE = 1024,

        /**
         * Key dump directory
         *
         * @var str
         */
        KEY_DUMP_DIR = "keydump",

        /**
         * Keys directory
         *
         * @var str
         */
        KEYS_DIR = "keys",

        /**
         * Key dump chunks directory
         *
         * @var str
         */
        KEY_DUMP_CHUNKS_DIR = "chunks",

        /**
         * Keys chunks directory
         *
         * @var str
         */
        KEYS_CHUNKS_DIR = "chunks"
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
            foreach ($this->getWords() as $word) {

                /**
                 * Нужно получить ключи из Redis'ов, да так чтобы быстро и все
                 *
                 * @author shpizel
                 */
                $keysFilename = $this->dir . DIRECTORY_SEPARATOR . self::KEY_DUMP_DIR . DIRECTORY_SEPARATOR . "{$number}-{$word}.keydump";
                $cmd = "redis-cli -h " . $dsn->getHost() . " -p " . $dsn->getPort() . " -n " . $dsn->getDatabase() . " keys '{$word}*' > {$keysFilename}";
                $this->log("Executing <comment>{$cmd}</comment>..");
                exec($cmd, $ret, $code);
                if ($code) {
                    $this->log("FAILED", 16);
                    throw new \Core\ScriptBundle\ScriptException("Failed executing {$cmd}");
                } else {
                    $this->log("SUCCESS", 64);
                }

                $this->log("Chunking <comment>$keysFilename</comment> to <info>". self::CHUNK_SIZE. "</info>-lined chunks..");
                if ($filePointer = fopen($keysFilename, 'r')) {
                    $processedKeys = 0;
                    $keys = array();
                    $chunkNumber = 0;

                    while (($key = fgets($filePointer)) !== false) {
                        if ($key = trim($key)) {
                            $processedKeys++;

                            if ($prefix = $dsn->getPrefix()) {
                                $key = substr($key, strlen($prefix));
                            }

                            if ((string) $this->getRedis()->getDSNByKey($key) != (string) $dsn) {
                                $keys[] = $key;

                                if (count($keys) >= self::CHUNK_SIZE) {
                                    file_put_contents($this->dir . DIRECTORY_SEPARATOR . self::KEYS_DUMP_DIR . DIRECTORY_SEPARATOR . self::KEY_DUMP_CHUNKS_DIR . DIRECTORY_SEPARATOR . basename($keysFilename, ".keydump") . "-{$chunkNumber}.keydump", implode(PHP_EOL, $keys) . PHP_EOL);

                                    $chunkNumber++;
                                    $this->log("<info>" . number_format($chunkNumber + 1) . "</info> chunks generated", -1);
                                    $keys = array();
                                }
                            }
                        }
                    }

                    if ($keys) {
                        file_put_contents($this->dir . DIRECTORY_SEPARATOR . self::KEYS_DUMP_DIR . DIRECTORY_SEPARATOR . self::KEY_DUMP_CHUNKS_DIR . DIRECTORY_SEPARATOR . basename($keysFilename, ".keydump") . "-{$chunkNumber}.keydump", implode(PHP_EOL, $keys) . PHP_EOL);
                        $this->log("<info>" . number_format($chunkNumber + 1) . "</info> chunks generated", -1);
                    }
                    echo "\n";

                    if ($processedKeys) {
                        $this->log("<info>SUCCESS</info> processed <comment>" . number_format($processedKeys) . "</comment> key(s) and <comment>" . number_format($chunkNumber) . "</comment> chunks");
                    } else {
                        $this->log("No keys was found..", 16);
                    }

                } else {
                    $this->log("FAILED", 16);
                    throw new \Core\ScriptBundle\ScriptException("Failed opening {$keysFilename}");
                }

                if ($chunks = glob($this->dir . DIRECTORY_SEPARATOR . self::KEY_DUMP_DIR . DIRECTORY_SEPARATOR . self::KEY_DUMP_CHUNKS_DIR . DIRECTORY_SEPARATOR . "{$number}-{$word}-*.keydump")) {

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
                    foreach ($chunks as $chunk) {
                        $chunk = file($chunk);
                        foreach ($chunk as $ci=>$cv) {
                            if (!$chunk[$ci] = trim($cv)) {
                                unset($chunk[$ci]);
                            }
                        }

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

                            $this->log("<info>" . number_format($keysCounter) . "</info> keys processed", -1);
                        }


                        foreach ($_keys as $_key => $chunk) {
                            foreach ($chunk as $type => $data) {
                                file_put_contents($this->dir . DIRECTORY_SEPARATOR . self::KEYS_DIR . DIRECTORY_SEPARATOR . "{$_key}-{$type}.keys", implode(PHP_EOL, $data) . PHP_EOL, FILE_APPEND);
                            }
                        }
                    }

                    echo "\n";
                }
            }
        }

        /**
         * Проверить правильность чанка можно командами:
         * ls -1 | egrep "^[0-9]+-[0-9]+-[0-9]+\.keys" | xargs cat | wc -l
         * ls -1 | egrep "^[0-9]+-[0-9]+-[0-9]+-[0-9]+\.keys" | xargs cat | wc -l
         *
         * результат должен быть один
         *
         * @author shpizel
         */

        $files = glob($this->dir . DIRECTORY_SEPARATOR . self::KEYS_DIR . DIRECTORY_SEPARATOR . "*.keys");
        $keysCounter = 0;
        foreach ($files as $number=>$keysFile) {
            if ($filePointer = @fopen($keysFile, 'r')) {
                $counter = 0;
                $chunk = array();
                while (($key = fgets($filePointer)) !== false) {
                    if ($key = trim($key)) {
                        $chunk[] = $key;
                        $keysCounter++;
                        $this->log("<info>" . ($number + 1) . "/" . count($files) . "</info>, <comment>" . number_format($keysCounter) . "</comment> keys processed", -1);

                        if (count($chunk) >= self::CHUNK_SIZE) {
                            /** Получаем имя файла */
                            $filename = basename($keysFile, ".keys") . "-{$counter}.keys";
                            file_put_contents($this->dir . DIRECTORY_SEPARATOR . self::KEYS_DIR . DIRECTORY_SEPARATOR . self::KEYS_CHUNKS_DIR . DIRECTORY_SEPARATOR . $filename, implode(PHP_EOL, $chunk) . PHP_EOL);
                            $chunk = array();
                            $counter++;
                        }
                    }
                }

                if (!feof($filePointer)) {
                    throw new \Core\ScriptBundle\ScriptException("Unexpected fgets() failed");
                }

                fclose($filePointer);

                if ($chunk) {
                    /** Получаем имя файла */
                    $filename = basename($keysFile, ".keys") . "-{$counter}.keys";
                    file_put_contents($this->dir . DIRECTORY_SEPARATOR . self::KEYS_DIR . DIRECTORY_SEPARATOR . self::KEYS_CHUNKS_DIR . DIRECTORY_SEPARATOR . $filename, implode(PHP_EOL, $chunk) . PHP_EOL);
                }
            } else {
                throw new \Core\ScriptBundle\ScriptException("Could not open {$keysFile}");
            }
        }
        echo "\n";
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

        $cmd = "rm -fr {$this->dir}" . DIRECTORY_SEPARATOR . "*";
        $this->log("Executing <comment>{$cmd}</comment>..");
        exec($cmd, $ret, $code);
        if ($code) {
            $this->log("FAILED", 16);
            throw new \Core\ScriptBundle\ScriptException("Failed executing {$cmd}");
        } else {
            $this->log("SUCCESS", 64);
        }

        if
        (!
            (
                mkdir($this->dir . DIRECTORY_SEPARATOR . self::KEY_DUMP_DIR) &&
                mkdir($this->dir . DIRECTORY_SEPARATOR . self::KEY_DUMP_DIR . DIRECTORY_SEPARATOR . self::KEY_DUMP_CHUNKS_DIR) &&
                mkdir($this->dir . DIRECTORY_SEPARATOR . self::KEYS_DIR) &&
                mkdir($this->dir . DIRECTORY_SEPARATOR . self::KEYS_DIR . DIRECTORY_SEPARATOR . self::KEYS_CHUNKS_DIR)
            )
        ) {
            throw new \Core\ScriptBundle\ScriptException("Could not create directories");
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
            file_put_contents($this->dir . "/destination.dsn", $this->dst);

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
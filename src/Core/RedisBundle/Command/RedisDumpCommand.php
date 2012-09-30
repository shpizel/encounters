<?php
namespace Core\RedisBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Core\ScriptBundle\Script;

/**
 * RedisDumpCommand
 *
 * @package RedisBundle
 */
class RedisDumpCommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Redis dumper",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "redis:dump",

        DEFAULT_TIMEOUT = 2.5,
        DEFAULT_PERSISTENT = true
    ;

    /**
     * Конфигурирование крон-скрипта
     *
     *
     */
    protected function configure() {
        parent::configure();

        $this
            ->addOption('dsn', null, InputOption::VALUE_REQUIRED, "DSN", null)
            ->addOption('dir', null, InputOption::VALUE_REQUIRED, "Output directory", getcwd() . DIRECTORY_SEPARATOR . "rdb")
        ;
    }

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $this->prepare();

        foreach ($this->nodes as $nodeIndex => $node) {
            $Redis = $this->getRedis()->getNodeConnection($node);
            $words = array_merge(range('a', 'z'), range(1, 9));

            foreach ($words as $wordIndex => $word) {
                if ($keys = $Redis->keys("{$word}*")) {
                    $counter = 0;

                    $keysChunks = array_chunk($keys, 1024);
                    foreach ($keysChunks as $keysChunk) {
                        $Redis->multi();
                        foreach ($keysChunk as &$key) {
                            if ($node['options']['prefix'] && strpos($key, $node['options']['prefix']) === 0) {
                                $key = substr($key, strlen($node['options']['prefix']));
                            }

                            $Redis->type($key);
                        }
                        $types = $Redis->exec();


                        $Redis->multi();
                        foreach ($keysChunk as $index=>$key) {
                            $type = $types[$index];

                            if ($type == 1 /** string */) {
                                $Redis->get($key);
                                //file_put_contents("{$this->dir}/strings.db", (json_encode(array($key=>$src))) . PHP_EOL ,FILE_APPEND);
                            } elseif ($type == 2 /** set */) {
                                $Redis->sMembers($key);
                                //file_put_contents("{$this->dir}/sets.db", (json_encode(array($key=>$srcSet))) . PHP_EOL ,FILE_APPEND);
                            } elseif ($type == 3 /** list */) {
                                $Redis->lGet($key, 0, -1);
                                //file_put_contents("{$this->dir}/lists.db", (json_encode(array($key=>$srcList))) . PHP_EOL ,FILE_APPEND);
                            } elseif ($type == 4 /** zset */) {
                                $Redis->zRange($key, 0, -1, array('withscores'=>true));
                                //file_put_contents("{$this->dir}/zsets.db", (json_encode(array($key=>$srcZSet))) . PHP_EOL ,FILE_APPEND);
                            } elseif ($type == 5 /** hash */) {
                                $Redis->hGetAll($key);
                                //file_put_contents("{$this->dir}/hashes.db", (json_encode(array($key=>$srcHash))) . PHP_EOL ,FILE_APPEND);
                            } else {
                                throw new \Core\ScriptBundle\ScriptException("OOC");
                            }

                            $counter++;

                            //
                            $logMessage = "<info>words</info>: " . ($wordIndex+1) . "/" . count($words) . " <info>keys</info>:" . $counter  . "/" . count($keys);;
                            $logMessage = "";

                            //nodes
                            $logMessage.= "<info>nodes</info>: " . ($nodeIndex+1) . "/" . count($this->nodes) . " (" . round( ($nodeIndex+1) * 100/ count($this->nodes),2) . "%), ";

                            //words
                            $logMessage.= "<info>words</info>: " . ($wordIndex+1) . "/" . count($words) . " (" . round( ($wordIndex+1) * 100/ count($words),2) . "%), ";

                            //keys
                            $logMessage.= "<info>keys</info>: " . ($counter) . "/" . count($keys) . " (" . round( $counter * 100/ count($keys),2) . "%)";

                            $this->log($logMessage, -1);
                        }
                        $data = $Redis->exec();
                        foreach ($keysChunk as $index=>$key) {
                            $type = $types[$index];

                            if ($type == 1 /** string */) {
                                file_put_contents("{$this->dir}/strings.db", (json_encode(array($key=>$data[$index]))) . PHP_EOL ,FILE_APPEND);
                            } elseif ($type == 2 /** set */) {
                                file_put_contents("{$this->dir}/sets.db", (json_encode(array($key=>$data[$index]))) . PHP_EOL ,FILE_APPEND);
                            } elseif ($type == 3 /** list */) {
                                file_put_contents("{$this->dir}/lists.db", (json_encode(array($key=>$data[$index]))) . PHP_EOL ,FILE_APPEND);
                            } elseif ($type == 4 /** zset */) {
                                file_put_contents("{$this->dir}/zsets.db", (json_encode(array($key=>$data[$index]))) . PHP_EOL ,FILE_APPEND);
                            } elseif ($type == 5 /** hash */) {
                                file_put_contents("{$this->dir}/hashes.db", (json_encode(array($key=>$data[$index]))) . PHP_EOL ,FILE_APPEND);
                            } else {
                                throw new \Core\ScriptBundle\ScriptException("OOC");
                            }
                        }
                    }
                }
            }
        }
    }

    private function prepare() {
        if ($dsn = $this->input->getOption('dsn')) {
            $this->nodes = $this->getNodesFromDSN($dsn);
        } else {
            throw new \Core\ScriptBundle\ScriptException("Please provide Redis DSN");
        }

        if (($dir = $this->input->getOption('dir')) && is_dir($dir) && is_writable($dir)) {
            $this->dir = rtrim($dir, DIRECTORY_SEPARATOR);
        } else {
            throw new \Core\ScriptBundle\ScriptException("{$dir} does not exists or not writable");
        }
    }

    /**
     * @param $dsn
     * @return array
     */
    private function getNodesFromDSN($dsn) {
        $nodes = array();
        foreach (explode(";", $dsn) as $dsn) {
            $parts = parse_url($dsn);
            $path = explode(DIRECTORY_SEPARATOR, ltrim($parts['path'], DIRECTORY_SEPARATOR));

            $node = array(
                'host' => $parts['host'],
                'port' => $parts['port'],
                'timeout' => self::DEFAULT_TIMEOUT,
                'options' => array(
                    'persistent' => self::DEFAULT_PERSISTENT,
                    'database' => isset($path[0]) ? $path[0] : 0,
                    'prefix' => isset($path[1]) ? $path[1] : false,
                )
            );

            $nodes[] = $node;
        }
        return $nodes;
    }
}
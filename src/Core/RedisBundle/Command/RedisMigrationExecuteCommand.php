<?php
namespace Core\RedisBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Core\ScriptBundle\Script;

/**
 * RedisMigrationExecuteCommand
 *
 * @package RedisBundle
 */
class RedisMigrationExecuteCommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Redis migration executor",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "redis:migration:execute",

        /**
         * Размер fork
         *
         * @var int
         */
        CHUNK_SIZE = 32,

        /**
         * Размер multi() контейнера
         *
         * @var int
         */
        OP_CHUNK_SIZE = 1024
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
            ->addOption('mode', null, InputOption::VALUE_REQUIRED, "dump - just dump, migrate - dump and migrate", "migrate")
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
                    !file_exists(
                        ($this->mode == 'migrate')
                            ? $this->dir . DIRECTORY_SEPARATOR . RedisMigrationPrepareCommand::COMPLETED_DIR . DIRECTORY_SEPARATOR . $filename . ".completed"
                            : $this->dir . DIRECTORY_SEPARATOR . RedisMigrationPrepareCommand::DUMP_DIR . DIRECTORY_SEPARATOR . $filename . ".dump"
                    )
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
        $destinationConnection = $this->getRedis()->getNodeConnection($this->dst[$destinationDSNNumber]);

        if ($resourceType == 1 /** string */) {
            $sourceConnection->multi();
            foreach ($keys as $key) {
                $sourceConnection->get($key);
            }
            $smret = $sourceConnection->exec();

            $sourceConnection->multi();
            foreach ($keys as $key) {
                $sourceConnection->ttl($key);
            }
            $ttlret = $sourceConnection->exec();

            /**
             * Нужно сохранить данные в дамп в формате json
             *
             * @author shpizel
             */
            $json = array();
            foreach ($keys as $index=>$key) {
                $data = $smret[$index];
                $ttl  = $ttlret[$index];

                $json[] = array(
                    'key'   => $key,
                    'ttl'   => $ttl,
                    'value' => $data,
                );
            }

            file_put_contents($this->dir . DIRECTORY_SEPARATOR . RedisMigrationPrepareCommand::DUMP_DIR . DIRECTORY_SEPARATOR . $filename . ".dump", json_encode($json));
            if ($this->mode == 'dump') {
                return;
            }

            /** Удалим ключи у приемщика */
            $destinationConnection->del($keys);

            $destinationConnection->multi();
            foreach ($keys as $index=>$key) {
                $data = $smret[$index];
                $ttl  = $ttlret[$index];

                if ($ttl > 0) {
                    $destinationConnection->setex($key, $ttl, $data);
                } else {
                    $destinationConnection->set($key, $data);
                }

            }
            $ret = $destinationConnection->exec();
            $ret = array_filter($ret, function($item) {
                return $item !== true;
            });

            if (count($ret) > 0) {
                throw new \Core\ScriptBundle\ScriptException("setex error");
            }

            /** Если все ОК то сохраняем метку о том, что этот кусок успешно обработан */
            file_put_contents($this->dir . DIRECTORY_SEPARATOR . RedisMigrationPrepareCommand::COMPLETED_DIR . DIRECTORY_SEPARATOR . $filename . ".completed", time());
        } elseif ($resourceType == 2 /** set */) {
            $sourceConnection->multi();
            foreach ($keys as $key) {
                $sourceConnection->sMembers($key);
            }
            $smret = $sourceConnection->exec();

            /**
             * Нужно сохранить данные в дамп в формате json
             *
             * @author shpizel
             */
            $json = array();
            foreach ($keys as $index=>$key) {
                $data = $smret[$index];

                $json[] = array(
                    'key'   => $key,
                    'value' => $data,
                );
            }
            file_put_contents($this->dir . DIRECTORY_SEPARATOR . RedisMigrationPrepareCommand::DUMP_DIR . DIRECTORY_SEPARATOR . $filename . ".dump", json_encode($json));
            if ($this->mode == 'dump') {
                return;
            }

            /** Удалим ключи у приемщика */
            $destinationConnection->del($keys);

            $destinationConnection->multi();
            foreach ($keys as $index=>$key) {
                $data = $smret[$index];

                $opcounter = 0;
                foreach ($data as $item) {
                    $destinationConnection->sAdd($key, $item);
                    $opcounter++;

                    if ($opcounter >= self::OP_CHUNK_SIZE) {
                        $ret = $destinationConnection->exec();
                        $ret = array_filter($ret, function($item) {
                            return $item === false;
                        });

                        if (count($ret) > 0) {
                            throw new \Core\ScriptBundle\ScriptException("sAdd error");
                        }

                        $destinationConnection->multi();
                        $opcounter = 0;
                    }
                }
            }
            $ret = $destinationConnection->exec();
            $ret = array_filter($ret, function($item) {
                return $item === false;
            });

            if (count($ret) > 0) {
                throw new \Core\ScriptBundle\ScriptException("sAdd error");
            }

            /** Если все ОК то сохраняем метку о том, что этот кусок успешно обработан */
            file_put_contents($this->dir . DIRECTORY_SEPARATOR . RedisMigrationPrepareCommand::COMPLETED_DIR . DIRECTORY_SEPARATOR . $filename . ".completed", time());
        } elseif ($resourceType == 3 /** list */) {
            $sourceConnection->multi();
            foreach ($keys as $key) {
                $sourceConnection->lRange($key, 0, -1);
            }
            $smret = $sourceConnection->exec();

            /**
             * Нужно сохранить данные в дамп в формате json
             *
             * @author shpizel
             */
            $json = array();
            foreach ($keys as $index=>$key) {
                $data = $smret[$index];

                $json[] = array(
                    'key'   => $key,
                    'value' => $data,
                );
            }
            file_put_contents($this->dir . DIRECTORY_SEPARATOR . RedisMigrationPrepareCommand::DUMP_DIR . DIRECTORY_SEPARATOR . $filename . ".dump", json_encode($json));
            if ($this->mode == 'dump') {
                return;
            }

            /** Удалим ключи у приемщика */
            $destinationConnection->del($keys);

            $destinationConnection->multi();
            foreach ($keys as $index=>$key) {
                $data = $smret[$index];

                $opcounter = 0;
                foreach ($data as $item) {
                    $destinationConnection->lPush($key, $item);
                    $opcounter++;

                    if ($opcounter >= self::OP_CHUNK_SIZE) {
                        $ret = $destinationConnection->exec();
                        $ret = array_filter($ret, function($item) {
                            return $item === false;
                        });

                        if (count($ret) > 0) {
                            throw new \Core\ScriptBundle\ScriptException("lPush error");
                        }

                        $destinationConnection->multi();
                        $opcounter = 0;
                    }
                }
            }
            $ret = $destinationConnection->exec();
            $ret = array_filter($ret, function($item) {
                return $item === false;
            });

            if (count($ret) > 0) {
                throw new \Core\ScriptBundle\ScriptException("lPush error");
            }

            /** Если все ОК то сохраняем метку о том, что этот кусок успешно обработан */
            file_put_contents($this->dir . DIRECTORY_SEPARATOR . RedisMigrationPrepareCommand::COMPLETED_DIR . DIRECTORY_SEPARATOR . $filename . ".completed", time());
        } elseif ($resourceType == 4 /** zset */) {
            $sourceConnection->multi();
            foreach ($keys as $key) {
                $sourceConnection->zRange($key, 0, -1, true /** withsccores */);
            }
            $smret = $sourceConnection->exec();

            /**
             * Нужно сохранить данные в дамп в формате json
             *
             * @author shpizel
             */
            $json = array();
            foreach ($keys as $index=>$key) {
                $data = $smret[$index];

                $json[] = array(
                    'key'   => $key,
                    'value' => $data,
                );
            }
            file_put_contents($this->dir . DIRECTORY_SEPARATOR . RedisMigrationPrepareCommand::DUMP_DIR . DIRECTORY_SEPARATOR . $filename . ".dump", json_encode($json));
            if ($this->mode == 'dump') {
                return;
            }

            /** Удалим ключи у приемщика */
            $destinationConnection->del($keys);

            $destinationConnection->multi();
            foreach ($keys as $index=>$key) {
                $data = $smret[$index];

                $opcounter = 0;
                foreach ($data as $item=>$scores) {
                    $destinationConnection->zAdd($key, $scores, $item);
                    $opcounter++;

                    if ($opcounter >= self::OP_CHUNK_SIZE) {
                        $ret = $destinationConnection->exec();
                        $ret = array_filter($ret, function($item) {
                            return $item === 0;
                        });

                        if (count($ret) > 0) {
                            throw new \Core\ScriptBundle\ScriptException("zAdd error");
                        }

                        $destinationConnection->multi();
                        $opcounter = 0;
                    }
                }
            }
            $ret = $destinationConnection->exec();
            $ret = array_filter($ret, function($item) {
                return $item === 0;
            });

            if (count($ret) > 0) {
                throw new \Core\ScriptBundle\ScriptException("zAdd error");
            }

            /** Если все ОК то сохраняем метку о том, что этот кусок успешно обработан */
            file_put_contents($this->dir . DIRECTORY_SEPARATOR . RedisMigrationPrepareCommand::COMPLETED_DIR . DIRECTORY_SEPARATOR . $filename . ".completed", time());
        } elseif ($resourceType == 5 /** hash */) {
            $sourceConnection->multi();
            foreach ($keys as $key) {
                $sourceConnection->hGetAll($key);
            }
            $smret = $sourceConnection->exec();

            /**
             * Нужно сохранить данные в дамп в формате json
             *
             * @author shpizel
             */
            $json = array();
            foreach ($keys as $index=>$key) {
                $data = $smret[$index];

                $json[] = array(
                    'key'   => $key,
                    'value' => $data,
                );
            }
            file_put_contents($this->dir . DIRECTORY_SEPARATOR . RedisMigrationPrepareCommand::DUMP_DIR . DIRECTORY_SEPARATOR . $filename . ".dump", json_encode($json));
            if ($this->mode == 'dump') {
                return;
            }

            /** Удалим ключи у приемщика */
            $destinationConnection->del($keys);

            $destinationConnection->multi();
            foreach ($keys as $index=>$key) {
                $data = $smret[$index];

                $opcounter = 0;
                foreach ($data as $hashKey=>$hashVal) {
                    $destinationConnection->hSet($key, $hashKey, $hashVal);
                    $opcounter++;

                    if ($opcounter >= self::OP_CHUNK_SIZE) {
                        $ret = $destinationConnection->exec();
                        $ret = array_filter($ret, function($item) {
                            return $item === false;
                        });

                        if (count($ret) > 0) {
                            throw new \Core\ScriptBundle\ScriptException("hSet error");
                        }

                        $destinationConnection->multi();
                        $opcounter = 0;
                    }
                }
            }
            $ret = $destinationConnection->exec();
            $ret = array_filter($ret, function($item) {
                return $item === false;
            });

            if (count($ret) > 0) {
                throw new \Core\ScriptBundle\ScriptException("hSet error");
            }

            /** Если все ОК то сохраняем метку о том, что этот кусок успешно обработан */
            file_put_contents($this->dir . DIRECTORY_SEPARATOR . RedisMigrationPrepareCommand::COMPLETED_DIR . DIRECTORY_SEPARATOR . $filename . ".completed", time());
        } else {
            throw new \Core\ScriptBundle\ScriptException("Invalid resource type");
        }
    }

    private function prepare() {
        if (!$this->dir = $this->input->getOption('dir')) {
            throw new \Core\ScriptBundle\ScriptException("Please provide input directory");
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

        if (!in_array($this->mode = $this->input->getOption('mode'), array('dump', 'migrate'))) {
            throw new \Core\ScriptBundle\ScriptException("Invalid script mode");
        }
    }
}
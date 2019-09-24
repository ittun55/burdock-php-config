<?php
namespace Burdock\Config;

use Symfony\Component\Yaml\Yaml;
use Yosymfony\Toml\Toml;
use Psr\Log\LoggerInterface;

//Todo: merge multiple configurations

class Config
{
    const YAML = 'yaml';
    const JSON = 'json';
    const TOML = 'toml';

    protected $_data   = [];
    protected $_logger = null;

    public function __construct(?array $kv=null)
    {
        if (is_null($kv)) return;
        foreach ($kv as $k => $v) {
            $this->_data[$k] = $v;
        }
    }

    public static function load(string $path=null, string $type=null)
    {
        $config = new static();
        if (self::YAML === substr($path, -1 * strlen(self::YAML)) || $type == self::YAML) {
            $yaml = file_get_contents($path);
            $config->_data = Yaml::parse($yaml);
        } elseif (self::JSON === substr($path,-1 * strlen(self::JSON)) || $type == self::JSON) {
            $json = file_get_contents($path);
            $config->_data = json_decode($json, true);
        } elseif (self::TOML === substr($path,-1 * strlen(self::TOML)) || $type == self::TOML) {
            $toml = file_get_contents($path);
            $config->_data = Toml::Parse($toml);
        } else {
            throw new \InvalidArgumentException('Please specify valid config file type');
        }
        return $config;
    }

    public function getValue(string $path, string $delimiter='.')
    {
        if (is_null($path)) {
            return $this->_data;
        } else {
            $nodes = explode($delimiter, $path);
            $value = $this->_data;
            foreach ($nodes as $node) {
                if ($node === '') continue;
                if (preg_match('/(?P<prop>\w+)\[(?P<idx>\d+)\]/', $node, $matches)) {
                    $prop = $matches['prop'];
                    $idx  = (int)$matches['idx'];
                    if (!array_key_exists($prop, $value) || !array_key_exists($idx, $value[$prop])) {
                        throw new \OutOfRangeException('The config has no value for '.$path);
                    }
                    $value = $value[$prop][$idx];
                } else {
                    if (!array_key_exists($node, $value)) {
                        throw new \OutOfRangeException('The config has no value for '.$path);
                    }
                    $value = $value[$node];
                }
            }
            return $value;
        }
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->_logger = $logger;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->_logger;
    }
}
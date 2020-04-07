<?php
namespace Burdock\Config;

use Symfony\Component\Yaml\Yaml;
use Yosymfony\Toml\Toml;
use Psr\Log\LoggerInterface;

//Todo: merge multiple configurations

class Config
{
    const INI  = 'ini';
    const YML  = 'yml';
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
        if (!file_exists($path)) {
            throw new \InvalidArgumentException($path . ' not found. Please specify existing file.');
        }
        if (!$file = file_get_contents($path)) {
            throw new \InvalidArgumentException($path . ' could not read. Please confirm access rights.');
        };
        $config = new static();
        if (self::YAML === substr($path, -1 * strlen(self::YAML)) || $type == self::YAML) {
            $config->_data = Yaml::parse($file);
        } elseif (self::YML === substr($path, -1 * strlen(self::YML)) || $type == self::YML) {
            $config->_data = Yaml::parse($file);
        } elseif (self::JSON === substr($path,-1 * strlen(self::JSON)) || $type == self::JSON) {
            $config->_data = json_decode($file, true);
        } elseif (self::INI === substr($path,-1 * strlen(self::INI)) || $type == self::INI) {
            $config->_data = Toml::Parse($file);
        } elseif (self::TOML === substr($path,-1 * strlen(self::TOML)) || $type == self::TOML) {
            $config->_data = Toml::Parse($file);
        } else {
            throw new \InvalidArgumentException('Please specify valid config file type. .yaml .json .toml');
        }
        return $config;
    }

    public function setValue(string $path, $obj, string $delimiter='.')
    {
        $nodes = explode($delimiter, $path);
        $value = &$this->_data;
        for ($i=0; $i < count($nodes); $i++) {
            $node = $nodes[$i];
            if ($i == count($nodes) - 1) {
                if (preg_match('/(?P<prop>\w+)\[(?P<idx>\d*)\]/', $node, $matches)) {
                    $prop = $matches['prop'];
                    if (!array_key_exists($prop, $value))
                        $value[$prop] = [];
                    if ('' === $matches['idx']) {
                        $value[$prop][] = $obj;
                    } else {
                        $idx = (int)$matches['idx'];
                        $value[$prop][$idx] = $obj;
                    }
                } else {
                    $value[$node] = $obj;
                }
            } else {
                if (preg_match('/(?P<prop>\w+)\[(?P<idx>\d+)\]/', $node, $matches)) {
                    $prop = $matches['prop'];
                    if (!array_key_exists($prop, $value))
                        $value[$prop] = [];
                    if ('' === $matches['idx']) {
                        $len = count($value[$prop]);
                        $value[$prop][] = [];
                        $value = &$value[$prop][$len];
                    } else {
                        $idx = (int)$matches['idx'];
                        $value = &$value[$prop][$idx];
                    }
                } else {
                    $value = &$value[$node];
                }
            }
        }
    }

    public function hasValue(?string $path=null, string $delimiter='.')
    {
        if (is_null($path)) return true;
        try {
            $this->getValue($path, $delimiter);
            return true;
        } catch (\OutOfRangeException $e) {
            return false;
        }
    }

    public function getValue(?string $path=null, string $delimiter='.')
    {
        if (is_null($path)) return $this->_data;

        $nodes = explode($delimiter, $path);
        $value = $this->_data;

        foreach ($nodes as $node) {
            if ($node === '') continue;
            if (preg_match('/(?P<prop>\w+)\[(?P<idx>\d*)\]/', $node, $matches)) {
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

    public function setLogger(LoggerInterface $logger): void
    {
        $this->_logger = $logger;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->_logger;
    }
}
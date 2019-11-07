<?php

use Burdock\Config\Config;
use PHPUnit\Framework\TestCase;


class ConfigTest extends TestCase
{
    public function test_construct()
    {
        $config = new Config([
            'TEST1' => 'VALUE1',
            'TEST2' => 'VALUE2',
        ]);
        $this->assertEquals('VALUE1', $config->getValue('TEST1'));
        $this->assertEquals('VALUE2', $config->getValue('TEST2'));
    }

    public function test_for_deep_hierarchy()
    {
        $config = new Config([
            'TEST3' => [
                'key1' => [
                    ['ABC' => 'abc'],
                    'XYZ',
                ]
            ],
        ]);
        $this->assertEquals('abc', $config->getValue('TEST3.key1[0].ABC'));
        $this->assertEquals('XYZ', $config->getValue('TEST3.key1[1]'));
    }

    public function test_toml()
    {
        $config = Config::load(__DIR__.DIRECTORY_SEPARATOR.'config.toml');
        $this->assertNotNull($config->getValue());
        $this->assertEquals('VALUE_FOR_TEST1', $config->getValue('TEST1'));
        $this->assertEquals('VALUE_FOR_TEST2', $config->getValue('TEST2'));
        $this->assertEquals('', $config->getValue('TEST3'));
    }

    public function test_json()
    {
        $config = Config::load(__DIR__.DIRECTORY_SEPARATOR.'config.json');
        $this->assertNotNull($config->getValue());
        $this->assertEquals('value_a', $config->getValue('prop_a'));
        $this->assertEquals('value_c', $config->getValue('prop_b.prop_c'));
        $this->assertEquals('value_d2', $config->getValue('prop_d[2]'));
    }

    public function test_yaml()
    {
        $config = Config::load(__DIR__.DIRECTORY_SEPARATOR.'config.yaml');
        $this->assertNotNull($config->getValue());
        $this->assertEquals('value_a', $config->getValue('prop_a'));
        $this->assertEquals('value_c', $config->getValue('prop_b.prop_c'));
        $this->assertEquals('value_d2', $config->getValue('prop_d[2]'));
    }

    public function test_notExists()
    {
        $this->expectException('\InvalidArgumentException');
        $config = Config::load(__DIR__.DIRECTORY_SEPARATOR.'aaa.yaml');
    }
}

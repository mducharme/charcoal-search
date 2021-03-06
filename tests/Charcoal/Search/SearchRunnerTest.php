<?php

namespace Charcoal\Search\Tests;

use \Psr\Log\NullLogger;
use \Cache\Adapter\Void\VoidCachePool;

use \Charcoal\Factory\GenericFactory;

use \Charcoal\Model\MetadataLoader;

use \Charcoal\Search\SearchConfig;
use \Charcoal\Search\SearchRunner;

use \PHPUnit_Framework_TestCase;

/**
 *
 */
class SearchRunnerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Charcoal\Factory\FactoryInterface $modelFactory
     */
    private $modelFactory;

    /**
     * @var SearchRunner $obj
     */
    private $obj;

    /**
     * @return \Charcoal\Factory\FactoryInterface
     */
    private function modelFactory()
    {
        if (!$this->modelFactory) {
            $container = $GLOBALS['container'];
            $metadataLoader = new MetadataLoader([
                'logger' => new NullLogger(),
                'base_path' => __DIR__,
                'paths' => ['metadata'],
                'cache'  => new VoidCachePool()
            ]);

            $this->modelFactory = new GenericFactory([
                'arguments'     => [[
                    'logger'            => new NullLogger(),
                    'metadata_loader'   => $metadataLoader
                ]]
            ]);
        }
        return $this->modelFactory;
    }

    /**
     * @return SearchRunner
     */
    private function obj($searchConfig=[])
    {
        $this->obj = new SearchRunner([
            'search_config' => $searchConfig,
            'model_factory' => $this->modelFactory(),
            'logger'        => new NullLogger()
        ]);
        return $this->obj;
    }

    /**
     * @return array
     */
    private function defaultSearchObjects()
    {
        return [
            'foo' => [
                'search_type' => 'custom',
                'callback' => function($kw) {
                    return ['test'];
                }
            ],
            'bar' => [
                'search_type' => 'custom',
                'callback' => function($kw) {
                    return ['foo', 'bar', 'baz'];
                }
            ]
        ];
    }

    /**
     *
     */
    public function testSearchConfig()
    {
        $obj = $this->obj([
            'foo' => 'bar'
        ]);
        $this->assertInstanceOf(SearchConfig::class, $obj->searchConfig());
        $this->assertEquals('bar', $obj->searchConfig()['foo']);
    }

    /**
     *
     */
    public function testSearchInvalidKeywordThrowsException()
    {
        $obj = $this->obj();
        $this->setExpectedException('\InvalidArgumentException');
        $obj->search([]);
    }

    /**
     *
     */
    public function testSearchEmptyKeywordThrowsException()
    {
        $obj = $this->obj();
        $this->setExpectedException('\InvalidArgumentException');
        $obj->search('');
    }

    /**
     *
     */
    public function testSearchWithoutObjectsThrowsException()
    {
        $obj = $this->obj([
            'foo' => 'nar'
        ]);
        $this->setExpectedException('\InvalidArgumentException');
        $obj->search('foo');
    }

    /**
     *
     */
    public function testSearchWithoutSearchTypeThrowsException()
    {
        $obj = $this->obj([
            'objects' => [
                'foo'   => []
            ]
        ]);
        $this->setExpectedException('\InvalidArgumentException');
        $obj->search('foo');
    }

    /**
     *
     */
    public function testSarchInvalidSearchTypeThrowsException()
    {
        $obj = $this->obj([
            'objects' => [
                'foo'   => [
                    'search_type' => '_invalid'
                ]
            ]
        ]);
        $this->setExpectedException('\InvalidArgumentException');
        $obj->search('foo');
    }

    /**
     *
     */
    public function testSearchCustomWithoutCallbackThrowsException()
    {
        $obj = $this->obj([
            'objects'   =>[
                'foo'       => [
                    'search_type' => 'custom'
                ]
            ]
        ]);
        $this->setExpectedException('\InvalidArgumentException');
        $obj->search('foo');
    }

        /**
     *
     */
    public function testSearchCustomInvalidCallbackThrowsException()
    {
        $obj = $this->obj([
            'objects'   => [
                'foo'       => [
                    'search_type'   => 'custom',
                    'callback'      => '_invalid_callback'
                ]
            ]
        ]);
        $this->setExpectedException('\InvalidArgumentException');
        $obj->search('foo');
    }

    /**
     *
     */
    public function testSearchCustom()
    {
        $obj = $this->obj([
            'objects'   => $this->defaultSearchObjects()
        ]);

        $expected = [
            'foo'   => ['test'],
            'bar'   => ['foo','bar', 'baz']
        ];

        $res = $obj->search('foo');
        $this->assertEquals($expected, $res);
        $this->assertEquals($expected, $obj->results());
        $this->assertEquals(4, $obj->searchLog()->numResults());
    }

    /**
     *
     */
    public function testSearchIdentLogsIdent()
    {
        $obj = $this->obj([
            'ident'     => 'foobar',
            'objects'   => $this->defaultSearchObjects()
        ]);

        $res = $obj->search('foo');
        $this->assertEquals('foobar', $obj->searchLog()->searchIdent());
    }
}

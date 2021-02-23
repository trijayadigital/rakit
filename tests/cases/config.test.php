<?php

defined('DS') or exit('No direct script access.');

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        // ..
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Config::$items = [];
        Config::$cache = [];
    }

    /**
     * Test untuk method Config::get().
     *
     * @group system
     */
    public function testItemsCanBeRetrievedFromConfigFiles()
    {
        $this->assertEquals('UTF-8', Config::get('application.encoding'));
        $this->assertEquals('mysql', Config::get('database.connections.mysql.driver'));
        $this->assertEquals('dashboard', Config::get('dashboard::meta.package'));
    }

    /**
     * Test untuk method Config::has().
     *
     * @group system
     */
    public function testHasMethodIndicatesIfConfigItemExists()
    {
        $this->assertFalse(Config::has('application.foo'));
        $this->assertTrue(Config::has('application.encoding'));
    }

    /**
     * Test untuk method Config::set().
     *
     * @group system
     */
    public function testConfigItemsCanBeSet()
    {
        Config::set('application.encoding', 'foo');
        Config::set('dashboard::meta.package', 'bar');

        $this->assertEquals('foo', Config::get('application.encoding'));
        $this->assertEquals('bar', Config::get('dashboard::meta.package'));
    }

    /**
     * Test item config tetap bisa diubah lagi setelah seluruh file termuat.
     *
     * @group system
     */
    public function testItemsCanBeSetAfterEntireFileIsLoaded()
    {
        Config::get('application');
        Config::set('application.key', 'foobarbazqux123456890ABCDEFGHIJKL000000000');
        $application = Config::get('application');

        $this->assertEquals('foobarbazqux123456890ABCDEFGHIJKL000000000', $application['key']);

        Config::set('application.key', 'mySecretKeyIsSoDarnLongSoPeopleCantRememberIt');

        $this->assertEquals('mySecretKeyIsSoDarnLongSoPeopleCantRememberIt', Config::get('application.key'));
    }
}

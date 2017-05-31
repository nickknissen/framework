<?php

namespace Illuminate\Tests\Foundation;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Application;


class FoundationHelpersTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function setUp() {
        register_shutdown_function(function() {
            if(file_exists(public_path('mix-manifest.json'))) {
                unlink(public_path('mix-manifest.json'));
            }
        });
    }

    public function testCache()
    {
        $app = new Application;
        $app['cache'] = $cache = m::mock('StdClass');

        // 1. cache()
        $this->assertInstanceOf('StdClass', cache());

        // 2. cache(['foo' => 'bar'], 1);
        $cache->shouldReceive('put')->once()->with('foo', 'bar', 1);
        cache(['foo' => 'bar'], 1);

        // 3. cache('foo');
        $cache->shouldReceive('get')->once()->andReturn('bar');
        $this->assertEquals('bar', cache('foo'));

        // 4. cache('baz', 'default');
        $cache->shouldReceive('get')->once()->with('baz', 'default')->andReturn('default');
        $this->assertEquals('default', cache('baz', 'default'));
    }

    /**
     * @expectedException Exception
     */
    public function testCacheThrowsAnExceptionIfAnExpirationIsNotProvided()
    {
        cache(['foo' => 'bar']);
    }

    public function testUnversionedElixir()
    {
        $file = 'unversioned.css';

        app()->singleton('path.public', function () {
            return __DIR__;
        });

        touch(public_path($file));

        $this->assertEquals('/'.$file, elixir($file));

        unlink(public_path($file));
    }

    public function testMixHotReloading()
    {
        $file = 'app.css';

        app()->singleton('path.public', function () {
            return __DIR__;
        });

        touch(public_path('hot'));

        $mixPath = mix($file);

        unlink(public_path('hot'));

        $this->assertEquals('//localhost:8080/'.$file, (string) $mixPath);
    }

    /**
     * @expectedException Exception
     */
    public function testMixNoManifest()
    {
        mix('/app.js');
    }

    public function testMixVersioned()
    {
        $file = 'app.css';
        $versionedFile = 'app.d41d8cd98f00b204e9800998ecf8427e.css';

        app()->singleton('path.public', function () {
            return __DIR__;
        });

        file_put_contents(
            public_path('mix-manifest.json'),
            json_encode(['/app.css' => "/{$versionedFile}"], JSON_UNESCAPED_SLASHES)
        );

        $this->assertEquals('/'.$versionedFile, (string) mix($file));

        unlink(public_path('mix-manifest.json'));
    }

    /**
     * @expectedException Exception
     */
    public function testMixMissingFile()
    {
        app()->singleton('path.public', function () {
            return __DIR__;
        });

        $dir = public_path('mix-manifest.json');
        file_put_contents(
            $dir,
            json_encode(['/app.css' => "/app.css"])
        );
        mix('app.js');
    }
}

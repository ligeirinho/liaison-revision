<?php

namespace Liaison\Revision\Tests\Paths;

use CodeIgniter\Test\CIUnitTestCase;
use Liaison\Revision\Config\ConfigurationResolver;
use Tests\Support\Configurations\SimpleConfig;
use Tests\Support\Pathfinders\AbsoluteDestinationPathfinder;
use Tests\Support\Pathfinders\InvalidPathfinder;
use Tests\Support\Pathfinders\SimplePathfinder;

class BasePathfinderTest extends CIUnitTestCase
{
    public function testNormalGetPaths()
    {
        $finder  = new SimplePathfinder();
        $subset1 = [
            'origin'      => realpath(SYSTEMPATH . '../spark'),
            'destination' => 'spark',
        ];
        $subset2 = [
            'origin'      => realpath(SYSTEMPATH . '../app/Config/App.php'),
            'destination' => 'app/Config/App.php',
        ];
        $this->assertContains($subset1, $finder->getPaths());
        $this->assertContains($subset2, $finder->getPaths());
    }

    public function testAbsoluteDestinationPathThrowsException()
    {
        $this->expectException('\Liaison\Revision\Exception\InvalidArgumentException');
        $this->expectExceptionMessage('"' . ROOTPATH . 'spark" must be a relative path.');
        (new AbsoluteDestinationPathfinder())->getPaths();
    }

    public function testInvalidPathsGiven()
    {
        $this->expectException('\Liaison\Revision\Exception\InvalidArgumentException');
        $this->expectExceptionMessage('"' . SYSTEMPATH . '../foo/bar" is not a valid origin file or directory.');
        (new InvalidPathfinder())->getPaths();
    }

    public function testEmptyIgnoredPaths()
    {
        $this->assertEmpty((new SimplePathfinder())->getIgnoredPaths());
    }

    public function testArrayIgnoredPaths()
    {
        $config = new ConfigurationResolver(new SimpleConfig());
        $finder = new SimplePathfinder($config);

        $this->assertIsArray($finder->getIgnoredPaths());
        $this->assertContains(realpath(ROOTPATH . 'app/.htaccess'), $finder->getIgnoredPaths());
        $this->assertContains(realpath(APPPATH . 'Config/Constants.php'), $finder->getIgnoredPaths());
    }

    /**
     * @param string $invalid
     * @param string $type
     * @param string $message
     * @dataProvider invalidPathsProvider
     */
    public function testInvalidIgnoredPaths(string $invalid, string $type = 'file', string $message = '')
    {
        $config = new ConfigurationResolver(new SimpleConfig());
        if ('dir' === $type) {
            array_push($config->getConfig()->ignoredDirs, $invalid);
        } else {
            array_push($config->getConfig()->ignoredFiles, $invalid);
        }

        $this->expectException('\Liaison\Revision\Exception\InvalidArgumentException');
        $this->expectExceptionMessage($message);
        (new SimplePathfinder($config))->getIgnoredPaths();
    }

    public function invalidPathsProvider()
    {
        return [
            [APPPATH, 'dir', '"' . APPPATH . '" must be a relative path.'],
            ['foo/bar', 'dir', '"foo/bar" is not a valid directory.'],
            [ROOTPATH . '.gitignore', 'file', '"' . ROOTPATH . '.gitignore" must be a relative path.'],
            ['.env', 'file', '".env" is not a valid file.'],
        ];
    }
}

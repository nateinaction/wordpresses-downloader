<?php

namespace nateinaction\WordPressesDownloader;

class ActionsTest extends \PHPUnit\Framework\TestCase
{
    public $actions;

    public static $wp_org_server = '/tmp/wp-org-server';
    public static $wp_zip_file = 'test.zip';
    public static $wp_core_package = '/tmp/wp-core-package';

    public function setup(): void {
        $this->actions = new Actions();
    }

    public static function setUpBeforeClass(): void
    {
        // Create dirs
        mkdir(self::$wp_org_server . '/wordpress', 0777, true);
        mkdir(self::$wp_core_package);

        // Place zip on org server mock
        file_put_contents(
            self::$wp_org_server . '/' . self::$wp_zip_file,
            file_get_contents(__DIR__ . '/files/'  . self::$wp_zip_file)
        );
    }

    public function dataSomeMajorMinorVersions(): array {
        return [
            [
                'latest_major_minor' => '5.3.3',
                'expected' => ['5.3.3', '5.3.2', '5.3.1', '5.3'],
            ],
            [
                'latest_major_minor' => '5.2',
                'expected' => ['5.2'],
            ],
        ];
    }

    /**
     * @param $latest_major_minor
     * @dataProvider dataSomeMajorMinorVersions
     */
    public function testGetAllMinorVersions($latest_major_minor, $expected): void {
        $actual = $this->actions->getAllMinorVersions($latest_major_minor);
        $this->assertEquals($expected, $actual);
    }

    public function testParseVersionJson(): void {
        $json_file_path = __DIR__ . '/files/test-versions.json';
        $actual = $this->actions->parseVersionJson($json_file_path, 5.1);
        $expected = [
            'latest' => [
                'current' => '5.2.3',
                '5.2' => '5.2.3',
                '5.1' => '5.1.2'
            ],
            'minor_versions' => [
                '5.2.3',
                '5.2.2',
                '5.2.1',
                '5.2',
                '5.1.2',
                '5.1.1',
                '5.1',
            ],
        ];
        $this->assertEquals($expected, $actual);
    }

    /**
     * @param string $minimum_version
     * @param string $version
     * @param bool $expected
     * @dataProvider dataVersionNumberAboveMinimum
     */
    public function testVersionNumberAboveMinimum(string $minimum_version, string $version, bool $expected): void {
        $actual = $this->actions->versionNumberAboveMinimum($minimum_version, $version);
        $this->assertEquals($expected, $actual);
    }

    public function dataVersionNumberAboveMinimum(): array {
        return [
            'above_minimum' => ['4.9', '5.9', true],
            'equal_to_minimum' => ['4.9', '4.9', true],
            'equal_to_minimum_2' => ['4.9', '4.9.99.9.9', true],
            'below_minimum' => ['4.9', '3.9', false],
        ];
    }

    /**
     * @param string $version
     * @param string $expected
     * @dataProvider dataStripWpMinorVersion
     */
    public function testStripWpMinorVersion(string $version, string $expected): void {
        $actual = $this->actions->stripWpMinorVersion($version);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function dataStripWpMinorVersion(): array {
        return [
            'new_release' => ['5.3', '5.3'],
            'first_patch' => ['5.3.1', '5.3'],
            'other' => ['abcdef', 'abc'],
        ];
    }

    public function testDownloadZip(): string {
        $download_url = self::$wp_org_server . '/' . self::$wp_zip_file;
        $download_dir = self::$wp_core_package;
        $zip_location = $this->actions->downloadZip($download_url, $download_dir);
        $this->assertFileExists($zip_location);

        return $zip_location;
    }

    /**
     * @depends testDownloadZip
     */
    public function testUnzipDir(): void {
        $zip_location = func_get_args()[0];
        $unzip_location = '/tmp/test_unzip/';
        $file = $unzip_location . '/file.txt';

        $this->actions->unzipDir($zip_location, $unzip_location);
        $this->assertFileExists($file);
        $this->assertDirectoryNotExists($unzip_location . '/wordpress/');

        $file_contents = file_get_contents($file);
        $this->assertStringContainsString('Hello, world!', $file_contents);
    }
}

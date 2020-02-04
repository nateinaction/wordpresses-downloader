<?php

namespace nateinaction\WordPressesDownloader;

class WordPressTest extends \PHPUnit\Framework\TestCase
{
    const WP_CORE_DIR = '/usr/share/php/nateinaction/wp_core';

    public function readAvailableVersions(): array {
        $available_versions = file_get_contents(self::WP_CORE_DIR . '/available_versions.json');
        return json_decode($available_versions, true);
    }

    /**
     * @return array
     */
    public function dataWpVersions(): array {
        return array_map(
            fn(string $major_version): array => [$major_version],
            array_keys($this->readAvailableVersions()),
        );
    }

    /**
     * @dataProvider dataWpVersions
     */
    public function testWpCli($major_version): void
    {
        $dir = self::WP_CORE_DIR . "/{$major_version}";

        // Verify the directory exists
        $this->assertTrue(is_dir($dir));

        $actual = [];
        $rc = 255;
        exec("wp --allow-root --path='{$dir}' core version", $actual, $rc);

        // Verify that the WP CLI command completed successfully
        $this->assertEquals(0, $rc, "WP CLI was unable to run on '{$dir}'");

        // Verify that the WordPress major version matches directory name
        $this->assertEquals(
            preg_match("/^{$major_version}/", $actual[0]),
            1,
            "WordPress version '{$actual[0]}' does not match directory version '{$dir}'"
        );

        // Verify that the WordPress minor version matches version in available_versions.json
        $available_versions = $this->readAvailableVersions();
        $this->assertEquals(
            $available_versions[$major_version]['full_version'],
            $actual[0],
            "WordPress minor version '{$actual[0]}' does not match available_versions.json version '{$available_versions[$major_version]['full_version']}'"
        );
    }

    public function testVerifyNoAnomalousFilesInDir(): void {
        $ignore_list = [
            '.',
            '..',
            'available_versions.json',
            'wp-config.php',
        ];
        $dirs = array_filter(
            scandir(self::WP_CORE_DIR),
            fn(string $item): bool => ! in_array($item, $ignore_list),
        );
        $available_versions = array_keys($this->readAvailableVersions());
        $collection = array_diff($dirs, $available_versions);

        $this->assertEmpty(
            $collection,
            sprintf("Unexpected items found in WP Core directory: %s", var_export($collection, true))
        );
    }

    /**
     * @dataProvider dataWpVersions
     */
    public function testVerifyWpConfigReachable($version): void {
        $wp_version_dir = self::WP_CORE_DIR . "/{$version}";
        $wp_config_symlink = "{$wp_version_dir}/wp-config.php";
        $wp_config_relative_path = readlink($wp_config_symlink);
        $wp_config_absolute_path = "{$wp_version_dir}/{$wp_config_relative_path}";
        $wp_config_contents = file_get_contents($wp_config_absolute_path);

        // Verify symlink is valid
        $this->assertStringStartsWith("<?php", $wp_config_contents);
    }
}

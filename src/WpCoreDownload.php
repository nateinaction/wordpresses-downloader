<?php

namespace nateinaction\WordPressesDownloader;

require __DIR__ . '/../vendor/autoload.php';

$minimum_supported_version = '4.9';
$wordpress_version_api = 'https://api.wordpress.org/core/version-check/1.7/';
$wordpress_download_url = 'https://downloads.wordpress.org/release/wordpress-%s-no-content.zip';
$core_source_dir = __DIR__ . '/../build/usr/share/php/nateinaction/wp_core';
$artifacts_dir = __DIR__ . '/../artifacts';

// Ensure output dirs exist
foreach ([$artifacts_dir, $core_source_dir] as $dir) {
    if (!is_dir($dir)) {
        throw new \Exception("'{$dir}' directory does not exist");
    }
}

$actions = new Actions();
$available_versions = $actions->parseVersionJson($wordpress_version_api, $minimum_supported_version);

// Put available_versions.json into build and artifacts dirs
file_put_contents(
    "{$core_source_dir}/available_versions.json",
    json_encode($available_versions, JSON_PRETTY_PRINT)
);
copy("{$core_source_dir}/available_versions.json", "{$artifacts_dir}/available_versions.json");

// Download WordPress versions
copy(__DIR__ . "/files/wp-config.php", "{$core_source_dir}/wp-config.php");
foreach ($available_versions['minor_versions'] as $minor_version) {
    $download_location = $actions->downloadZip(
        sprintf($wordpress_download_url, $minor_version),
        $core_source_dir
    );
    $actions->unzipDir($download_location, "{$core_source_dir}/{$minor_version}/");
    symlink("../wp-config.php", "{$core_source_dir}/{$minor_version}/wp-config.php");
}

$latest_dir = "{$core_source_dir}/latest";
mkdir($latest_dir);
foreach ($available_versions['latest'] as $major_version => $minor_version) {
    symlink("../{$minor_version}", "{$latest_dir}/{$major_version}");
}


<?php

namespace nateinaction\WordPressesDownloader;

class Actions
{
    /**
     * Parse version information from the WP upgrades API
     * https://api.wordpress.org/core/version-check/1.7/
     *
     * @param string $json_file_path Path to the downloaded json from WP upgrades API
     * @param string $minimum_supported_version Minimum version to parse
     * @return array Where WP version numbers are the keys with associated data related to their version
     */
    public function parseVersionJson(string $json_file_path, string $minimum_supported_version): array
    {
        $latest = [];
        $minor_versions = [];
        $file_content = file_get_contents($json_file_path);
        $versions_data = json_decode($file_content, true);
        foreach ($versions_data['offers'] as $index => $package) {
            $full_version = $package['version'];
            if ($package['response'] !== 'autoupdate') {
                $latest['current'] = $full_version;
                continue;
            };

            $major_version = $this->stripWpMinorVersion($full_version);
            if ($this->versionNumberAboveMinimum($minimum_supported_version, $major_version)) {
                $latest[$major_version] = $full_version;
                $minor_versions = array_merge($minor_versions, $this->getAllMinorVersions($full_version));
            }
        }
        return [
            'latest' => $latest,
            'minor_versions' => $minor_versions,
        ];
    }

    /**
     * Get all minor versions associated with a major version
     *
     * WordPress does not adhear to semantic versioning, instead the first two version numbers
     * represent a major version i.e. 4.9. This creates an array of available minor versions
     * associated with a major version.
     *
     * @param string $latest_major_minor Expect the latest version release for a specific major version i.e. 5.3.2
     * @return array A list of all minor versions associated with this major version i.e. ['5.3.2', '5.3.1', '5.3']
     */
    public function getAllMinorVersions(string $latest_major_minor): array
    {
        $exploded_version = explode('.', $latest_major_minor);
        if (! isset($exploded_version[2])) return [$latest_major_minor];

        $major_version = "{$exploded_version[0]}.{$exploded_version[1]}";
        $all_minor_versions = [];
        $latest_minor = $exploded_version[2];
        for ($minor_version = intval($latest_minor); $minor_version >= 0; $minor_version--) {
            if ($minor_version === 0) {
                $all_minor_versions[] = "{$major_version}";
            } else {
                $all_minor_versions[] = "{$major_version}.{$minor_version}";
            }
        }
        return $all_minor_versions;
    }

    /**
     * Validates that WP major version is above the required minimum
     *
     * @param string $minimum_version Minimum WP version
     * @param string $version WP version to inspect
     * @return bool True if version number is above the minimum
     */
    public function versionNumberAboveMinimum(string $minimum_version, string $version): bool
    {
        $minimum_version = number_format($minimum_version, 1);
        $version = number_format($version, 1);
        return ($version >= $minimum_version);
    }

    /**
     * Strip minor version from WP versions
     *
     * WordPress does not adhear to semantic versioning, instead the first two version numbers
     * represent a major version i.e. 4.9. This function strips minor versions (4.9.1 => 4.9)
     *
     * @param  string $version
     * @return string
     */
    public function stripWpMinorVersion(string $version): string
    {
        return substr($version, 0, 3);
    }

    /**
     * Downloads a WP Core zip and places it in a location
     *
     * @param string $download_url Url used to download the zip
     * @param string $download_dir Directory where a zip is placed
     * @return string Location where the zip has been downloaded to
     */
    public function downloadZip(string $download_url, string $download_dir): string
    {
        $hash_filename = hash('md5', $download_url);
        $download_location = "{$download_dir}/{$hash_filename}.zip";
        file_put_contents($download_location, file_get_contents($download_url));
        return $download_location;
    }

    /**
     * Unzip and cleanup a WordPress core zip into a specific location
     *
     * @param string $zip_location Location where the WP core zip lives
     * @param string $unzip_location Location where the WP core zip should be unzipped
     * @return void
     */
    public function unzipDir(string $zip_location, string $unzip_location): void
    {
        mkdir("{$unzip_location}", 0777, true);
        exec("unzip -d {$unzip_location} {$zip_location}");
        exec("mv {$unzip_location}/wordpress/* {$unzip_location}");
        rmdir("{$unzip_location}/wordpress");
        unlink($zip_location);
    }
}

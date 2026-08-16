<?php

/**
 * LivingWord — fetch external build dependencies.
 *
 * Downloads the latest stable pkg_cwmscripture release from GitHub. The build
 * bundles the package whole, not its three inner zips — see script.install.php.
 *
 * Fails hard on any network, API, or verification error — offline builds are
 * not supported because shipping a stale scripture library would silently
 * hide breakage against the latest API.
 */

const SCRIPTURE_REPO         = 'Joomla-Bible-Study/CWMScriptureLinks';
const SCRIPTURE_PACKAGE_NAME = 'pkg_cwmscripture';
const SCRIPTURE_INNER_ZIPS   = [
    'lib_cwmscripture.zip',
    'plg_content_scripturelinks.zip',
    'plg_task_cwmscripture.zip',
];

/**
 * Fetch the latest stable pkg_cwmscripture release into the given vendor
 * directory, verifying it carries the extensions LivingWord depends on.
 *
 * @param   string  $vendorDir  Absolute path where inner zips should land.
 * @param   bool    $verbose    Whether to echo progress.
 *
 * @return  array{version: string, zips: array<string, string>}
 *
 * @throws  RuntimeException on any failure.
 */
function fetchScriptureDependencies(string $vendorDir, bool $verbose = false): array
{
    if (!is_dir($vendorDir) && !mkdir($vendorDir, 0755, true) && !is_dir($vendorDir)) {
        throw new \RuntimeException("Cannot create vendor directory: $vendorDir");
    }

    $release = ghFetchLatestRelease(SCRIPTURE_REPO);
    $tag     = $release['tag_name'] ?? '';
    $version = ltrim($tag, 'v');

    if ($version === '') {
        throw new \RuntimeException('GitHub release has no tag_name');
    }

    $assetName = SCRIPTURE_PACKAGE_NAME . '-' . $version . '.zip';
    $assetUrl  = null;

    foreach ($release['assets'] ?? [] as $asset) {
        if (($asset['name'] ?? '') === $assetName) {
            $assetUrl = $asset['browser_download_url'] ?? null;
            break;
        }
    }

    if ($assetUrl === null) {
        throw new \RuntimeException(
            sprintf('Asset %s not found in %s release %s', $assetName, SCRIPTURE_REPO, $tag)
        );
    }

    if ($verbose) {
        echo "  Fetching $assetName from $assetUrl\n";
    }

    $pkgZipPath = $vendorDir . '/' . $assetName;
    ghDownload($assetUrl, $pkgZipPath);

    // Kept whole: unpacking here is what made the scripture extensions package
    // children of LivingWord, so removing LivingWord uninstalled Proclaim's stack.
    $canonical = $vendorDir . '/' . SCRIPTURE_PACKAGE_NAME . '.zip';

    try {
        $zip = new ZipArchive();

        if ($zip->open($pkgZipPath) !== true) {
            throw new \RuntimeException("Cannot open downloaded package: $pkgZipPath");
        }

        // Verified, not extracted — catch a release missing an extension here
        // rather than on a site.
        foreach (SCRIPTURE_INNER_ZIPS as $innerZip) {
            if ($zip->locateName($innerZip) === false) {
                $zip->close();

                throw new \RuntimeException("Expected inner zip $innerZip not found in $assetName");
            }
        }

        $zip->close();

        if (file_exists($canonical) && !unlink($canonical)) {
            throw new \RuntimeException("Cannot replace $canonical");
        }

        if (!rename($pkgZipPath, $canonical)) {
            throw new \RuntimeException("Failed to move $assetName to vendor directory");
        }

        if ($verbose) {
            echo '  + ' . SCRIPTURE_PACKAGE_NAME . '.zip (' . count(SCRIPTURE_INNER_ZIPS) . " extensions inside)\n";
        }

        return ['version' => $version, 'zips' => [SCRIPTURE_PACKAGE_NAME . '.zip' => $canonical]];
    } finally {
        if (file_exists($pkgZipPath)) {
            unlink($pkgZipPath);
        }
    }
}

/**
 * Resolve a GitHub API token from the environment, falling back to the
 * `gh` CLI's stored credential. Without a token the public API caps at
 * 60 requests per hour per IP, which the build trips during scripture
 * dependency fetch.
 */
function ghToken(): string
{
    $token = getenv('GITHUB_TOKEN') ?: getenv('GH_TOKEN') ?: '';

    if ($token !== '') {
        return $token;
    }

    $process = @proc_open(
        ['gh', 'auth', 'token'],
        [1 => ['pipe', 'w'], 2 => ['file', '/dev/null', 'w']],
        $pipes
    );

    if (!\is_resource($process)) {
        return '';
    }

    $output = stream_get_contents($pipes[1]) ?: '';
    fclose($pipes[1]);
    $code = proc_close($process);

    return $code === 0 ? trim($output) : '';
}

/**
 * Call the GitHub REST API for the latest release of a repo.
 *
 * @param   string  $repo  owner/name
 *
 * @return  array  Decoded JSON response.
 *
 * @throws  RuntimeException on any HTTP, JSON, or rate-limit failure.
 */
function ghFetchLatestRelease(string $repo): array
{
    $url = "https://api.github.com/repos/$repo/releases/latest";

    $headers = [
        'User-Agent: CWMLivingWord-Build',
        'Accept: application/vnd.github+json',
        'X-GitHub-Api-Version: 2022-11-28',
    ];

    $token = ghToken();

    if ($token !== '') {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 30,
    ]);

    $body   = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error  = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        throw new \RuntimeException("GitHub API request failed: $error");
    }

    if ($status !== 200) {
        throw new \RuntimeException("GitHub API returned HTTP $status for $url: " . substr($body, 0, 500));
    }

    $data = json_decode($body, true);

    if (!\is_array($data)) {
        throw new \RuntimeException('GitHub API returned invalid JSON');
    }

    return $data;
}

/**
 * Download a URL to a local path, following redirects.
 *
 * @throws RuntimeException
 */
function ghDownload(string $url, string $destination): void
{
    $fp = fopen($destination, 'wb');

    if ($fp === false) {
        throw new \RuntimeException("Cannot open $destination for writing");
    }

    $headers = ['User-Agent: CWMLivingWord-Build'];
    $token   = ghToken();

    if ($token !== '') {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_FILE           => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 120,
    ]);

    $ok     = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error  = curl_error($ch);
    curl_close($ch);
    fclose($fp);

    if (!$ok || $status !== 200) {
        if (file_exists($destination)) {
            unlink($destination);
        }

        throw new \RuntimeException("Download failed (HTTP $status): $error");
    }
}

// CLI entry — fires only when invoked directly (php build/fetch_dependencies.php),
// not when included via require_once. Writes build/vendor/pkg_cwmscripture.zip for
// cwm-package's `prebuilt` include to pick up.
if (PHP_SAPI === 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $vendorDir = __DIR__ . '/vendor';
    $verbose   = in_array('--verbose', $argv ?? [], true) || in_array('-v', $argv ?? [], true);

    echo "Fetching scripture dependencies into $vendorDir\n";

    try {
        $result = fetchScriptureDependencies($vendorDir, $verbose);
        echo "  lib_cwmscripture @ v{$result['version']} (from " . SCRIPTURE_REPO . ")\n";

        foreach ($result['zips'] as $name => $path) {
            echo "  $name -> $path\n";
        }
    } catch (Throwable $e) {
        fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
        exit(1);
    }
}

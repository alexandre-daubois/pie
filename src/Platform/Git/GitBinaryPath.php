<?php

declare(strict_types=1);

namespace Php\Pie\Platform\Git;

use Composer\Util\Platform;
use Php\Pie\Util\Process;

use function file_exists;
use function is_executable;
use function preg_match;

/**
 * @internal This is not public API for PIE, so should not be depended upon unless you accept the risk of BC breaks
 *
 * @immutable
 */
class GitBinaryPath
{
    private function __construct(
        public readonly string $gitBinaryPath,
    ) {
    }

    /** @param non-empty-string $gitBinary */
    public static function fromGitBinaryPath(string $gitBinary): self
    {
        self::assertValidLookingGitBinary($gitBinary);

        return new self($gitBinary);
    }

    public function fetchSubmodules(string $path): string
    {
        return Process::run([$this->gitBinaryPath, 'submodule', 'update', '--init', '--force', '--remote'], $path);
    }

    private static function assertValidLookingGitBinary(string $gitBinary): void
    {
        if (! file_exists($gitBinary)) {
            throw Exception\InvalidGitBinaryPath::fromNonExistentgitBinary($gitBinary);
        }

        if (! Platform::isWindows() && ! is_executable($gitBinary)) {
            throw Exception\InvalidGitBinaryPath::fromNonExecutableGitBinary($gitBinary);
        }

        $output = Process::run([$gitBinary, '--version']);

        if (! preg_match('/git version \d+\.\d+\.\d+/', $output)) {
            throw Exception\InvalidGitBinaryPath::fromInvalidGitBinary($gitBinary);
        }
    }
}

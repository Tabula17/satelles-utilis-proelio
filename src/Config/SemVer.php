<?php

namespace Tabula17\Satelles\Utilis\Config;

use InvalidArgumentException;

class SemVer extends AbstractDescriptor implements \Stringable
{
    protected(set) int $major = 1, $minor = 0, $patch = 0;
    protected(set) string $preRelease = '';
    protected(set) string $build = '';

    /**
     * @param int $major
     * @param int $minor
     * @param int $patch
     * @param string $preRelease
     * @param string $build
     */
    public function __construct(int $major = 1, int $minor = 0, int $patch = 0, string $preRelease = '', string $build = '')
    {
        $this->major = $major;
        $this->minor = $minor;
        $this->patch = $patch;
        $this->preRelease = $preRelease;
        $this->build = $build;
        $this->validateVersionString($this->version());
        parent::__construct();
    }


    public function increment(string $type = 'patch'): static
    {
        match ($type) {
            'major' => $this->major++,
            'minor' => $this->minor++,
            'patch' => $this->patch++,
            default => throw new \InvalidArgumentException("Invalid increment type: $type"),
        };
        return $this;
    }

    public function parse(string $version): static
    {
        if (!$this->validateVersionString($version)) {
            throw new InvalidArgumentException("Invalid SemVer version string: $version");
        }
        $parts = explode('.', $version);
        $this->major = (int)$parts[0];
        $this->minor = (int)$parts[1];
        $this->patch = (int)$parts[2];
        if (isset($parts[3])) {
            $this->preRelease = $parts[3];
        }
        if (isset($parts[4])) {
            $this->build = $parts[4];
        }
        return $this;
    }

    private function validateVersionString(string $value): bool
    {
        $regex = '/^(?P<major>0|[1-9]\d*)\.(?P<minor>0|[1-9]\d*)\.(?P<patch>0|[1-9]\d*)(?:-(?P<prerelease>(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+(?P<buildmetadata>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/';
        return preg_match($regex, $value);
    }

    public function version(): string
    {
        return implode('.', [$this->major, $this->minor, $this->patch]);
    }
    public static function fromString(string $version): static
    {
        return new static()->parse($version);
    }
    public function __toString(): string
    {
        $release = [$this->major, $this->minor, $this->patch];
        if (!empty($thi->preRelease)) {
            $release[] = $this->preRelease;
        }
        if (!empty($this->build)) {
            $release[] = $this->build;
        }
        return implode('.', $release);
    }
}
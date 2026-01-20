<?php

namespace TsfCorp\Email;

class Attachment implements \JsonSerializable
{
    private ?string $disk = null;
    private ?string $path = null;
    private ?string $name = null;

    public static function path(string $path, ?string $name = null): static
    {
        return (new static())->setPath($path, $name);
    }

    public static function disk(string $disk): static
    {
        return (new static())->setDisk($disk);
    }

    public function setPath(string $path, ?string $name = null): static
    {
        $this->path = $path;
        $this->name = $name;

        return $this;
    }

    public function setDisk(string $disk): static
    {
        $this->disk = $disk;

        return $this;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getDisk(): string
    {
        return $this->disk ?? 'local';
    }

    public function getName(): ?string
    {
        return $this->name ?? basename($this->path);
    }

    public function toArray(): array
    {
        return [
            'path' => $this->getPath(),
            'disk' => $this->getDisk(),
            'name' => $this->getName(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

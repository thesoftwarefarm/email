<?php

namespace TsfCorp\Email;

use JsonSerializable;

class Attachment implements JsonSerializable
{
    private string $path;
    private ?string $name;
    private string $disk;

    public function __construct(string $path, ?string $name = null, ?string $disk = 'local')
    {
        $this->path = $path;
        $this->name = $name;
        $this->disk = $disk;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function setDisk(string $disk): static
    {
        $this->disk = $disk;

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

    public function getName(): string
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

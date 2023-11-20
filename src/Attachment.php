<?php

namespace TsfCorp\Email;

class Attachment implements \JsonSerializable
{
    /**
     * @var null|string
     */
    private $path = null;
    /**
     * @var null|string
     */
    private $disk = null;
    /**
     * @var null|string
     */
    private $name = null;

    /**
     * @param $path
     * @param $name
     * @return static
     */
    public static function path($path, $name = null)
    {
        return (new static())->setPath($path, $name);
    }

    /**
     * @param $disk
     * @return static
     */
    public static function disk($disk)
    {
        return (new static())->setDisk($disk);
    }

    /**
     * @param $path
     * @param $name
     * @return static
     */
    public function setPath($path, $name = null)
    {
        $this->path = $path;
        $this->name = $name;

        return $this;
    }

    /**
     * @param $disk
     * @return static
     */
    public function setDisk($disk)
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * @param $name
     * @return static
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getDisk()
    {
        return $this->disk ?? 'local';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name ?? basename($this->path);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'path' => $this->getPath(),
            'disk' => $this->getDisk(),
            'name' => $this->getName(),
        ];
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}

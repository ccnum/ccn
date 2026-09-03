<?php

namespace Spip\Cli\Mutualisation\Spl;

class SplFileInfoHost extends \SplFileInfo
{
    private $host = null;

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function setHost(string $host): void
    {
        $this->host = $host;
    }
}

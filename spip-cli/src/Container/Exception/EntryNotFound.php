<?php

namespace Spip\Cli\Container\Exception;

use Psr\Container\NotFoundExceptionInterface;

/**
 * No entry was found in the container.
 */
class EntryNotFound extends \Exception implements NotFoundExceptionInterface
{
    public function __construct($id)
    {
        parent::__construct(sprintf('Identifier "%s" is not defined.', $id));
    }
}

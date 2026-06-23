<?php

use SpipLeague\EasyCodingStandard\Set\SetList;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withSets([SetList::SPIP_LEAGUE])
    ->withPaths([__DIR__ . '/bin', __DIR__ . '/src'])
    ->withRootFiles()
;

<?php

namespace Jackalope\Version;

use Jackalope\Transport\VersioningInterface;

/**
 * This interface has to be implemented by classes which want to use the generic version handler provided by jackalope
 */
interface GenericVersioningInterface extends VersioningInterface
{
    /**
     * Sets the generic version handler delivered by jackalope
     * @param VersionHandler $versionHandler
     */
    public function setVersionHandler(VersionHandler $versionHandler);
}

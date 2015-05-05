<?php
/*
 * This file is part of Sulu
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Jackalope\Version;

use Jackalope\Transport\VersioningInterface;

/**
 * This marker interface has to be implemented by classes which want to use the generic version handler provided by
 * jackalope
 */
interface GenericVersioningInterface extends VersioningInterface
{
    /**
     * Sets the generic version handler delivered by jackalope
     * @param VersionHandler $versionHandler
     */
    public function setVersionHandler(VersionHandler $versionHandler);
}

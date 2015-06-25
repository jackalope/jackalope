<?php

namespace Jackalope\Transport;
use Jackalope\Node;
use Jackalope\Property;
use PHPCR\Security\AccessControlPolicyInterface;

/**
 * Representing a set policy operation.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
class SetPolicyOperation extends Operation
{
    /**
     * The policy to set.
     *
     * @var AccessControlPolicyInterface
     */
    public $policy;

    /**
     * Whether this remove operations was later determined to be skipped
     * (i.e. a parent node is removed as well.)
     *
     * @var bool
     */
    public $skip = false;

    /**
     * @param string   $srcPath  Absolute path of the property to remove.
     * @param Property $property Property object to be removed.
     */
    public function __construct($srcPath, AccessControlPolicyInterface $policy)
    {
        parent::__construct($srcPath, self::SET_POLICY);
        $this->policy = $policy;
    }
}

<?php
/**
 * Class to handle the communication between Jackalope and Jackrabbit via Davex.
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache License Version 2.0, January 2004
 *   Licensed under the Apache License, Version 2.0 (the "License");
 *   you may not use this file except in compliance with the License.
 *   You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 *   Unless required by applicable law or agreed to in writing, software
 *   distributed under the License is distributed on an "AS IS" BASIS,
 *   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *   See the License for the specific language governing permissions and
 *   limitations under the License.
 *
 * @package jackalope
 * @subpackage transport
 */

namespace Jackalope\Transport\DavexClient\Requests;

/**
 * Class to handle a REPORT request.
 *
 * @package jackalope
 * @subpackage transport
 */
class Report extends \Jackalope\Transport\DavexClient\Requests\Base
{
    /**
     * Identifier of the used XML namespace
     * @var string
     */
    protected $nsPrefix = 'dcr';

    /**
     * URI of the used XML namespace
     * @var string
     */
    protected $nsUri = 'http://www.day.com/jcr/webdav/1.0';

    /**
     * Generates the DOMDocument representing the request to be send.
     *
     * Avaiable names:
     *  - repositorydescriptors
     *  - registerednamespaces
     *  - …
     *
     * @throws \InvalidArgumentException
     */
    public function build()
    {
        if(empty($this->arguments['name'])) {
            throw new \InvalidArgumentException('Missing report name.');
        }

        $doc = $this->dom->createElementNS($this->nsUri, $this->nsPrefix.':'.$this->arguments['name']);
        $this->dom->appendChild($doc);
    }
}
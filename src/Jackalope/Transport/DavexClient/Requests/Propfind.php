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
 * Class to handle a PROPFIND request.
 *
 * @package jackalope
 * @subpackage transport
 */
class Propfind extends \Jackalope\Transport\DavexClient\Requests\Base
{

    /**
     * Generates the XML representing the request to be send.
     *
     * Available properties:
     *  - D:workspace
     *  - dcr:workspaceName
     *
     * @throws \InvalidArgumentException
     */
    public function build()
    {
        if (empty($this->arguments['properties'])) {
            throw new \InvalidArgumentException('Missing Properties.');
        }
        if (!is_array($this->arguments['properties'])) {
            $this->arguments['properties'] = array($this->arguments['properties']);
        }

        $this->xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $this->xml .= '<D:propfind xmlns:D="DAV:" xmlns:dcr="http://www.day.com/jcr/webdav/1.0">';
        $this->xml .= '<D:prop>';
        foreach($this->arguments['properties'] as $property) {
            $this->xml .= sprintf('<%s />', $property);
        }
        $this->xml .= '</D:prop>';
        $this->xml .= '</D:propfind>';
    }
}
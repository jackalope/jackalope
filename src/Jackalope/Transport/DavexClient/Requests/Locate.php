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
 * Class to handle a Locate request.
 *
 * @package jackalope
 * @subpackage transport
 */
class Locate extends \Jackalope\Transport\DavexClient\Requests\Base
{

    /**
     * Generates the XML representing the request to be send.
     *
     * @throws \InvalidArgumentException
     */
    public function build()
    {
        if (empty($this->arguments['uuid'])) {
            throw new \InvalidArgumentException('Missing UUID in argument list.');
        }
        $this->xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $this->xml .= '<dcr:locate-by-uuid xmlns:dcr="http://www.day.com/jcr/webdav/1.0" xmlns:D="DAV:">';
        $this->xml .= sprintf('<D:href xmlns:D="DAV:">%s</D:href>', $this->arguments['uuid']);
        $this->xml .= '</dcr:locate-by-uuid>';
    }
}

<?php

namespace Jackalope\Transport\Davex;

use Jackalope\TestCase;

use DOMDocument;
use DOMXPath;

class RequestTest extends TestCase
{
    protected function getCurlFixture($fixture = null, $httpCode = 200, $errno = null)
    {
        $curl =  $this->getMock('Jackalope\Transport\curl', array('exec', 'getinfo', 'errno', 'setopt'));

        if ($fixture) {
            if (is_file($fixture)) {
                $fixture = file_get_contents($fixture);
            }
            $curl
                ->expects($this->any())
                ->method('exec')
                ->will($this->returnValue($fixture));

            $curl
                ->expects($this->any())
                ->method('getinfo')
                ->with($this->equalTo(CURLINFO_HTTP_CODE))
                ->will($this->returnValue($httpCode));
        }

        if (null !== $errno) {
            $curl
                ->expects($this->any())
                ->method('errno')
                ->will($this->returnValue($errno));
        }
        return $curl;
    }

    public function getRequest($fixture = null, $httpCode = 200, $errno = null)
    {
        return new RequestMock($this->getCurlFixture($fixture, $httpCode, $errno), 'GET', 'http://foo/');
    }

    public function testExecuteDom()
    {
        $request = $this->getMock('Jackalope\Transport\Davex\Request', array('execute'), array(null,null,null));
        $request->expects($this->once())
            ->method('execute')
            ->will($this->returnValue('<xml/>'));

        $this->assertTrue($request->executeDom() instanceof DOMDocument);
    }

    /**
     * @covers \Jackalope\Transport\Davex\Request::execute
     */
    public function testPrepareRequestWithCredentials()
    {
        $request = $this->getRequest('fixtures/empty.xml');
        $request->setCredentials(new \PHPCR\SimpleCredentials('foo', 'bar'));
        $request->getCurl()->expects($this->at(0))
            ->method('setopt')
            ->with(CURLOPT_USERPWD, 'foo:bar');
        $request->execute();
    }
}

class RequestMock extends Request
{
    public function getCurl()
    {
        return $this->curl;
    }
}

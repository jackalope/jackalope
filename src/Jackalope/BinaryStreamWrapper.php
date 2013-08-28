<?php
namespace Jackalope;

use LogicException;

/**
 * This class implements a stream wrapper that allows for lazy loaded binary
 * properties.
 *
 * The stream is registered for the protocol "jackalope://". The URL must
 * contain the sessions registryKey as the host part and the oath of the binary
 * property as the path part, e.g. "jackalope://abc0123/content/node/binary"
 *
 * For multivalued properties the url also contains the position of the stream
 * in the property array in the port field and a token to identify all streams
 * loaded by the single backend call in an static array as username.
 *
 * The loading from the backend is deferred until the stream is accessed. Then
 * it is loaded and all stream functions are passed on to the underlying
 * stream. This means after closing the Session, streams can no longer be
 * accessed.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @private
 */
class BinaryStreamWrapper
{
    /**
     * Cached streams for multivalue binary properties - there is no way to fetch only one stream of a multivalue property.
     *
     * @var array
     */
    private static $multiValueMap = array();

    /**
     * The backend path this stream represents
     *
     * @var string
     */
    private $path = null;

    /**
     * The stream once the wrapper has been accessed once.
     * @var stream
     */
    private $stream = null;

    /**
     * The PHPCR session to fetch data through it.
     * @var \PHPCR\SessionInterface
     */
    private $session = null;

    /**
     * Get the information and store it for later usage.
     *
     * @param string $path        the backend path for this stream
     * @param int    $mode        ignored
     * @param int    $options     ignored
     * @param mixed  $opened_path ignored
     *
     * @return bool true on success
     */
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->path = $path;

        return true;
    }

    /**
     * Make sure the stream is ready and read from the underlying stream.
     *
     * @param int $count How many bytes to read from the stream.
     *
     * @return string data from the stream in utf-8 format.
     */
    public function stream_read($count)
    {
        $this->init_stream();

        return fread($this->stream, $count);
    }

    /**
     * Make sure the stream is ready and write to the underlying stream.
     *
     * @param string $data the data to write to the stream (utf-8)
     */
    public function stream_write($data)
    {
        $this->init_stream();

        return fwrite($this->stream, $data);
    }

    /**
     * Make sure the stream is ready and specify the position in the stream.
     */
    public function stream_tell()
    {
        $this->init_stream();

        return ftell($this->stream);
    }

    /**
     * Make sure the stream is ready and check whether the stream is at its end.
     *
     * @return bool true if the stream has ended.
     */
    public function stream_eof()
    {
        $this->init_stream();

        return feof($this->stream);
    }

    /**
     * Make sure the stream is ready and get information about the stream.
     */
    public function stream_stat()
    {
        $this->init_stream();

        return fstat($this->stream);
    }

    /**
     * Retrieve information about a file
     *
     * @param string $path  The backend path for this stream, e.g.
     *                      jackalope://abc0123/content/node/binary
     * @param int    $flags ignored
     *
     * @return array Should return as many elements as stat() does. Unknown or
     *               unavailable values should be set to a rational value
     *               (usually 0).
     *
     * @see http://php.net/manual/en/streamwrapper.url-stat.php
     */
    public function url_stat($path, $flags)
    {
        $this->path = $path;

        return $this->stream_stat();
    }

    /**
     * Make sure the stream is ready and position the file pointer to the
     * specified position.
     *
     * @param int $offset the position in the stream in bytes from the beginning
     * @param int $whence whether to seek relative or absolute
     */
    public function stream_seek($offset, $whence)
    {
        $this->init_stream();

        return fseek($this->stream, $offset, $whence);
    }

    /**
     * Close this stream if it was initialized
     */
    public function stream_close()
    {
        if ($this->stream) {
            fclose($this->stream);
        }
    }

    /**
     * Flush all data written to this stream if the stream was initialized.
     */
    public function stream_flush()
    {
        if ($this->stream) {
            return fflush($this->stream);
        }

        return false;
    }

    /**
     * Check whether stream was already loaded, otherwise fetch from backend
     * and cache it.
     *
     * Multivalued properties have a special handling since the backend returns
     * all streams in a single call.
     *
     * Always checks if the current session is still alive.
     *
     * @throws LogicException when trying to use a stream from a closed session
     *      and on trying to access a nonexisting multivalue id.
     */
    private function init_stream()
    {
        if (null === $this->stream) {
            if ($this->session && !$this->session->isLive()) {
                throw new LogicException('Trying to read a stream from a closed transport.');
            }

            $url = parse_url($this->path);
            $this->session = Session::getSessionFromRegistry($url['host']);
            if (! $this->session) {
                throw new LogicException('Trying to read a stream from a closed transport');
            }
            $property_path = $url['path'];
            $token = isset($url['user']) ? $url['user'] : null;
            if (null === $token) {
                $this->stream = $this->session->getObjectManager()->getBinaryStream($property_path);
            } else {
                // check if streams have been loaded for multivalued properties
                if (!isset(self::$multiValueMap[$token])) {
                    self::$multiValueMap[$token] = $this->session->getObjectManager()->getBinaryStream($property_path);
                }
                $index = isset($url['port']) ? $url['port'] - 1 : 0;
                if (!isset(self::$multiValueMap[$token][$index])) {
                    throw new LogicException("Trying to read a stream from a non existent token '$token' or token index '$index'.");
                }
                $this->stream = self::$multiValueMap[$token][$index];
            }
        }
    }
}

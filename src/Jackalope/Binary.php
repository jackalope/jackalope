<?php
namespace Jackalope;

/**
 * A Binary object holds a PHPCR property value of type BINARY.
 *
 * @api
 */
class Binary implements \PHPCR\BinaryInterface
{
    protected $content;
    protected $pointer;
    protected $size;

    public function __construct($objectManager, $path, $size)
    {
        // TODO turn this into a stream-wrapper for potentially saving time
        $this->content = $objectManager->getBinaryProperty($path);
        $this->pointer = 0;
        $this->size = $size;
    }

    /**
     * Returns a stream representation of this value.
     *
     * Each call to getStream() returns a new stream.
     * The API consumer is responsible for calling close() on the returned
     * stream.
     *
     * @return resource A stream representation of this value.
     * @throws \BadMethodCallException if dispose() has already been called on this Binary
     * @throws \PHPCR\RepositoryException if an error occurs.
     * @api
     */
    public function getStream()
    {
        throw new \Jackalope\NotImplementedException('This is not yet possible');
    }

    /**
     * Reads successive bytes from the specified position in this Binary into
     * the passed string until $limit or the end of the Binary is encountered
     * (whichever comes first).
     *
     * @param integer $bytes how many bytes to read, unlimited by default
     * @return string bytes
     * @throws \RuntimeException if an I/O error occurs.
     * @throws \InvalidArgumentException if offset is negative.
     * @throws \BadMethodCallException if dispose() has already been called on this Binary
     * @throws \PHPCR\RepositoryException if another error occurs.
     * @api
     */
    public function read($bytes)
    {
        $read = substr($this->content, $this->pointer, $bytes);
        $this->pointer += $bytes;
        return $read;
    }

    /**
     * Returns the size of this Binary value in bytes.
     *
     * @return integer the size of this value in bytes.
     * @throws \BadMethodCallException if dispose() has already been called on this Binary
     * @throws \PHPCR\RepositoryException if an error occurs.
     * @api
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Releases all resources associated with this Binary object and informs the
     * repository that these resources may now be reclaimed.
     *
     * An application should call this method when it is finished with the
     * Binary object.
     *
     * @return void
     * @api
     */
    public function dispose()
    {
        $this->content = $this->pointer = $this->size = null;
    }

    /**
     * Returns the entire binary data
     *
     * @return string
     */
    public function __toString()
    {
        return $this->content;
    }
}

<?php

namespace App\Helper;

/**
 * Class FileDescriptorReader.
 */
class FileDescriptorReader
{
    /**
     * @param string $inputFd
     *
     * @return bool|string
     */
    private static function readFileDescriptor(string $inputFd)
    {
        $fileHandler = fopen($inputFd, 'r');
        return stream_get_contents($fileHandler);
    }

    /**
     * @return bool|string
     */
    public function readFd3()
    {
        return $this->readFileDescriptor('php://fd/3');
    }

    /**
     * @return bool|string
     */
    public function readStdin()
    {
        return $this->readFileDescriptor('php://fd/0');
    }
}

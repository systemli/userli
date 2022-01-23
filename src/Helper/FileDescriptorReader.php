<?php

namespace App\Helper;

/**
 * Class FileDescriptorReader.
 */
class FileDescriptorReader
{
    /**
     * @return bool|string
     */
    private static function readFileDescriptor(string $inputFd)
    {
        $fileHandler = fopen($inputFd, 'rb');

        return stream_get_contents($fileHandler);
    }

    /**
     * @return bool|string
     */
    public function readFd3()
    {
        return self::readFileDescriptor('php://fd/3');
    }

    /**
     * @return bool|string
     */
    public function readStdin()
    {
        return self::readFileDescriptor('php://fd/0');
    }
}

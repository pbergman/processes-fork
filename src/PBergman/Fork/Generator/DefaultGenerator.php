<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace PBergman\Fork\Generator;

use Opis\Closure\SerializableClosure;

/**
 * Class DefaultGenerator
 *
 * @package PBergman\Fork\Generator
 */
class DefaultGenerator implements GeneratorInterface
{
    const P_TYPE_BOOL = 1;
    const P_TYPE_INT = 2;
    const P_TYPE_DOUBLE = 3;
    const P_TYPE_NULL = 4;
    const P_TYPE_STRING = 5;
    const P_TYPE_ARRAY = 6;
    const P_TYPE_OBJECT = 7;

    /**
     * will convert given data to a string
     *
     * @param   mixed $data
     * @param   bool $compress
     * @return  string
     */
    static function pack($data, $compress = true)
    {
        $header = [
            'compressed' => false,
            'serialized' => false,
            'is_closure' => false,
            'type'       => self::P_TYPE_BOOL,
        ];
        $types = [
            'boolean' => self::P_TYPE_BOOL,
            'integer' => self::P_TYPE_INT,
            'double' => self::P_TYPE_DOUBLE,
            'string' => self::P_TYPE_STRING,
            'null' => self::P_TYPE_NULL,
        ];
        switch (strtolower(gettype($data))) {
            case 'boolean';
            case 'integer';
            case 'double';
            case 'null':
                $header['type'] = $types[strtolower(gettype($data))];
                break;
            case 'string';
                if (empty($data)) {
                    $header['type'] = self::P_TYPE_NULL;
                } else {
                    if ($compress && function_exists('gzdeflate')) {
                        $data = gzdeflate($data, 9);
                        $header['compressed'] = true;
                    }
                    $header['type'] = self::P_TYPE_STRING;
                }
                break;
            case 'array':
                $data = serialize($data);
                $header['serialized'] = true;
                if ($compress && function_exists('gzdeflate')) {
                    $data = gzdeflate($data, 9);
                    $header['compressed'] = true;
                }
                $header['type'] = self::P_TYPE_ARRAY;
                break;
            case 'object':
                if ($data instanceof \Closure) {
                    $data  = new SerializableClosure($data, true);
                    $header['is_closure'] = true;
                }
                $data = serialize($data);
                $header['serialized'] = true;
                if ($compress && function_exists('gzdeflate')) {
                    $data = gzdeflate($data, 9);
                    $header['compressed'] = true;
                }
                $header['type'] = self::P_TYPE_OBJECT;
                break;
            default:
                throw new \RuntimeException(sprintf('Unsupported type ("%s") of data for packing ', gettype($data)));

        }
        $header = pack(
            'C4',
            (int) $header['compressed'],
            (int) $header['serialized'],
            (int) $header['is_closure'],
            (int) $header['type']
        );

        $max = pow(2, 16) - 1; // max length 16 bit unsigned
        $parts = [];
        foreach (str_split($data, $max) as $part) {
            $length = strlen($part);
            $parts[] = pack(sprintf('SSA%s', $length), $length, self::crc16($part), $part);
        }
        return $header . implode('', $parts);
    }

    /**
     * will convert back given data to original data
     *
     * @param   mixed $data
     * @return  mixed
     */
    static function unpack($data)
    {
        $return = null;
        if (false ==  $handler = fopen('php://temp', 'w+')) {
            throw new \RuntimeException('Could not create a tmp (php://temp) resource handler to process data');
        }
        fwrite($handler, $data);
        rewind($handler);
        $header = fread($handler, 4);
        $header = unpack('Ccompressed/Cserialized/Cis_closure/Ctype', $header);
        while (false === feof($handler)) {
            $chunkHeader  = fread($handler, 4);
            $chunkHeader  = unpack('Ssize/Scrc', $chunkHeader);
            if ($chunkHeader['size']) {
                $data = fread($handler, $chunkHeader['size']);
                $crc = self::crc16($data);
                if ($crc !== $chunkHeader['crc']) {
                    throw new \RuntimeException(sprintf('CRC check failed, data is incomplete or corrupt (0x%04x != 0x%04x)', $crc, $chunkHeader['crc']));
                }
                $return .= $data;
            }
        }
        if (is_resource($header)) {
            fclose($header);
        }
        if ($header['compressed']) {
            $return  = gzinflate($return );
        }
        if ($header['serialized']) {
            $return  = unserialize($return );
        }
        if ($header['is_closure']) {
            /** @var SerializableClosure $return  */
            $return  = $return ->getClosure();
        }
        switch ($header['type']) {
            case self::P_TYPE_BOOL:
                $return = (bool) $return;
                break;
            case self::P_TYPE_INT:
                $return = (int) $return;
                break;
            case self::P_TYPE_DOUBLE:
                $return = (double) $return;
                break;
            case self::P_TYPE_NULL:
                $return = null;
                break;
        }
        return $return;
    }

    /**
     * generate a 16 bit crc from given data
     *
     * @param   $data
     * @return  int
     */
    static function crc16($data)
    {
        $crc = 0xffff;
        for ($i = 0; $i < strlen($data); $i++) {
            $x = (($crc >> 8) ^ ord($data[$i])) & 0xff;
            $x ^= $x >> 4;
            $crc = (($crc << 8) ^ ($x << 12) ^ ($x << 5) ^ $x) & 0xffff;
        }
        return $crc;
    }


}
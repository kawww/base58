<?php

/*
 * This file is part of the Base58 package
 *
 * Copyright (c) 2017-2018 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/base58
 *
 */

namespace Tuupola\Base58;

use InvalidArgumentException;
use Tuupola\Base58;

abstract class BaseEncoder
{
    private $options = [
        "characters" => Base58::GMP,
    ];

    public function __construct($options = [])
    {
        $this->options = array_merge($this->options, (array) $options);

        $uniques = count_chars($this->options["characters"], 3);
        if (58 !== strlen($uniques) || 58 !== strlen($this->options["characters"])) {
            throw new InvalidArgumentException("Character set must contain 58 unique characters");
        }
    }

    public function encode($data, $integer = false)
    {
        if (is_integer($data) || true === $integer) {
            $data = [$data];
        } else {
            $data = str_split($data);
            $data = array_map("ord", $data);
        }

        $leadingZeroes = 0;
        while (!empty($data) && 0 === $data[0]) {
            $leadingZeroes++;
            array_shift($data);
        }

        $converted = $this->baseConvert($data, 256, 58);

        if (0 < $leadingZeroes) {
            $converted = array_merge(
                array_fill(0, $leadingZeroes, 0),
                $converted
            );
        }

        return implode("", array_map(function ($index) {
            return $this->options["characters"][$index];
        }, $converted));
    }

    public function decode($data, $integer = false)
    {
        // If the data contains characters that aren't in the character set
        if (strlen($data) !== strspn($data, $this->options["characters"])) {
            throw new InvalidArgumentException("Data contains invalid characters");
        }

        $data = str_split($data);
        $data = array_map(function ($character) {
            return strpos($this->options["characters"], $character);
        }, $data);

        $leadingZeroes = 0;
        while (!empty($data) && 0 === $data[0]) {
            $leadingZeroes++;
            array_shift($data);
        }

        /* Return as integer when requested. */
        if ($integer) {
            $converted = $this->baseConvert($data, 58, 10);
            return (integer) implode("", $converted);
        }

        $converted = $this->baseConvert($data, 58, 256);

        if (0 < $leadingZeroes) {
            $converted = array_merge(
                array_fill(0, $leadingZeroes, 0),
                $converted
            );
        }

        return implode("", array_map("chr", $converted));
    }

    public function encodeInteger($data)
    {
        return $this->encode($data, true);
    }

    public function decodeInteger($data)
    {
        return $this->decode($data, true);
    }

    abstract public function baseConvert(array $source, $source_base, $target_base);
}

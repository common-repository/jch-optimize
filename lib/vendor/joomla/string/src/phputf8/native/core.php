<?php

namespace _JchOptimizeVendor;

/**
* @package utf8
*/
/**
* Define UTF8_CORE as required
*/
if (!\defined('UTF8_CORE')) {
    \define('UTF8_CORE', \TRUE);
}
//--------------------------------------------------------------------
/**
* Unicode aware replacement for strlen(). Returns the number
* of characters in the string (not the number of bytes), replacing
* multibyte characters with a single byte equivalent
* utf8_decode() converts characters that are not in ISO-8859-1
* to '?', which, for the purpose of counting, is alright - It's
* much faster than iconv_strlen
* Note: this function does not count bad UTF-8 bytes in the string
* - these are simply ignored
* @author <chernyshevsky at hotmail dot com>
* @link   http://www.php.net/manual/en/function.strlen.php
* @link   http://www.php.net/manual/en/function.utf8-decode.php
* @param string UTF-8 string
* @return int number of UTF-8 characters in string
* @package utf8
*/
function utf8_strlen($str)
{
    return \strlen(\utf8_decode($str));
}
//--------------------------------------------------------------------
/**
* UTF-8 aware alternative to strpos
* Find position of first occurrence of a string
* Note: This will get alot slower if offset is used
* Note: requires utf8_strlen amd utf8_substr to be loaded
* @param string haystack
* @param string needle (you should validate this with utf8_is_valid)
* @param integer offset in characters (from left)
* @return mixed integer position or FALSE on failure
* @see http://www.php.net/strpos
* @see utf8_strlen
* @see utf8_substr
* @package utf8
*/
function utf8_strpos($str, $needle, $offset = NULL)
{
    if (\is_null($offset)) {
        $ar = \explode($needle, $str, 2);
        if (\count($ar) > 1) {
            return utf8_strlen($ar[0]);
        }
        return \FALSE;
    } else {
        if (!\is_int($offset)) {
            \trigger_error('utf8_strpos: Offset must be an integer', \E_USER_ERROR);
            return \FALSE;
        }
        $str = utf8_substr($str, $offset);
        if (\FALSE !== ($pos = utf8_strpos($str, $needle))) {
            return $pos + $offset;
        }
        return \FALSE;
    }
}
//--------------------------------------------------------------------
/**
* UTF-8 aware alternative to strrpos
* Find position of last occurrence of a char in a string
* Note: This will get alot slower if offset is used
* Note: requires utf8_substr and utf8_strlen to be loaded
* @param string haystack
* @param string needle (you should validate this with utf8_is_valid)
* @param integer (optional) offset (from left)
* @return mixed integer position or FALSE on failure
* @see http://www.php.net/strrpos
* @see utf8_substr
* @see utf8_strlen
* @package utf8
*/
function utf8_strrpos($str, $needle, $offset = NULL)
{
    if (\is_null($offset)) {
        $ar = \explode($needle, $str);
        if (\count($ar) > 1) {
            // Pop off the end of the string where the last match was made
            \array_pop($ar);
            $str = \join($needle, $ar);
            return utf8_strlen($str);
        }
        return \FALSE;
    } else {
        if (!\is_int($offset)) {
            \trigger_error('utf8_strrpos expects parameter 3 to be long', \E_USER_WARNING);
            return \FALSE;
        }
        $str = utf8_substr($str, $offset);
        if (\FALSE !== ($pos = utf8_strrpos($str, $needle))) {
            return $pos + $offset;
        }
        return \FALSE;
    }
}
//--------------------------------------------------------------------
/**
* UTF-8 aware alternative to substr
* Return part of a string given character offset (and optionally length)
*
* Note arguments: comparied to substr - if offset or length are
* not integers, this version will not complain but rather massages them
* into an integer.
*
* Note on returned values: substr documentation states false can be
* returned in some cases (e.g. offset > string length)
* mb_substr never returns false, it will return an empty string instead.
* This adopts the mb_substr approach
*
* Note on implementation: PCRE only supports repetitions of less than
* 65536, in order to accept up to MAXINT values for offset and length,
* we'll repeat a group of 65535 characters when needed.
*
* Note on implementation: calculating the number of characters in the
* string is a relatively expensive operation, so we only carry it out when
* necessary. It isn't necessary for +ve offsets and no specified length
*
* @author Chris Smith<chris@jalakai.co.uk>
* @param string
* @param integer number of UTF-8 characters offset (from left)
* @param integer (optional) length in UTF-8 characters from offset
* @return mixed string or FALSE if failure
* @package utf8
*/
function utf8_substr($str, $offset, $length = NULL)
{
    // generates E_NOTICE
    // for PHP4 objects, but not PHP5 objects
    $str = (string) $str;
    $offset = (int) $offset;
    if (!\is_null($length)) {
        $length = (int) $length;
    }
    // handle trivial cases
    if ($length === 0) {
        return '';
    }
    if ($offset < 0 && $length < 0 && $length < $offset) {
        return '';
    }
    // normalise negative offsets (we could use a tail
    // anchored pattern, but they are horribly slow!)
    if ($offset < 0) {
        // see notes
        $strlen = \strlen(\utf8_decode($str));
        $offset = $strlen + $offset;
        if ($offset < 0) {
            $offset = 0;
        }
    }
    $Op = '';
    $Lp = '';
    // establish a pattern for offset, a
    // non-captured group equal in length to offset
    if ($offset > 0) {
        $Ox = (int) ($offset / 65535);
        $Oy = $offset % 65535;
        if ($Ox) {
            $Op = '(?:.{65535}){' . $Ox . '}';
        }
        $Op = '^(?:' . $Op . '.{' . $Oy . '})';
    } else {
        // offset == 0; just anchor the pattern
        $Op = '^';
    }
    // establish a pattern for length
    if (\is_null($length)) {
        // the rest of the string
        $Lp = '(.*)$';
    } else {
        if (!isset($strlen)) {
            // see notes
            $strlen = \strlen(\utf8_decode($str));
        }
        // another trivial case
        if ($offset > $strlen) {
            return '';
        }
        if ($length > 0) {
            // reduce any length that would
            // go passed the end of the string
            $length = \min($strlen - $offset, $length);
            $Lx = (int) ($length / 65535);
            $Ly = $length % 65535;
            // negative length requires a captured group
            // of length characters
            if ($Lx) {
                $Lp = '(?:.{65535}){' . $Lx . '}';
            }
            $Lp = '(' . $Lp . '.{' . $Ly . '})';
        } else {
            if ($length < 0) {
                if ($length < $offset - $strlen) {
                    return '';
                }
                $Lx = (int) (-$length / 65535);
                $Ly = -$length % 65535;
                // negative length requires ... capture everything
                // except a group of  -length characters
                // anchored at the tail-end of the string
                if ($Lx) {
                    $Lp = '(?:.{65535}){' . $Lx . '}';
                }
                $Lp = '(.*)(?:' . $Lp . '.{' . $Ly . '})$';
            }
        }
    }
    if (!\preg_match('#' . $Op . $Lp . '#us', $str, $match)) {
        return '';
    }
    return $match[1];
}
//---------------------------------------------------------------
/**
* UTF-8 aware alternative to strtolower
* Make a string lowercase
* Note: The concept of a characters "case" only exists is some alphabets
* such as Latin, Greek, Cyrillic, Armenian and archaic Georgian - it does
* not exist in the Chinese alphabet, for example. See Unicode Standard
* Annex #21: Case Mappings
* Note: requires utf8_to_unicode and utf8_from_unicode
* @author Andreas Gohr <andi@splitbrain.org>
* @param string
* @return mixed either string in lowercase or FALSE is UTF-8 invalid
* @see http://www.php.net/strtolower
* @see utf8_to_unicode
* @see utf8_from_unicode
* @see http://www.unicode.org/reports/tr21/tr21-5.html
* @see http://dev.splitbrain.org/view/darcs/dokuwiki/inc/utf8.php
* @package utf8
*/
function utf8_strtolower($string)
{
    static $UTF8_UPPER_TO_LOWER = NULL;
    if (\is_null($UTF8_UPPER_TO_LOWER)) {
        $UTF8_UPPER_TO_LOWER = array(0x41 => 0x61, 0x3a6 => 0x3c6, 0x162 => 0x163, 0xc5 => 0xe5, 0x42 => 0x62, 0x139 => 0x13a, 0xc1 => 0xe1, 0x141 => 0x142, 0x38e => 0x3cd, 0x100 => 0x101, 0x490 => 0x491, 0x394 => 0x3b4, 0x15a => 0x15b, 0x44 => 0x64, 0x393 => 0x3b3, 0xd4 => 0xf4, 0x42a => 0x44a, 0x419 => 0x439, 0x112 => 0x113, 0x41c => 0x43c, 0x15e => 0x15f, 0x143 => 0x144, 0xce => 0xee, 0x40e => 0x45e, 0x42f => 0x44f, 0x39a => 0x3ba, 0x154 => 0x155, 0x49 => 0x69, 0x53 => 0x73, 0x1e1e => 0x1e1f, 0x134 => 0x135, 0x427 => 0x447, 0x3a0 => 0x3c0, 0x418 => 0x438, 0xd3 => 0xf3, 0x420 => 0x440, 0x404 => 0x454, 0x415 => 0x435, 0x429 => 0x449, 0x14a => 0x14b, 0x411 => 0x431, 0x409 => 0x459, 0x1e02 => 0x1e03, 0xd6 => 0xf6, 0xd9 => 0xf9, 0x4e => 0x6e, 0x401 => 0x451, 0x3a4 => 0x3c4, 0x423 => 0x443, 0x15c => 0x15d, 0x403 => 0x453, 0x3a8 => 0x3c8, 0x158 => 0x159, 0x47 => 0x67, 0xc4 => 0xe4, 0x386 => 0x3ac, 0x389 => 0x3ae, 0x166 => 0x167, 0x39e => 0x3be, 0x164 => 0x165, 0x116 => 0x117, 0x108 => 0x109, 0x56 => 0x76, 0xde => 0xfe, 0x156 => 0x157, 0xda => 0xfa, 0x1e60 => 0x1e61, 0x1e82 => 0x1e83, 0xc2 => 0xe2, 0x118 => 0x119, 0x145 => 0x146, 0x50 => 0x70, 0x150 => 0x151, 0x42e => 0x44e, 0x128 => 0x129, 0x3a7 => 0x3c7, 0x13d => 0x13e, 0x422 => 0x442, 0x5a => 0x7a, 0x428 => 0x448, 0x3a1 => 0x3c1, 0x1e80 => 0x1e81, 0x16c => 0x16d, 0xd5 => 0xf5, 0x55 => 0x75, 0x176 => 0x177, 0xdc => 0xfc, 0x1e56 => 0x1e57, 0x3a3 => 0x3c3, 0x41a => 0x43a, 0x4d => 0x6d, 0x16a => 0x16b, 0x170 => 0x171, 0x424 => 0x444, 0xcc => 0xec, 0x168 => 0x169, 0x39f => 0x3bf, 0x4b => 0x6b, 0xd2 => 0xf2, 0xc0 => 0xe0, 0x414 => 0x434, 0x3a9 => 0x3c9, 0x1e6a => 0x1e6b, 0xc3 => 0xe3, 0x42d => 0x44d, 0x416 => 0x436, 0x1a0 => 0x1a1, 0x10c => 0x10d, 0x11c => 0x11d, 0xd0 => 0xf0, 0x13b => 0x13c, 0x40f => 0x45f, 0x40a => 0x45a, 0xc8 => 0xe8, 0x3a5 => 0x3c5, 0x46 => 0x66, 0xdd => 0xfd, 0x43 => 0x63, 0x21a => 0x21b, 0xca => 0xea, 0x399 => 0x3b9, 0x179 => 0x17a, 0xcf => 0xef, 0x1af => 0x1b0, 0x45 => 0x65, 0x39b => 0x3bb, 0x398 => 0x3b8, 0x39c => 0x3bc, 0x40c => 0x45c, 0x41f => 0x43f, 0x42c => 0x44c, 0xde => 0xfe, 0xd0 => 0xf0, 0x1ef2 => 0x1ef3, 0x48 => 0x68, 0xcb => 0xeb, 0x110 => 0x111, 0x413 => 0x433, 0x12e => 0x12f, 0xc6 => 0xe6, 0x58 => 0x78, 0x160 => 0x161, 0x16e => 0x16f, 0x391 => 0x3b1, 0x407 => 0x457, 0x172 => 0x173, 0x178 => 0xff, 0x4f => 0x6f, 0x41b => 0x43b, 0x395 => 0x3b5, 0x425 => 0x445, 0x120 => 0x121, 0x17d => 0x17e, 0x17b => 0x17c, 0x396 => 0x3b6, 0x392 => 0x3b2, 0x388 => 0x3ad, 0x1e84 => 0x1e85, 0x174 => 0x175, 0x51 => 0x71, 0x417 => 0x437, 0x1e0a => 0x1e0b, 0x147 => 0x148, 0x104 => 0x105, 0x408 => 0x458, 0x14c => 0x14d, 0xcd => 0xed, 0x59 => 0x79, 0x10a => 0x10b, 0x38f => 0x3ce, 0x52 => 0x72, 0x410 => 0x430, 0x405 => 0x455, 0x402 => 0x452, 0x126 => 0x127, 0x136 => 0x137, 0x12a => 0x12b, 0x38a => 0x3af, 0x42b => 0x44b, 0x4c => 0x6c, 0x397 => 0x3b7, 0x124 => 0x125, 0x218 => 0x219, 0xdb => 0xfb, 0x11e => 0x11f, 0x41e => 0x43e, 0x1e40 => 0x1e41, 0x39d => 0x3bd, 0x106 => 0x107, 0x3ab => 0x3cb, 0x426 => 0x446, 0xde => 0xfe, 0xc7 => 0xe7, 0x3aa => 0x3ca, 0x421 => 0x441, 0x412 => 0x432, 0x10e => 0x10f, 0xd8 => 0xf8, 0x57 => 0x77, 0x11a => 0x11b, 0x54 => 0x74, 0x4a => 0x6a, 0x40b => 0x45b, 0x406 => 0x456, 0x102 => 0x103, 0x39b => 0x3bb, 0xd1 => 0xf1, 0x41d => 0x43d, 0x38c => 0x3cc, 0xc9 => 0xe9, 0xd0 => 0xf0, 0x407 => 0x457, 0x122 => 0x123);
    }
    $uni = utf8_to_unicode($string);
    if (!$uni) {
        return \FALSE;
    }
    $cnt = \count($uni);
    for ($i = 0; $i < $cnt; $i++) {
        if (isset($UTF8_UPPER_TO_LOWER[$uni[$i]])) {
            $uni[$i] = $UTF8_UPPER_TO_LOWER[$uni[$i]];
        }
    }
    return utf8_from_unicode($uni);
}
//---------------------------------------------------------------
/**
* UTF-8 aware alternative to strtoupper
* Make a string uppercase
* Note: The concept of a characters "case" only exists is some alphabets
* such as Latin, Greek, Cyrillic, Armenian and archaic Georgian - it does
* not exist in the Chinese alphabet, for example. See Unicode Standard
* Annex #21: Case Mappings
* Note: requires utf8_to_unicode and utf8_from_unicode
* @author Andreas Gohr <andi@splitbrain.org>
* @param string
* @return mixed either string in lowercase or FALSE is UTF-8 invalid
* @see http://www.php.net/strtoupper
* @see utf8_to_unicode
* @see utf8_from_unicode
* @see http://www.unicode.org/reports/tr21/tr21-5.html
* @see http://dev.splitbrain.org/view/darcs/dokuwiki/inc/utf8.php
* @package utf8
*/
function utf8_strtoupper($string)
{
    static $UTF8_LOWER_TO_UPPER = NULL;
    if (\is_null($UTF8_LOWER_TO_UPPER)) {
        $UTF8_LOWER_TO_UPPER = array(0x61 => 0x41, 0x3c6 => 0x3a6, 0x163 => 0x162, 0xe5 => 0xc5, 0x62 => 0x42, 0x13a => 0x139, 0xe1 => 0xc1, 0x142 => 0x141, 0x3cd => 0x38e, 0x101 => 0x100, 0x491 => 0x490, 0x3b4 => 0x394, 0x15b => 0x15a, 0x64 => 0x44, 0x3b3 => 0x393, 0xf4 => 0xd4, 0x44a => 0x42a, 0x439 => 0x419, 0x113 => 0x112, 0x43c => 0x41c, 0x15f => 0x15e, 0x144 => 0x143, 0xee => 0xce, 0x45e => 0x40e, 0x44f => 0x42f, 0x3ba => 0x39a, 0x155 => 0x154, 0x69 => 0x49, 0x73 => 0x53, 0x1e1f => 0x1e1e, 0x135 => 0x134, 0x447 => 0x427, 0x3c0 => 0x3a0, 0x438 => 0x418, 0xf3 => 0xd3, 0x440 => 0x420, 0x454 => 0x404, 0x435 => 0x415, 0x449 => 0x429, 0x14b => 0x14a, 0x431 => 0x411, 0x459 => 0x409, 0x1e03 => 0x1e02, 0xf6 => 0xd6, 0xf9 => 0xd9, 0x6e => 0x4e, 0x451 => 0x401, 0x3c4 => 0x3a4, 0x443 => 0x423, 0x15d => 0x15c, 0x453 => 0x403, 0x3c8 => 0x3a8, 0x159 => 0x158, 0x67 => 0x47, 0xe4 => 0xc4, 0x3ac => 0x386, 0x3ae => 0x389, 0x167 => 0x166, 0x3be => 0x39e, 0x165 => 0x164, 0x117 => 0x116, 0x109 => 0x108, 0x76 => 0x56, 0xfe => 0xde, 0x157 => 0x156, 0xfa => 0xda, 0x1e61 => 0x1e60, 0x1e83 => 0x1e82, 0xe2 => 0xc2, 0x119 => 0x118, 0x146 => 0x145, 0x70 => 0x50, 0x151 => 0x150, 0x44e => 0x42e, 0x129 => 0x128, 0x3c7 => 0x3a7, 0x13e => 0x13d, 0x442 => 0x422, 0x7a => 0x5a, 0x448 => 0x428, 0x3c1 => 0x3a1, 0x1e81 => 0x1e80, 0x16d => 0x16c, 0xf5 => 0xd5, 0x75 => 0x55, 0x177 => 0x176, 0xfc => 0xdc, 0x1e57 => 0x1e56, 0x3c3 => 0x3a3, 0x43a => 0x41a, 0x6d => 0x4d, 0x16b => 0x16a, 0x171 => 0x170, 0x444 => 0x424, 0xec => 0xcc, 0x169 => 0x168, 0x3bf => 0x39f, 0x6b => 0x4b, 0xf2 => 0xd2, 0xe0 => 0xc0, 0x434 => 0x414, 0x3c9 => 0x3a9, 0x1e6b => 0x1e6a, 0xe3 => 0xc3, 0x44d => 0x42d, 0x436 => 0x416, 0x1a1 => 0x1a0, 0x10d => 0x10c, 0x11d => 0x11c, 0xf0 => 0xd0, 0x13c => 0x13b, 0x45f => 0x40f, 0x45a => 0x40a, 0xe8 => 0xc8, 0x3c5 => 0x3a5, 0x66 => 0x46, 0xfd => 0xdd, 0x63 => 0x43, 0x21b => 0x21a, 0xea => 0xca, 0x3b9 => 0x399, 0x17a => 0x179, 0xef => 0xcf, 0x1b0 => 0x1af, 0x65 => 0x45, 0x3bb => 0x39b, 0x3b8 => 0x398, 0x3bc => 0x39c, 0x45c => 0x40c, 0x43f => 0x41f, 0x44c => 0x42c, 0xfe => 0xde, 0xf0 => 0xd0, 0x1ef3 => 0x1ef2, 0x68 => 0x48, 0xeb => 0xcb, 0x111 => 0x110, 0x433 => 0x413, 0x12f => 0x12e, 0xe6 => 0xc6, 0x78 => 0x58, 0x161 => 0x160, 0x16f => 0x16e, 0x3b1 => 0x391, 0x457 => 0x407, 0x173 => 0x172, 0xff => 0x178, 0x6f => 0x4f, 0x43b => 0x41b, 0x3b5 => 0x395, 0x445 => 0x425, 0x121 => 0x120, 0x17e => 0x17d, 0x17c => 0x17b, 0x3b6 => 0x396, 0x3b2 => 0x392, 0x3ad => 0x388, 0x1e85 => 0x1e84, 0x175 => 0x174, 0x71 => 0x51, 0x437 => 0x417, 0x1e0b => 0x1e0a, 0x148 => 0x147, 0x105 => 0x104, 0x458 => 0x408, 0x14d => 0x14c, 0xed => 0xcd, 0x79 => 0x59, 0x10b => 0x10a, 0x3ce => 0x38f, 0x72 => 0x52, 0x430 => 0x410, 0x455 => 0x405, 0x452 => 0x402, 0x127 => 0x126, 0x137 => 0x136, 0x12b => 0x12a, 0x3af => 0x38a, 0x44b => 0x42b, 0x6c => 0x4c, 0x3b7 => 0x397, 0x125 => 0x124, 0x219 => 0x218, 0xfb => 0xdb, 0x11f => 0x11e, 0x43e => 0x41e, 0x1e41 => 0x1e40, 0x3bd => 0x39d, 0x107 => 0x106, 0x3cb => 0x3ab, 0x446 => 0x426, 0xfe => 0xde, 0xe7 => 0xc7, 0x3ca => 0x3aa, 0x441 => 0x421, 0x432 => 0x412, 0x10f => 0x10e, 0xf8 => 0xd8, 0x77 => 0x57, 0x11b => 0x11a, 0x74 => 0x54, 0x6a => 0x4a, 0x45b => 0x40b, 0x456 => 0x406, 0x103 => 0x102, 0x3bb => 0x39b, 0xf1 => 0xd1, 0x43d => 0x41d, 0x3cc => 0x38c, 0xe9 => 0xc9, 0xf0 => 0xd0, 0x457 => 0x407, 0x123 => 0x122);
    }
    $uni = utf8_to_unicode($string);
    if (!$uni) {
        return \FALSE;
    }
    $cnt = \count($uni);
    for ($i = 0; $i < $cnt; $i++) {
        if (isset($UTF8_LOWER_TO_UPPER[$uni[$i]])) {
            $uni[$i] = $UTF8_LOWER_TO_UPPER[$uni[$i]];
        }
    }
    return utf8_from_unicode($uni);
}

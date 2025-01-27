<?php

/**
 * Part of the Joomla Framework Filter Package
 *
 * @copyright  Copyright (C) 2005 - 2021 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */
namespace _JchOptimizeVendor\Joomla\Filter;

use _JchOptimizeVendor\Joomla\Language\Language;
use _JchOptimizeVendor\Joomla\Language\Transliterate;
use _JchOptimizeVendor\Joomla\String\StringHelper;
/**
 * OutputFilter is a class for processing an output string for "safe" display
 *
 * @since  1.0
 */
class OutputFilter
{
    /**
     * Language instance for making a string URL safe
     *
     * @var    Language|null
     * @since  2.0.0
     */
    private static $language;
    /**
     * Makes an object safe to display in forms.
     *
     * Object parameters that are non-string, array, object or start with underscore will be converted
     *
     * @param   object   $mixed        An object to be parsed
     * @param   integer  $quoteStyle   The optional quote style for the htmlspecialchars function
     * @param   mixed    $excludeKeys  An optional string single field name or array of field names not to be parsed (eg, for a textarea)
     *
     * @return  void
     *
     * @since   1.0
     */
    public static function objectHtmlSafe(&$mixed, $quoteStyle = \ENT_QUOTES, $excludeKeys = '')
    {
        if (\is_null($quoteStyle)) {
            $quoteStyle = \ENT_QUOTES;
        }
        if (\is_object($mixed)) {
            foreach (\get_object_vars($mixed) as $k => $v) {
                if (\is_array($v) || \is_object($v) || $v == null || \substr($k, 1, 1) == '_') {
                    continue;
                }
                if (\is_string($excludeKeys) && $k == $excludeKeys) {
                    continue;
                }
                if (\is_array($excludeKeys) && \in_array($k, $excludeKeys)) {
                    continue;
                }
                $mixed->{$k} = \htmlspecialchars($v, $quoteStyle, 'UTF-8');
            }
        }
    }
    /**
     * Makes a string safe for XHTML output by escaping ampersands in links.
     *
     * @param   string  $input  String to process
     *
     * @return  string  Processed string
     *
     * @since   1.0
     */
    public static function linkXhtmlSafe($input)
    {
        $regex = 'href="([^"]*(&(amp;){0})[^"]*)*?"';
        return \preg_replace_callback("#{$regex}#i", function ($m) {
            return \preg_replace('#&(?!amp;)#', '&amp;', $m[0]);
        }, $input);
    }
    /**
     * Processes a string and escapes it for use in JavaScript
     *
     * @param   string  $string  String to process
     *
     * @return  string  Processed text
     *
     * @since   3.0
     */
    public static function stringJSSafe($string)
    {
        $chars = \preg_split('//u', $string, -1, \PREG_SPLIT_NO_EMPTY);
        $newStr = '';
        foreach ($chars as $chr) {
            $code = \str_pad(\dechex(StringHelper::ord($chr)), 4, '0', \STR_PAD_LEFT);
            if (\strlen($code) < 5) {
                $newStr .= '\\u' . $code;
            } else {
                $newStr .= '\\u{' . $code . '}';
            }
        }
        return $newStr;
    }
    /**
     * Generates a URL safe version of the specified string with language transliteration.
     *
     * This method processes a string and replaces all accented UTF-8 characters by unaccented
     * ASCII-7 "equivalents"; whitespaces are replaced by hyphens and the string is lowercased.
     *
     * @param   string  $string    String to process
     * @param   string  $language  Language to transliterate to
     *
     * @return  string  Processed string
     *
     * @since   1.0
     */
    public static function stringUrlSafe($string, $language = '')
    {
        // Remove any '-' from the string since they will be used as concatenaters
        $str = \str_replace('-', ' ', $string);
        if (self::$language) {
            /*
             * Transliterate on the language requested (fallback to current language if not specified)
             *
             * 1) If the language is empty, is an asterisk (used in the CMS for "All"), or the language matches, use the active Language instance
             * 2) If the language does not match the active Language instance, build a new one to get the right transliterator
             */
            if (empty($language) || $language === '*' || self::$language->getLanguage() === $language) {
                $str = self::$language->transliterate($str);
            } else {
                $str = (new Language(self::$language->getBasePath(), $language, self::$language->getDebug()))->transliterate($str);
            }
        } else {
            // Fallback behavior based on the Language package's en-GB LocaliseInterface implementation
            $str = StringHelper::strtolower((new Transliterate())->utf8_latin_to_ascii($string));
        }
        // Trim white spaces at beginning and end of alias and make lowercase
        $str = \trim(StringHelper::strtolower($str));
        // Remove any duplicate whitespace, and ensure all characters are alphanumeric
        $str = \preg_replace('/(\\s|[^A-Za-z0-9\\-])+/', '-', $str);
        // Trim dashes at beginning and end of alias
        $str = \trim($str, '-');
        return $str;
    }
    /**
     * Generates a URL safe version of the specified string with unicode character replacement.
     *
     * @param   string  $string  String to process
     *
     * @return  string  Processed string
     *
     * @since   1.0
     */
    public static function stringUrlUnicodeSlug($string)
    {
        // Replace double byte whitespaces by single byte (East Asian languages)
        $str = \preg_replace('/\\xE3\\x80\\x80/', ' ', $string);
        // Remove any '-' from the string as they will be used as concatenator.
        // Would be great to let the spaces in but only Firefox is friendly with this
        $str = \str_replace('-', ' ', $str);
        // Replace forbidden characters by whitespaces
        $str = \preg_replace('#[:\\#\\*"@+=;!><&\\.%()\\]\\/\'\\\\|\\[]#', " ", $str);
        // Delete all '?'
        $str = \str_replace('?', '', $str);
        // Trim white spaces at beginning and end of alias and make lowercase
        $str = \trim(StringHelper::strtolower($str));
        // Remove any duplicate whitespace and replace whitespaces by hyphens
        $str = \preg_replace('#\\x20+#', '-', $str);
        return $str;
    }
    /**
     * Makes a string safe for XHTML output by escaping ampersands.
     *
     * @param   string  $text  Text to process
     *
     * @return  string  Processed string.
     *
     * @since   1.0
     */
    public static function ampReplace($text)
    {
        return \preg_replace('/(?<!&)&(?!&|#|[\\w]+;)/', '&amp;', $text);
    }
    /**
     * Cleans text of all formatting and scripting code.
     *
     * @param   string  $text  Text to clean
     *
     * @return  string  Cleaned text.
     *
     * @since   1.0
     */
    public static function cleanText(&$text)
    {
        $text = \preg_replace("'<script[^>]*>.*?</script>'si", '', $text);
        $text = \preg_replace('/<a\\s+.*?href="([^"]+)"[^>]*>([^<]+)<\\/a>/is', '\\2 (\\1)', $text);
        $text = \preg_replace('/<!--.+?-->/', '', $text);
        $text = \preg_replace('/{.+?}/', '', $text);
        $text = \preg_replace('/&nbsp;/', ' ', $text);
        $text = \preg_replace('/&amp;/', ' ', $text);
        $text = \preg_replace('/&quot;/', ' ', $text);
        $text = \strip_tags($text);
        $text = \htmlspecialchars($text, \ENT_COMPAT, 'UTF-8');
        return $text;
    }
    /**
     * Set a Language instance for use
     *
     * @param   Language  $language  The Language instance to use.
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public static function setLanguage(Language $language) : void
    {
        self::$language = $language;
    }
    /**
     * Strips `<img>` tags from a string.
     *
     * @param   string  $string  Sting to be cleaned.
     *
     * @return  string  Cleaned string
     *
     * @since   1.0
     */
    public static function stripImages($string)
    {
        return \preg_replace('#(<[/]?img.*>)#U', '', $string);
    }
    /**
     * Strips `<iframe>` tags from a string.
     *
     * @param   string  $string  Sting to be cleaned.
     *
     * @return  string  Cleaned string
     *
     * @since   1.0
     */
    public static function stripIframes($string)
    {
        return \preg_replace('#(<[/]?iframe.*>)#U', '', $string);
    }
}

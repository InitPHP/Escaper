<?php
/**
 * Escaper.php
 *
 * This file is part of InitPHP.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 InitPHP
 * @license    http://initphp.github.io/license.txt  MIT
 * @version    1.0
 * @link       https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);

namespace InitPHP\Escaper;

use \Exception;

use function strtolower;
use function in_array;
use function htmlspecialchars;
use function ctype_digit;
use function preg_replace_callback;
use function rawurlencode;
use function ord;
use function strlen;
use function bin2hex;
use function hexdec;
use function sprintf;
use function strtoupper;
use function substr;
use function preg_match;
use function function_exists;

class Escaper
{
    protected static array $htmlNamedEntityMap = [
        34      => 'quot',
        38      => 'amp',
        60      => 'lt',
        62      => 'gt',
    ];

    protected string $encoding = 'utf-8';

    protected int $htmlSpecialCharsFlags;

    /** @var callable */
    protected $htmlAttrMatcher;

    /** @var callable */
    protected $jsMatcher;

    /** @var callable */
    protected $cssMatcher;

    protected array $supportedEncodings = [
        'iso-8859-1',
        'iso8859-1',
        'iso-8859-5',
        'iso8859-5',
        'iso-8859-15',
        'iso8859-15',
        'utf-8',
        'cp866',
        'ibm866',
        '866',
        'cp1251',
        'windows-1251',
        'win-1251',
        '1251',
        'cp1252',
        'windows-1252',
        '1252',
        'koi8-r',
        'koi8-ru',
        'koi8r',
        'big5',
        '950',
        'gb2312',
        '936',
        'big5-hkscs',
        'shift_jis',
        'sjis',
        'sjis-win',
        'cp932',
        '932',
        'euc-jp',
        'eucjp',
        'eucjp-win',
        'macroman',
    ];

    public function __construct(?string $encoding = null)
    {
        if($encoding !== null && $encoding !== ''){
            $encoding = strtolower($encoding);
            if(!in_array($encoding, $this->supportedEncodings)){
                throw new Exception('Encoding type not supported.');
            }
            $this->encoding = $encoding;
        }
        $this->htmlSpecialCharsFlags = \ENT_QUOTES | \ENT_SUBSTITUTE;
        $this->htmlAttrMatcher = [$this, 'htmlAttrMatcher'];
        $this->jsMatcher = [$this, 'jsMatcher'];
        $this->cssMatcher = [$this, 'cssMatcher'];
    }

    public function getEncoding(): string
    {
        return $this->encoding;
    }

    public function escHtml(string $string): string
    {
        return htmlspecialchars($string, $this->htmlSpecialCharsFlags, $this->encoding);
    }

    public function escHtmlAttr(string $str): string
    {
        $str = $this->toUtf8($str);
        if($str === '' || ctype_digit($str)){
            return $str;
        }
        $res = preg_replace_callback('/[^a-z0-9,\.\-_]/iSu', $this->htmlAttrMatcher, $str);
        return $this->fromUTF8($res);
    }

    public function escJs(string $str): string
    {
        $str = $this->toUtf8($str);
        if($str === '' || ctype_digit($str)){
            return $str;
        }
        $res = preg_replace_callback('/[^a-z0-9,\._]/iSu', $this->jsMatcher, $str);
        return $this->fromUTF8($res);
    }

    public function escUrl(string $str): string
    {
        return rawurlencode($str);
    }

    public function escCss(string $str): string
    {
        $str = $this->toUtf8($str);
        if($str === '' || ctype_digit($str)){
            return $str;
        }
        $res = preg_replace_callback('/[^a-z0-9]/iSu', $this->cssMatcher, $str);
        return $this->fromUTF8($res);
    }

    protected function htmlAttrMatcher($matches): string
    {
        $chr = $matches[0];
        $ord = ord($chr);
        if(($ord <= 0x1f && $chr !== "\t" && $chr !== "\n" && $chr !== "\r") || ($ord >= 0x7f && $ord <= 0x9f)){
            return '&#xFFFD;';
        }
        if(strlen($chr) > 1){
            $chr = $this->convertEncoding($chr, 'UTF-32BE', 'UTF-8');
        }
        $hex = bin2hex($chr);
        $ord = hexdec($hex);
        if(isset(static::$htmlNamedEntityMap[$ord])){
            return '&' . static::$htmlNamedEntityMap[$ord] . ';';
        }
        if($ord > 255){
            return sprintf('&#x%04X;', $ord);
        }
        return sprintf('&#x%02X;', $ord);
    }

    protected function jsMatcher($matches): string
    {
        $chr = $matches[0];
        if(strlen($chr) === 1){
            return sprintf('\\x%02X', ord($chr));
        }
        $chr = $this->convertEncoding($chr, 'UTF-16BE', 'UTF-8');
        $hex = strtoupper(bin2hex($chr));
        if(strlen($hex) <= 4){
            return sprintf('\\u%04s', $hex);
        }
        $highSurrogate = substr($hex, 0, 4);
        $lowSurrogate = substr($hex, 4, 4);
        return sprintf('\\u%04s\\u%04s', $highSurrogate, $lowSurrogate);
    }

    protected function cssMatcher($matches): string
    {
        $chr = $matches[0];
        if(strlen($chr) === 1){
            $ord = ord($chr);
        }else{
            $chr = $this->convertEncoding($chr, 'UTF-32BE', 'UTF-8');
            $ord = hexdec(bin2hex($chr));
        }
        return sprintf('\\%X ', $ord);
    }

    protected function toUtf8($string)
    {
        if($this->encoding === 'utf-8'){
            $res = $string;
        }else{
            $res = $this->convertEncoding($string, 'UTF-8', $this->encoding);
        }
        if(!$this->isUTF8($res)){
            throw new Exception(sprintf('String to be escaped was not valid UTF-8 or could not be converted: %s', $res));
        }
        return $res;
    }

    protected function isUTF8($str): bool
    {
        return $str === '' || preg_match('/^./su', $str);
    }

    protected function convertEncoding($str, $to, $from)
    {
        if(function_exists('iconv')){
            $res = \iconv($from, $to, $str);
        }elseif(function_exists('mb_convert_encoding')){
            $res = \mb_convert_encoding($str, $to, $from);
        }else{
            throw new Exception('The MB_String plugin is required.');
        }
        if($res === FALSE){
            return '';
        }
        return $res;
    }

    protected function fromUTF8($str)
    {
        if($this->encoding === 'utf-8'){
            return $str;
        }
        return $this->convertEncoding($str, $this->encoding, 'UTF-8');
    }

}

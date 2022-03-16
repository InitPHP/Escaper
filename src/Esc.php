<?php
/**
 * Esc.php
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

use function is_array;
use function is_string;
use function strtolower;
use function in_array;
use function ucfirst;

class Esc
{

    /**
     * @param array|string $data
     * @param string $context <p> html, js, css, url, attr </p>
     * @param string|null $encoding
     * @return array|string
     * @throws Exception
     */
    public static function esc($data, string $context = 'html', ?string $encoding = null)
    {
        if(is_array($data)){
            foreach ($data as &$value) {
                $value = self::esc($value, $context);
            }
        }
        if(is_string($data)){
            $context = strtolower($context);
            if(empty($context) || $context === 'raw'){
                return $data;
            }
            if(in_array($context, ['html', 'js', 'css', 'url', 'attr'], true) === FALSE){
                throw new Exception('Invalid escape context provided.');
            }
            $method = $context === 'attr' ? 'escHtmlAttr' : 'esc' . ucfirst($context);

            static $esc;
            if(!$esc){
                $esc = new \InitPHP\Escaper\Escaper($encoding);
            }
            if($esc && $esc->getEncoding() !== $encoding){
                $esc = new \InitPHP\Escaper\Escaper($encoding);
            }
            $data = $esc->{$method}($data);
        }
        return $data;
    }

}

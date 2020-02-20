<?php
namespace Twogether\LaravelURLSigner;

class KeyFormatter
{
    public static function fromString($string,$is_private)
    {
        if(substr($string,0,12) !== '-----BEGIN P') {
            $string = trim($string);
            $string = wordwrap($string,64,"\n",true);
            $pp = $is_private ? "PRIVATE" : "PUBLIC";
            $string = "-----BEGIN {$pp} KEY-----\n".$string;
            $string .= "\n-----END {$pp} KEY-----";
        }

        return $string;
    }
}
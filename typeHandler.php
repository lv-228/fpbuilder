<?php

namespace BuildSql;

require_once 'builderSqlExceptions.php';

class typeHandler {

    private $exceptions;

    private $handlers = [
        '?' => 'anyArgHandler',
        '?d' => 'intArgHandler',
        '?f' => 'floatArgHandler',
        '?a' => 'arrayArgHandler',
        '?#' => 'arrayArgHandler',
    ];

    private $allowed_types = [
        'string',
        'integer',
        'double',
        'boolean',
        'NULL',
        'array',
    ];

    public function __construct() {
        $this->exceptions = new builderSqlExceptions();
    }

    public function getSpecs() {
        $result = [];
        foreach($this->handlers as $key => $value) {
            $result[] = $key;
        }
        return $result;
    }

    public function checkHandler(string $handler) {
        return !empty($this->handlers[$handler]);
    }

    public function getBySpec($spec, $value) {
        if(!empty($this->handlers[$spec])) {
            $function = $this->handlers[$spec];
            return $this->$function($value, $spec);
        }
        return false;
    }

    private function anyArgHandler($value, $spec) {
        $type = gettype($value);
        if (!in_array($type, $this->allowed_types)) {
            $this->exceptions->getError('arg_error');
        }
        if($type === 'array') {
            $this->exceptions->getError('arg_array_error');
        }
        if($type === 'boolean') {
            $value = (int)$value;
            return [$value];
        }
        if($type === 'string') {
            return ["'" . $this->getEscapeString($value) . "'"];
        }
        return [$value];
    }

    private function intArgHandler($value, $spec) {
        $type = gettype($value);
        if ($type !== 'integer' && $value !== '@null' && $type !== 'boolean') {
            $this->exceptions->getError('arg_int_error');
        }
        return [$value];
    }

    private function floatArgHandler($value, $spec) {
        if (gettype($value) !== 'double') {
            $this->exceptions->getError('arg_float_error');
        }
        return [$value];
    }

    private function arrayArgHandler($value, $spec) {
        $type = gettype($value);
        if ($type !== 'array' && $type !== 'string' && $type !== 'double' && $type !== 'integer') {
            $this->exceptions->getError('arg_array_error');
        }
        if($spec === '?#') {
            if(is_array($value)) {
                return $this->execArgArray($value, '`');
            }
            else {
                return ['`' . $this->getEscapeString($value) . '`'];
            }
        }
        if($spec === '?a') {
            if(is_array($value)) {
                return $this->execArgArray($value, '`');
            }
            else {
                return ['`' . $this->getEscapeString($value) . '`'];
            }
        }
    }

    private function execArgArray(array $values, string $quote) {
        $result = [];
        foreach ($values as $key_elem => $elem) {
            if($elem === null) {
                if(is_string($key_elem)) {
                    $result[] = $quote .  $this->getEscapeString($key_elem) . $quote . ' = @null';
                    continue;
                }
                $result[] = '@null';
                continue;
            }
            else if(is_array($elem)) {
                $this->exceptions->getError('array_error');
            }
            else if(gettype($key_elem) === 'string') {
                $result[] = $quote . $this->getEscapeString($key_elem)  . $quote . ' = ' . "'" . $this->getEscapeString($elem) . "'";
            } 
            else if(gettype($elem) === 'string')
                $result[] = $quote .  $this->getEscapeString($elem) . $quote;
            else if(gettype($elem) === 'boolean')
                $result[] = (int)$elem;
            else {
                $result[] = $elem;
            }
            
        }
        return $result;
    }

    private function getEscapeString(string $string) {
        $result = preg_replace('~[\x00\x0A\x0D\x1A\x22\x27\x5C]~u', '\\\$0', $string);
        // if($this->like_condition) {
        //     $result = $this->getEscapeSqlStringWithLikePhrase($result);
        // }
        return $result;
    }

    //Если SQL содержит оператор LIKE и возможные маски (%,_) их нужно дополнительно экранировать
    private function getEscapeSqlStringWithLikePhrase(string $string) {
        return preg_replace('~[\x25\x5F]~u', '\\\-$0', $string);
    }

}
<?php

namespace BuildSql;

require_once 'builderSqlExceptions.php';
require_once 'typeHandler.php';

class builderSql{

    public $like_condition = false;

    private $exceptions;

    private $typeHandler;

    public function __construct() {
        $this->exceptions = new builderSqlExceptions();
        $this->typeHandler = new typeHandler();
    }

    public function start(string $query, array $args = []): string {
        $validate = $this->validateConditions($query);
        $with_specs = $this->setAllSpec($query, $args);
        $with_specs = empty($with_specs) ? $query : $with_specs;
        $after_delete = $this->deleteAllConditions($with_specs);
        $after_delete = empty($after_delete) ? $with_specs : $after_delete;
        $after_null_replace = preg_replace('[@null]', 'NULL', $after_delete);
        return rtrim($after_null_replace);
    }

    private function setSpec(string $query, $arg): string|bool {
        $spec = stripos($query, '?');
        if(!$spec) {
            return false;
        }
        $specs = $this->typeHandler->getSpecs();
        $spec_second_symbol = !empty($query[$spec + 1]) ? $query[$spec + 1] : '';
        $full_spec = '?' . $spec_second_symbol;
        if(!in_array($full_spec, $specs)) {
            $full_spec = '?';
        }
        $result_str = '';
        if($handler = $this->typeHandler->getBySpec($full_spec, $arg)) {
            $set_specs = $handler;
            $result_str .= $result_str . implode(', ', $handler);
        }
        $start_query = mb_substr($query, 0, $spec);
        $result = $start_query . $result_str . mb_substr($query, $spec + strlen($full_spec), strlen($query) - $spec);
        return $result;
    }

    private function setAllSpec(string $query, array $args) {
        $result = [];
        $worker = $query;
        $counter = 0;
        while($worker = $this->setSpec($worker, $args[$counter] ?? '@null')) {
            $counter++;
            $result = $worker;
        }
        return $result;
    }

    private function deleteAllConditions(string $query) {
        $result = [];
        $worker = $query;
        while($worker = $this->deleteCondition($worker)) {
            $result = $worker;
        }
        return $result;
    }

    private function deleteCondition(string $query) {
        $condition_start = stripos($query, '{');
        $condition_stop = stripos($query, '}');
        if(!$condition_start) {
            return false;
        }
        $result = '';
        $condition_str = mb_substr($query, $condition_start + 1, $condition_stop - $condition_start - 1);
        if(stripos($condition_str, '@null') !== false) {
            $result_start = mb_substr($query, 0, $condition_start);
            $result_end = mb_substr($query, $condition_stop + 1, strlen($query) - $condition_stop - 1);
            $result = $result_start . ' ' . $result_end;
        } else {
            $result_start = mb_substr($query, 0, $condition_start);
            $result_end = !empty($condition_stop + 1) ? mb_substr($query, $condition_stop + 1, strlen($query) - $condition_stop) : '';
            $result = $result_start . $condition_str . $result_end;
        }
        return $result;
    }

    private function validateConditions(string $query) {
        $stack = [];
        for($i = 0; $i < strlen($query); $i++) {
            if($query[$i] === '{') {
                $last_elem = array_pop($stack);
                if($last_elem === '{')
                    $this->exceptions->getError('condition_error');
                array_push($stack, $query[$i]);
            }
            if($query[$i] === '}') {
                if(array_pop($stack) !== '{')
                    $this->exceptions->getError('condition_error');
            }
        }
    }
}
<?php

namespace BuildSql;

class builderSqlExceptions {

    private $messages = [
        'nested' => 'Вложение условного оператора недопустимо',
        'closed' => 'Лишняя закрывающая скобка условного оператора',
        'not_closed' => 'Условный оператор не закрыт',
        'array_error' => 'Вложенные массив аргументов не допускается',
        'arg_error' => 'Передан аргумент неподдерживаемого типа!',
        'arg_array_error' => 'Невозможно привести массив к простому типу',
        'arg_int_error' => 'Выбран неподходящий спецификатор для значения аргумента с типом int',
        'arg_float_error' => 'Выбран неподходящий спецификатор для значения аргумента с типом float',
        'condition_error' => 'Неправильное условное выражение',
    ];

    private $start_error_message = 'Ошибка!';

    public function getError(string $error_type) {
        throw new \Exception($this->start_error_message . ' ' . $this->messages[$error_type]);
    }

}
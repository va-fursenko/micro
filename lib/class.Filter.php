<?php
/**
 * Filter сlass         (PHP 5 >= 5.3.0)
 * Special thanks to:   all, http://www.php.net
 * Copyright (c)        viktor, Belgorod, 2010-2016
 * Email                vinjoy@bk.ru
 * version                2.0.0
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the MIT License (MIT)
 * @see https://opensource.org/licenses/MIT
 */

require_once('class.BaseException.php');


/** Собственное исключение класса */
class FilterException extends BaseException
{
}


/** @todo Сделать по возможности передачу в методы произвольного числа аргументов вместо массива. Хотя, не принципиально */

/**
 * Класс фильтрации параметров
 * @author    Enjoy
 * @version   2.0.0
 * @package   Micr0
 */
class Filter
{
    /**
     * Возвращает массив $arg или массив из всех параметров метода, начиная с [1], к элементам которых применили функцию $func
     * @param callable $func Функция вида mixed function (mixed $el){...}
     * @param mixed $arg Аргумент функции, массив аргументов, или один из нескольких переданных аргументов
     * @return mixed
     */
    public static function map(callable $func, $arg)
    {
        // Функции переданы только коллбэк и один аргумент
        if (func_num_args() == 2) {
            return is_array($arg) ? array_map($func, $arg) : $func($arg);

            // Меньше 2 параметров функция принять не должна, значит у нас их больше 2
        } else {
            // Передаём на обработку все аргументы кроме первого - это коллбэк
            return array_map($func, array_slice(func_get_args(), 1, func_num_args() - 1));
        }
    }


    /**
     * Возвращает массив $arg, к элементам которого рекурсивно применили функцию $func
     * @param callable $func Функция вида mixed function (mixed $el){...}
     * @param mixed $arg Аргумент функции
     * @return mixed
     */
    public static function mapRecursive(callable $func, array $arg)
    {
        foreach ($arg as $key => $value) {
            $arg[$key] = is_array($value)
                ? self::mapRecursive($func, $value)
                : $func($value);
        }
        return $arg;
    }


    /**
     * Применение ко всем элементам массива $arg или всем параметрам метода, начиная с [1], функции $func и логическое сложение && результатов
     * Прерывается при получении первого false в результате выполнения $func
     * @param callable $func Функция вида bool function(mixed $el){...}
     * @param mixed $arg Аргумент функции, массив аргументов, или один из нескольких переданных аргументов
     * @return bool
     */
    public static function mapBool(callable $func, $arg)
    {
        $map = function ($arr) use ($func) {
            $result = true;
            $i = 0;
            while ($result && $i < count($arr)) {
                $result = $result && $func($arr[$i]);
                $i++;
            }
            return $i > 0 && $result; // Для пустого массива стоит вернуть false
        };

        if (func_num_args() == 2) {
            return is_array($arg) ? $map($arg) : $func($arg);

            // Меньше 2 параметров функция принять не должна, значит у нас их больше 2
        } else {
            // Передаём на обработку все аргументы кроме первого - это коллбэк
            return $map($func, array_slice(func_get_args(), 1, func_num_args() - 1));
        }
    }


    /**
     * Проверка первого параметра на принадлежность к типу, указанному во втором
     * @param mixed|array $var Переменная или массив переменных для проверки
     * @param mixed $type Тип данных
     * @return bool
     */
    public static function is($var, $type)
    {
        return self::mapBool(
            function ($el) use ($type) {
                return is_a($el, $type, false);
            },
            $var
        );
    }


    /**
     * Проверка целочисленного числа на попадание в заданный отрезок
     * @param int|array $var Аргумент, или массив аргументов функции
     * @param int $from Начало диапозона допустимых значений
     * @param int $to Конец диапозона допустимых значений
     * @assert (0, 0, 0) == true
     * @assert (0, 0, 1) == true
     * @assert (1, 0, 1) == true
     * @assert (0, -1, 1) == true
     * @assert (-2, -3, -1) == true
     * @assert (2, 1.1, 2.1) == true
     * @assert (1, 0, 2) == true
     * @assert (1, 2, 3) == false
     * @assert (4, 1, 3) == false
     * @assert (-1, -3, -2) == false
     * @assert (1.2, 0, 3) == false
     * @return bool
     */
    public static function isIntegerBetween($var, $from, $to)
    {
        return self::mapBool(
            function ($el) use ($from, $to) {
                return is_int($el) && ($el >= $from) && ($el <= $to);
            },
            $var
        );
    }


    /**
     * Проверка даты на попадание в интервал
     * @param mixed|array $var Аргумент, или массив аргументов функции
     * @param datetime $from Начало диапозона допустимых значений
     * @param datetime $to Конец диапозона допустимых значений
     * @return bool
     */
    public static function isDateBetween($var, $from, $to)
    {
        /** @todo Дописать */
        return 1 / 0;
    }


    /**
     * Проверка одного параметра на строку
     * @param string|array $var Аргумент, или массив аргументов функции
     * @return bool
     */
    public static function isString($var)
    {
        return self::mapBool('is_string', $var);
    }


    /**
     * Проверка одного параметра на массив
     * @param array $var Аргумент, или массив аргументов функции
     * @return bool
     */
    public static function isArray($var)
    {
        return self::mapBool('is_array', $var);
    }


    /**
     * Проверка одного параметра на логическое значение
     * @param bool|array $var Аргумент, или массив аргументов функции
     * @return bool
     */
    public static function isBool($var)
    {
        return self::mapBool('is_bool', $var);
    }


    /**
     * Проверка одного числа на вещественное число
     * @param float|array $var Аргумент, или массив аргументов функции
     * @return bool
     */
    public static function isNumeric($var)
    {
        return self::mapBool('is_numeric', $var);
    }


    /**
     * Проверка одного числа на целочисленность
     * @param int|array $var Аргумент, или массив аргументов функции
     * @return bool
     */
    public static function isInteger($var)
    {
        return self::mapBool('is_int', $var);
    }


    /**
     * Проверка одного числа на натуральность
     * @param int|array $var Аргумент, или массив аргументов функции
     * @return bool
     */
    public static function isNatural($var)
    {
        return self::mapBool(
            function ($el) {
                return is_int($el) && $el >= 0;
            },
            $var
        );
    }


    /**
     * Проверка одного аргумента на правильну дату формата "yyyy-mm-dd"
     * @param datetime|array $var Аргумент, или массив аргументов функции
     * @param string $formatExpr Регулярное выражение для проверки формата даты
     * @return bool
     */
    public static function isDate($var, $formatExpr = '/^(\d{4})\-(\d{2})\-(\d{2})$/')
    {
        return self::mapBool(
            function ($el) use ($formatExpr) {
                return preg_match($formatExpr, $el, $d) && checkdate($d[2], $d[3], $d[1]);
            },
            $var
        );
    }


    /**
     * Проверка одного аргумента на правильну дату и время формата "yy-mm-dd hh:mm:ss"
     * @param datetime|array $var Аргумент, или массив аргументов функции
     * @return bool
     */
    public static function isDatetime($var)
    {
        $func = function ($el) {
            function checktime($hour, $min, $sec)
            {
                $hour = (strlen($hour) < 2 || $hour{0} !== '0') ?: $hour{1};
                if ($hour < 0 || $hour > 23 || !is_int($hour)) {
                    return false;
                }
                $min = (strlen($min) < 2 || $min{0} !== '0') ?: $min{1};
                if ($min < 0 || $min > 59 || !is_int($min)) {
                    return false;
                }
                $sec = (strlen($sec) < 2 || $sec{0} !== '0') ?: $sec{1};
                if ($sec < 0 || $sec > 59 || !is_int($sec)) {
                    return false;
                }
                return true;
            }

            return preg_match('/^(\d{4})\-(\d{2})\-(\d{2}) ([0-1][0-9]|[2][0-3]):([0-5][0-9]):([0-5][0-9])$/', $el, $d)
            && checkdate($d[2], $d[3], $d[1])
            && checktime($d[4], $d[5], $d[6]);
        };
        return self::mapBool($func, $var);
    }


    /**
     * Получение русской даты со склоняемым месяцем. Например, 1 января 2016
     * @param string $format Формат даты для функции strftime()
     * @param int $timestamp Время для форматирования. Текущее, если не указано
     * @return string
     * @see http://php.net/manual/ru/function.strftime.php
     */
    public static function dateRus($format = '%e %bg %Y', $timestamp = null) {
        setlocale(LC_ALL, 'ru_RU.cp1251');
        $months = ['', 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
        $format = str_replace('%bg', $months[date('n', $timestamp)], $format);
        return strftime($format, $timestamp ?: time());
    }


# - - - - - - - - - - - - - - - - - - - - - - - - - - - Функции экранирования - - - - - - - - - - - - - - - - - - - - - - - - - - - - #

    /**
     * Замена html-тегов и спецсимволов их html-сущностями
     * @param string|array $var Обрабатываемая строка или массив строк
     * @param int $flags Способ обработки кавычек, аналогичен второму параметру htmlspecialchars
     * @return string
     */
    public static function htmlEncode($var, $flags = ENT_QUOTES)
    {
        $flags = $flags !== null ?: ENT_COMPAT | ENT_HTML401;
        return self::map(
            function ($el) use ($flags) {
                return htmlspecialchars($el, $flags);
            },
            $var
        );
    }

    /**
     * Замена html-сущностей тегов и спецсимволов их реальными символами
     * @param string|array $var Обрабатываемая строка или массив строк
     * @param int $flags Способ обработки кавычек, аналогичен второму параметру htmlspecialchars_decode
     * @return string
     */
    public function htmlDecode($var, $flags = ENT_QUOTES)
    {
        $flags = $flags !== null ?: ENT_COMPAT | ENT_HTML401;
        return self::map(
            function ($el) use ($flags) {
                return htmlspecialchars_decode($el, $flags);
            },
            $var
        );
    }


    /**
     * Экранирование спесцимволов в стиле языка С
     * @param string|array $var Обрабатываемая строка или массив строк
     * @return mixed
     * @throws FilterException
     */
    public static function slashesAdd($var)
    {
        return self::map(
            function ($el) {
                return addslashes($el);
            },
            $var
        );
    }

    /**
     * Отмена экранирования спесцимволов в стиле языка С
     * @param string|array $var Обрабатываемая строка или массив строк
     * @return mixed
     * @throws FilterException
     */
    public static function slashesStrip($var)
    {
        return self::map(
            function ($el) {
                return stripslashes($el);
            },
            $var
        );
    }





# - - - - - - - - - - - - - - - - - - - - - - - Функции обработки строк и массивов - - - - - - - - - - - - - - - - - - - - - - - - - #

    /**
     * Переиндексация ассоциативного двухмерного массива по указанному индексу в строках
     * @param array $arr Переиндексовываемый массив
     * @param string $index Новый индекс - один из индексов во всех строках массива. Сохраняется первое вхождение всех дублируемых индексов
     * @return array
     */
    public static function arrayReindex($arr, $index)
    {
        $result = [];
        foreach ($arr as $el) {
            if (isset($el[$index]) && !isset($result[$el[$index]])) {
                $result[$el[$index]] = $el;
            }
        }
        return $result;
    }


    /**
     * Выбирает из двухмерного массива множество значений столбца
     * @todo Как-то коряво смотрится
     * @param array $arr Исходный массив
     * @param string $index Индекс столбца
     * @param bool $arrayReindex Флаг, указывающий та то, что индексация результата будет проведена значениями полученного массива
     * @return array
     */
    public static function arrayExtract($arr, $index, $arrayReindex = false)
    {
        $result = [];
        if ($arrayReindex) {
            foreach ($arr as $el) {
                if (isset($el[$index]) && !isset($result[$el[$index]])) {
                    $result[$el[$index]] = $el[$index];
                }
            }
        } else {
            foreach ($arr as $el) {
                if (isset($el[$index]) && (array_search($el[$index], $result) === false)) {
                    $result[] = $el[$index];
                }
            }
        }
        return $result;
    }


    /**
     * Проверяет существование в массиве ключа, или массива ключей
     * @param mixed|array $key Ключ, или массив ключей массива
     * @param array $arr Проверяемый массив
     * @return bool
     */
    public static function arrayKeyExists($key, $arr)
    {
        $func = function ($el) use ($arr) {
            return array_key_exists($el, $arr);
        };
        return is_array($key)
            ? self::mapBool($func, $key)
            : $func($key);
    }


    /**
     * Замена указанной подстроки или указанных подстрок на другую подстроку(подстроки).
     * @param string|array $search Старая подстрока(подстроки)
     * @param string|array $replacement Новая подстрока(подстроки)
     * @param string|array $subject Обрабатываемая строка, или массив строк
     * @return string|array
     */
    public static function strReplace($search, $replacement, $subject)
    {
        $func = function ($el) use ($search, $replacement) {
            return str_replace($search, $replacement, $el);
        };
        return is_array($subject)
            ? $func($subject)
            : self::map($func, $subject);
    }


    /**
     * Ограничивает строку указанной длинной
     * @param string|array $var Обрабатываемая строка, или массив строк
     * @param int $length Длина, до которой сокращается строка
     * @param string $strEnd Окончание укорачиваемой строки
     * @param string $encoding Кодировка
     * @return string
     */
    public static function strTrim($var, $length, $strEnd = '..', $encoding = null)
    {
        $encoding = $encoding !== null ?: mb_internal_encoding();
        $func = function ($el) use ($length, $strEnd, $encoding)
        {
            return mb_strimwidth($el, 0, $length, $strEnd, $encoding);
        };
        return is_array($var)
            ? $func($var)
            : self::map($func, $var);
    }


    /**
     * Получение подстроки $str, заключенной между $sMarker и $fMarker. Регистрозависима
     * @param string $str Строка, в которой ищется подстрока
     * @param string $sMarker Маркер начала
     * @param string $fMarker Маркер конца
     * @param int $initOffset
     * @return string
     * Похоже, что тут вызов от массива строк не нужен
     */
    public static function strBetween($str, $sMarker, $fMarker, $initOffset = 0)
    {
        $result = '';
        $s = strpos($str, $sMarker, $initOffset);
        if ($s !== false) {
            $s += strlen($sMarker);
            $f = strpos($str, $fMarker, $s);
            if ($f !== false) {
                $result = substr($str, $s, $f - $s);
            }
        }
        return $result;
    }


    /**
     * Увеличение строки до $padLength символов
     * @param string|array $var Исходная строка, или массив строк
     * @param int $padLength Длина, до которой будет дополняться исходная строка
     * @param string $padStr Строка, которой будет дополняться исходная строка
     * @param int $direct Направление дополнения - STR_PAD_RIGHT, STR_PAD_LEFT, STR_PAD_BOTH
     * @return string
     * @see http://php.net/manual/ru/function.str-pad.php
     */
    public static function strPad($var, $padLength, $padStr = ' ', $direct = STR_PAD_RIGHT)
    {
        $func = function ($el) use ($padLength, $padStr, $direct) {
            return str_pad($el, $padLength, $padStr, $direct);
        };
        return is_array($var)
            ? $func($var)
            : self::map($func, $var);
    }
}


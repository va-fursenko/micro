<?php
/**
 * Templates explorer сlass (PHP 5 >= 5.0.0)
 * Special thanks to: all, http://www.php.net
 * Copyright (c)    viktor Belgorod, 2009-2016
 * Email		    vinjoy@bk.ru
 * Version		    2.4.0
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the MIT License (MIT)
 * @see https://opensource.org/licenses/MIT
 */

/*
 * При работе в режиме отладки темплейты хранятся в файлах. Возможно расположение несольких темплейтов в одном файле
 * Фрагменты html-кода заключены в именованных блоках, выделяемых тегами [$имя блока] и [/$имя блока]
 * Стиль(если есть) указывается в квадратных скобках после объявления начала и конца блока [$имя блока][$стиль] и [/$имя блока][$стиль] 
 * Языковые константы обозначатся тегами {L_ИМЯ_КОНСТАНТЫ}
 * Прочие фрагменты текста - {имя фрагмента}
 */


/** Собственное исключение класса */
class TplException extends BaseException{

    /**
     * Строковое представление объекта
     * @return string
     */
    public function __toString() {
        $result = [__CLASS__ . ": [{$this->code}]: {$this->message}"];
        $prev = $this;
        while ($prev = $prev->getPrevious()){
            $result[] = $prev->__toString();
        }
        return Log::printObject($result);
    }
}




/**
 * Класс шаблонизатора
 * @author      viktor
 * @version     2.4
 * @package     Micr0
 */
class Tpl{

    # Скрытые свойства класса
    protected $fileName;        # Имя файла с темплейтом для работы в отладочном режиме
    protected $db;              # Класс БД с темплейтами для работы в эксплуатационном режиме
    protected $debugMode;       # Режим работы класса - отладка(true) или эксплуатация(false)
    protected $useDb;           # Источник тесплейтов - БД или файл (bool)
    protected $language;        # Языковой массив для поддержки мультиязычности


    # Свойства для работы с файлами темплейтов
    protected $content;         # Последний считаный файл


    # Языковые константы класса
    const L_TPL_FILE_UNREACHABLE = 'Файл шаблона недоступен';
    const L_TPL_BLOCK_UNKNOWN = 'Шаблон не найден';
    const L_TPL_DB_UNREACHABLE = 'База данных с темплейтами недоступна';



    /**
     * Создание объекта
     * @param string|Db $target Полное имя файла, или дескриптор БД
     * @param bool $useDb Флаг использования БД для чтения шаблонов
     * @param string $language Язык системы
     * @throws TplException
     */
    function __construct($target, $useDb = CONFIG::TPL_USE_DB, $language = CONFIG::TPL_DEFAULT_LANGUAGE) {
        $this->setDebug(CONFIG::TPL_DEBUG);
        $this->setUseDb($useDb);
        $this->content = '';
        if ($this->useDb()){
            $this->setDb($target);
            // Проверка дескриптора на корректность
            if (!method_exists($this->getDb(), 'isConnected') || !$this->getDb()->isConnected()) {
                throw new TplException(self::L_TPL_DB_UNREACHABLE, E_USER_WARNING);
            }
        }else{
            if (($target != '') && (!is_readable($target))) {
                throw new TplException(self::L_TPL_FILE_UNREACHABLE . ' - ' . $target, E_USER_WARNING);
            }
            $this->setFileName($target);
            $this->loadContent($target);
            $this->setDb(null);
        }
        $this->setLanguage($language);
    }



    /**
     * Загрузка содержимого файла
     * @param string $fileName Имя файла для загрузки данных
     * @return bool
     * @throws TplException
     */
    function loadContent($fileName = null) {
        if (!$this->useDb()) {
            if ($fileName != '') {
                if (!is_readable($fileName)) {
                    throw new TplException(self::L_TPL_FILE_UNREACHABLE . ' - ' . $fileName, E_USER_WARNING);
                }
                $this->setFileName($fileName);
            }
            $this->content = file_get_contents($this->getFileName());
            return true;
        }
        return false;
    }



    /** Получение подстроки $str, заключенной между $s_marker и $f_marker */
    private function getStrBetween($str, $sMarker, $fMarker, $initOffset = 0) {
        $result = '';
        $s = stripos($str, $sMarker, $initOffset);
        if ($s !== false) {
            $s += strlen($sMarker);
            $f = stripos($str, $fMarker, $s);
            if ($f !== false)
                $result = substr($str, $s, $f - $s);
        }
        return $result;
    }



    /** Получение из файла или БД заданного темплэйта */
    function getBlock($name, $style = '') {
        $result = '';
        if ($this->useDb()) {
            $st = $style != '' ? " AND `style` = '" . Db::quote($style)."'" : '';
            $result = $this->getDb()->scalarQuery(
                "SELECT `body` FROM " . self::TEMPLATES_DB_TABLE . " WHERE `name` = '" . Db::quote($name) . "'" . $st,
                ''
            );
            if (($result == '') && $this->isDebug()){
                trigger_error(self::L_TPL_BLOCK_UNKNOWN . ' - ' . $name, E_USER_WARNING);
            }
        } else {
            $result = $this->getStrBetween(
                    $this->content, "[\$$name]" . ($style != '' ? "[$style]" : ''), "[/\$$name]" . ($style != '' ? "[$style]" : '')
            );
            if (($result == '') && $this->isDebug()) {
                trigger_error(self::L_TPL_BLOCK_UNKNOWN . ' - ' . $name, E_USER_WARNING);
            }
        }
        return $result;
    }



    /** Заполнение контейнера, заданного строкой */
    private function parseStrBlock($content, $strContainer) {
        //Заменяем языковые константы
        preg_match_all('/\{L_([a-zA-Z_0-9]+)\}/', $strContainer, $arr);
        // Языковые константы
        $langs = Language::getValues($this->getDb(), TPL_DEFAULT_LANGUAGE, $arr[1]);
        foreach ($arr[1] as $name) {            
           $strContainer = str_replace('{L_' . $name . '}', $langs[$name], $strContainer);
        }
        // Прочие параметры		
        foreach ($content as $key => $value) {
            $strContainer = str_replace('{' . $key . '}', $value, $strContainer);
        }
        return $strContainer;
    }



    /** Заполнение контейнера, заданного именем секции */
    function parseBlock($content, $containerName) {
        return $this->parseStrBlock($content, $this->getBlock($containerName));
    }



    /** Обработка файла целиком */
    function parseFile($content, $fileName = null) {
        $fileName = $fileName ? $fileName : $this->fileName;
        if (!is_readable($fileName)) {
            trigger_error(self::L_TPL_FILE_UNREACHABLE . ' - ' . $fileName, E_USER_WARNING);
        }
        return $this->parseStrBlock(
                $content, 
                file_get_contents($fileName === null ? $this->getFileName() : $fileName)
        );
    }



    /** Заполнение одного выбранного блока из некэшированного файла */
    function parseBlockFromFile($content, $fileName, $blockName, $style = '') {
        $result = '';
        if (!is_readable($fileName)) {
            trigger_error(self::L_TPL_FILE_UNREACHABLE . ' - ' . $fileName, E_USER_WARNING);
        }
        $result = $this->parseStrBlock(
                $content, $this->getStrBetween(
                        file_get_contents($fileName), 
                        "[\$$blockName]" . ($style != '' ? "[$style]" : ''), 
                        "[/\$$blockName]" . ($style != '' ? "[$style]" : '')
                )
        );
        return $result;
    }



    # ------------------------------------------- Геттеры ---------------------------------------------------- #

    /** Имя файла темплейта */
    function getFileName() {
        return $this->fileName;
    }

    /** Режим работы */
    function isDebug() {
        return $this->debugMode;
    }

    /** Режим чтения темплейтов - из БД или файла */
    function useDb() {
        return $this->useDb;
    }
    
    /** Язык темплейтов */
    function getLanguage(){
        return $this->language;
    }
    
    /** Дескриптор соединения с БД */
    function getDb(){
        return $this->db;
    }



    # ------------------------------------------- Сеттеры ---------------------------------------------------- #
    /** Устанавливает имя файла */
    function setFileName($fileName) {
        $this->fileName = $fileName;
    }

    /** Устанавливает режим работы */
    function setDebug($debugMode) {
        $this->debugMode = $debugMode;
    }

    /** Устанавливает режим чтения темплейтов - из БД, или файла */
    function setUseDb($useDb) {
        return $this->useDb = $useDb;
    }
        
    /** Устанавливает язык темплейтов */
    function setLanguage($language) {
        return $this->language = $language;
    }
    
    /** Устанавливает дескриптор соединения с БД */
    function setDb($db){
        $this->db = $db;
    }

}



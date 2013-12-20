<?php

namespace Msingi\Db;

use Zend\Stdlib\ArrayObject;

class TableRow
{
    protected $data = array();

    /**
     * @param $name
     * @param $value
     * @return null
     */
    public function __set($name, $value)
    {
//        $class = get_called_class();
//        $definition = TableRow::$definitions[$class];
//
//        if (!isset($definition[$name])) {
//            trigger_error('Undefined property via __set(): ' . $name . ' in ' . $class, E_USER_NOTICE);
//            return null;
//        }
//
        $this->data[$name] = $value;
    }

    /**
     * @param $name
     * @return null
     */
    public function __get($name)
    {
//        $class = get_called_class();
//        $definition = TableRow::$definitions[$class];
//
//        if (!isset($definition[$name])) {
//            trigger_error('Undefined property via __get(): ' . $name . ' in ' . $class, E_USER_NOTICE);
//            return null;
//        }
//
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    /**
     * @param array $data
     */
    public function exchangeArray($data)
    {
        $this->data = $data;
//        $class = get_called_class();
//        $definition = TableRow::$definitions[$class];
//
//        foreach ($definition as $field => $desc) {
//            $this->data[$field] = (isset($data[$field])) ? $data[$field] : null;
//        }
    }

}
<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace OpenApi\Fd\Models;

/**
 * Description of Model
 *
 * @author brahman
 */
class Model {

    public function setIntVal($arr = array(), $name = '', $def = 0) {
        try {
            $this->$name = isset($arr[$name]) ? (int) $arr[$name] : $def;
        } catch (Exception $exc) {
            // echo $exc->getTraceAsString();
        }
    }

    public function setVal($arr = array(), $name = '', $def = '') {
        try {
            $this->$name = isset($arr[$name]) ? $arr[$name] : $def;
        } catch (Exception $exc) {
            // echo $exc->getTraceAsString();
        }
    }
}

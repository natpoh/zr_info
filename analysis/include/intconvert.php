<?php


Class INTCONVERT
{
    public static function str_to_int($data)
    {
        $result=0;

        $array_int_convert = array('W'=>1,'EA'=>2,'H'=>3,'B'=>4,'I'=>5,'M'=>6,'MIX'=>7,'JW'=>8,'NJW'=>9,'IND'=>10);

        if ($array_int_convert[$data])
        {
            $result = $array_int_convert[$data];
        }

        return $result;


    }
    public static function get_array_ints()
    {
        return array(1=>'W',2=>'EA',3=>'H',4=>'B',5=>'I',6=>'M',7=>'MIX',8=>'JW',9=>'NJW',10=>'IND');
    }
    public static function int_to_str($data)
    {
        $result=0;

        $array_int_convert = array(1=>'W',2=>'EA',3=>'H',4=>'B',5=>'I',6=>'M',7=>'MIX',8=>'JW',9=>'NJW',10=>'IND');

        if ($array_int_convert[$data])
        {
            $result = $array_int_convert[$data];
        }

        return $result;


    }


}
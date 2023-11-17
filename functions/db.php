<?php
function is_table_exist($table, $create_table_query='')
{
    $q = db_query("SELECT count((1)) AS ct FROM INFORMATION_SCHEMA.TABLES WHERE table_schema ='".DB_DATABASE."' and table_name='{$table}'");
    $d = db_fetch_array($q);
    $result = false;
    if ($d['ct']>0) 
    {
        $result = true;
    }  
    if($create_table_query)
    {
        db_query($create_table_query);
    }
    return $result;
}
function db_get_fields($queryObject)
{
    $result = [];
    while($field = mysqli_fetch_field($queryObject))
    {
        if(!isset($result[$field->orgtable]))
        {
            $result[$field->orgtable] = [];
        }
        $result[$field->orgtable][] = ['name'=>$field->name, 'original'=>$field->orgname];
    }
    return $result;
}
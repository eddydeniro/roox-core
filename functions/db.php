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
function get_fields_values_string($data)
{
    if(!is_array($data))
    {
        return false;
    }
    $isAssociative = is_string(key($data)) || (is_int(key($data)) && key($data));
    $fields = "";
    if($isAssociative)
    {
        $fields = "(`" . implode("`,`", array_keys($data)) . "`)";
        $values = "('" . implode("','", array_values($data)) . "')";    
    }
    else
    {
        $values_array = [];
        foreach ($data as $key => $value) 
        {
            if(!$fields)
            {
                $fields = "(`" . implode("`,`", array_keys($value)) . "`)";
            }
            $values_array[] = "('" . implode("','", array_values($value)) . "')";            
        }
        $values = implode(",", $values_array);
    }
    return [$fields, $values];
}
function db_insert_update($table, $data, $update_fields = [])
{
    if(!is_array($data))
    {
        return false;
    }
    list($fields, $values) = get_fields_values_string($data);
    $update_pars = "";
    if(count($update_fields))
    {
        $updates = [];
        foreach ($update_fields as $field) 
        {
            $updates[] = "`{$field}`=VALUES(`{$field}`)";
        }
        $update_pars = "ON DUPLICATE KEY UPDATE " . implode(",", $updates);    
    }
    return db_query("INSERT INTO {$table} {$fields} VALUES {$values} {$update_pars}");
}
?>
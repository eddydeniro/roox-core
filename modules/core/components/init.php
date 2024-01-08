<?php
//Special condition for module lingua
if(!in_array('lingua', ${ROOX_PLUGIN . "_modules"}))
{
    $q = db_query("SELECT * FROM `{$dictionary_table}`");
    $roox_dictionary = [];
    if(db_num_rows($q))
    {
        while($d = db_fetch_array($q))
        {
            $roox_dictionary[$d['id']] = ['dict_key'=>$d['dict_key'], 'dict_value'=>$d['dict_value'] ? $d['dict_value'] : $d['dict_key']];
            if(!defined($d['dict_key']))
            {
                define($d['dict_key'], $d['dict_value']);
            }
        }
    }
}
?>
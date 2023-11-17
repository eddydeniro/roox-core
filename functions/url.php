<?php
function redirect_to_ref($refUrl, $withTab = false)
{
    list($m, $pars) = explode("&", base64_decode($refUrl), 2);
    list($x, $url_module) = explode("=", $m);
    $url_pars = $pars;
    if($withTab)
    {
        $url_pars = str_replace('tab', 'active', $pars);
    }
    else
    {
        $new_url = [];
        foreach (explode("&", $pars) as $segment) 
        {
            list($k, $v) = explode("=", $segment);
            if(!in_array($k, ['tab', 'active']))
            {
                $new_url[] = $segment;
            }
        }
        $url_pars = implode("&", $new_url);
    }
    redirect_to($url_module, $url_pars);
}
?>
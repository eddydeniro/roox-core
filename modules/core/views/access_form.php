<?php
    $module = $_GET['name'];
    $form_name = basename(__FILE__, '.php');
    $module_info = db_find(${ROOX_PLUGIN . '_modules_table'}, $module, 'name'); 
    $q = db_query("SELECT id, field_7, field_8 FROM app_entity_1");
    $user_choices = [];
    while($d = db_fetch_array($q))
    {
        $user_choices[$d['id']] = $d['field_7'] . ' ' . $d['field_8'];
    }

    $html_data = [
        'div-1'=>[
            'attr'=>[
                'class'=>'modal-body',
                'content'=>['{{div-2}}']    
            ],
        ],
        'div-2'=>[
            'attr'=>[
                'class'=>'form-body',
                'content'=>['{{formTemplate_1}}']    
            ],
        ],        
        'formTemplate_1'=>[
            'attr'=>[
                'form'=>[
                    'action'=>url_for("$plugin_name/core/"),
                    'name'=>$form_name,
                    'id'=>$form_name
                ]
            ],
            'data'=>[

                ['element'=>'hidden', 'name'=>'action', 'value'=>'save_access'],
                ['element'=>'hidden', 'name'=>'id', 'value'=>$module_info['id']],
                ['element'=>'hidden', 'name'=>'name', 'value'=>$module],
                [
                    'element'=>'multiselect',
                    'name'=>'users_id[]',
                    'value'=>$module_info['users_id'],
                    'label'=>TEXT_USERS,
                    'choices'=>$user_choices      
                ],
                [
                    'element'=>'multiselect',
                    'name'=>'groups_id[]',
                    'value'=>$module_info['groups_id'],
                    'label'=>TEXT_USERS_GROUPS,
                    'choices'=>access_groups::get_choices()
                ]                
            ],
            'opts'=>[
                'withSubmit'=>false
            ]
        ]
    ];
    echo ajax_modal_template_header(TEXT_ACCESS);
    echo $Element->fromData($html_data)->render();
    echo ajax_modal_template_footer('hide-save-button', '<button type="button" id="def-submit-btn" class="btn btn-primary" onclick="$(\'#'.$form_name.'\').submit();">' . TEXT_SAVE . ' </button>');
?>
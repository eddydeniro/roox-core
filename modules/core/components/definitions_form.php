<h5><u><b><?php echo TEXT_DICTIONARY ?></b></u></h5>
<p><?php echo TEXT_DICTIONARY_INFO ?></p>
<?php
    echo form_tag('locale_def_form', $update_url) . button_tag(TEXT_BUTTON_ADD, $add_url, ['class'=>'btn btn-primary'])." ".submit_tag(TEXT_BUTTON_SAVE, ['class'=>'btn btn-danger']);
    echo input_hidden_tag('active', $module_name).input_hidden_tag('action', 'update_def');

?>
<div class="table-scrollable">
<div class="table-scrollable table-wrapper slimScroll" id="slimScroll">
    <table class="tree-table table table-striped table-bordered table-hover">
        <thead>
            <tr>
                <th><?php echo TEXT_KEYWORD ?></th>
                <th style="width:80%"><?php echo TEXT_VALUE ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if(count($roox_dictionary) == 0) echo '<tr><td colspan="2">' . TEXT_NO_RECORDS_FOUND . '</td></tr>'; ?>
            <?php foreach($roox_dictionary as $id=>$values): ?>
                <tr>
                    <td><div style="padding:8px 0;"><?php echo $values['dict_key'] ?></div></td>
                    <td><?php echo input_tag("definitions[{$id}]", $values['dict_value'], array('class' => 'form-control transparent')) ?></td>
                </tr>  
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</div>
</form>
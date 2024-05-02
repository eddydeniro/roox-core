<h5><u><b><?php echo TEXT_DICTIONARY ?></b></u></h5>
<p><?php echo TEXT_DICTIONARY_INFO ?></p>
<?php
    echo form_tag('locale_def_form', $update_url) . button_tag(TEXT_BUTTON_ADD, $add_url, ['class'=>'btn btn-primary'])." ".submit_tag(TEXT_BUTTON_SAVE, ['class'=>'btn btn-danger']);
    echo input_hidden_tag('action', 'update_def');
?>
<p></p>
<div class="table-scrollable table-wrapper slimScroll" id="slimScroll">
    <table class="tree-table table table-striped table-bordered table-hover" id="definition">
        <thead>
            <tr>
                <th><?php echo TEXT_KEYWORD ?></th>
                <th style="width:80%"><?php echo TEXT_VALUE ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if(count($roox_dictionary) == 0) echo '<tr><td colspan="2">' . TEXT_NO_RECORDS_FOUND . '</td></tr>'; ?>
            <?php foreach($roox_dictionary as $id=>$values):
                if(in_array(substr($values['dict_key'], 0, 4), ['CFG_', 'MOD_']))
                {
                    continue;
                } 
            ?>
                <tr>
                    <td><div style="padding:8px 0;"><?php echo $values['dict_key'] ?></div></td>
                    <td><i class="fa fa-edit" onclick="showDialog('<?php echo "definitions_{$id}"; ?>')" style="position:absolute;margin-top:10px;cursor:pointer;"></i><?php echo input_tag("definitions[{$id}]", $values['dict_value'], array('class' => 'form-control transparent', 'style'=>'margin-left:20px;width:100%')) ?></td>
                </tr>  
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</form>
 
<dialog id="favDialog" class="dialog" style="border-radius:6px;border-color:rgba(0,0,0,0.2);">
  <div class="modal-header">
    <button type="button" class="close" onclick="closeDialog()" style="outline:none;"></button>
	<h4 class="modal-title"><?php echo TEXT_EDIT; ?></h4>
  </div>
  <form method="dialog" id="dialogForm">
  <div class="modal-body">
	<div class="form-body">
        <textarea name="def_value" id="def_value"></textarea>
	</div>
  </div>
  <div class="modal-footer">
  	<button class="btn btn-warning" value="cancel" onclick="closeDialog()"><?php echo TEXT_CANCEL; ?></button>
    <button class="btn btn-primary" value="default" onclick="saveDialog()"><?php echo TEXT_SAVE; ?></button>
  </div>
  </form>
</dialog>

<script>
    $(document).ready(function () {
        $('#definition').DataTable();
    });
    function showDialog(id){
        $('#def_value').val($('#'+id).val());
        $('#def_value').data('target', id);
        CKEDITOR.config.baseFloatZIndex = 20000;
        CKEDITOR.replace('def_value', {
            startupFocus: true
        });    
        setTimeout(function(){
            $('#favDialog')[0].showModal();
        }, 500);
    }
    function closeDialog(){
        $('#favDialog')[0].close();
        CKEDITOR.instances['def_value'].destroy(true);        
    }
    function saveDialog(){
        const target = $('#def_value').data('target');
        $('#'+target).val(CKEDITOR.instances['def_value'].getData());
        CKEDITOR.instances['def_value'].destroy(true);        
    }
</script>
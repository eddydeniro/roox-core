<?php
    echo ajax_modal_template_header(TEXT_DICTIONARY);
    echo form_tag('dict_form', $url_form, ['class'=>'form-horizontal']);
    echo input_hidden_tag('action', 'save_def');
?>
<div class="modal-body ajax-modal-width-1100">
        <div class="form-body">

            <div class="form-group">
                <label class="col-md-2 control-label" for="dict_key">
                    <span class="required-label">*</span><?php echo TEXT_KEYWORD ?>
                </label>
                <div class="col-md-10">	
                    <?php
                        echo input_tag('dict_key', '', ['class'=>'form-control input-large is-unique', 'required'=>'required'])
                    ?>
                </div>			
            </div>
            
            <div class="form-group">
                <label class="col-md-2 control-label" for="dict_value">
                    <span class="required-label">*</span><?php echo TEXT_VALUE ?>
                </label>
                <div class="col-md-10">	
                    <?php
                        echo textarea_tag('dict_value', '', ['class'=>'form-control input-large', 'required'=>'required']);
                    ?>
                </div>			
            </div>

        </div>
</div>

<?php
    echo ajax_modal_template_footer('hide-save-button', '<button type="submit" id="def-submit-btn" class="btn btn-primary btn-primary-modal-action">' . TEXT_SAVE . ' </button>');
?>
</form>
<script>
    CKEDITOR.config.baseFloatZIndex = 20000;
    CKEDITOR.replace('dict_value', {
        startupFocus: true,
        toolbar: 'Default'
    });    
    $('#dict_key').on('blur', function(e){
        const val = $(this).val();
        if(!val)
        {
            return;
        }
        if(dict_key_val==val)
        {
            return;
        }
        dict_key_val = val;
        $.ajax({
            url:'<?php echo url_for("{$plugin_name}/core/", "action=check_def"); ?>',
            type:'POST',
            data:{dict_key:val}
        }).done(data=>{
            if(parseInt(data))
            {
                if(!$('#check-success').length)
                {
                    $(this).addClass('valid').removeClass('error');
                    $(this).parent().append("<div id='check-success' class='fa fa-check is-unique-checking-success'></div>");
                    $('#def-submit-btn').removeAttr('disabled');
                }
            } else {
                $('#check-success').remove();
                $(this).addClass('error').removeClass('valid');
                $('#def-submit-btn').attr('disabled', 'disabled');
            }
        })
    });
</script>
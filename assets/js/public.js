jQuery(function($){
    $('.wplm-contact-form').on('submit',function(e){
        e.preventDefault();
        var $form=$(this),$btn=$form.find('.wplm-submit-btn'),$resp=$form.find('.wplm-form-response');
        $btn.prop('disabled',true).text('Sending...');
        $.post(wplm_ajax.url,$form.serialize()+'&action=wplm_submit_lead',function(res){
            $resp.show().removeClass('success error');
            if(res.success){$resp.addClass('success').text(res.data.message);$form[0].reset();}
            else{$resp.addClass('error').text(res.data.message||'Error.');}
            $btn.prop('disabled',false).text('Send Message');
        });
    });
});

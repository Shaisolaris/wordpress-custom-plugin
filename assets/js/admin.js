jQuery(function($){
    $('#wplm-add-note').on('click',function(){
        var note=$('#wplm-new-note').val().trim();
        if(!note)return;
        $.post(wplm.ajax_url,{action:'wplm_add_note',lead_id:$(this).data('lead-id'),note:note,nonce:wplm.nonce},function(res){
            if(res.success){$('#wplm-notes-list').prepend('<div class="wplm-note"><strong>'+res.data.user+'</strong><p>'+res.data.note+'</p></div>');$('#wplm-new-note').val('');}
        });
    });
});

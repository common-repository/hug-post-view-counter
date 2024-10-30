(function($) {
	$( document ).ready(function() {
        // Returns height of browser viewport
        let windowHeight = $( window ).height();
        // Returns height of HTML document
        let documentHieght = $( document ).height();
        if(~~(documentHieght/windowHeight)<=2){
            setTimeout(function(){
                $.post(obj.ajax_url,
                    {
                        _ajax_nonce: obj.nonce,
                        action: 'hug_set_post_view',
                        id: obj.id
                    },
                    function(data) {
                        console.log(data);
                    }
                );
            },20000);
        }
        else{
            $(window).on('scroll',function(){
                if($(this).scrollTop()>(documentHieght*2/3)){
                    $(window).off('scroll');
                    $.post(obj.ajax_url,
                        {
                            _ajax_nonce: obj.nonce,
                            action: 'hug_set_post_view',
                            id: obj.id
                        },
                        function(data) {
                            console.log(data);
                        }
                    );
                }
            });
        }
    });
})( jQuery );
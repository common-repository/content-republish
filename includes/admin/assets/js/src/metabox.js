(function ($) {
    if ( CONTENT_REPUBLISH_POST_STATUS.post_status === 'publish' ) {
        $("#contentrepublish-clone-btn").on("click",function(e) {
            e.preventDefault();
            let $this = $(this);
            $this.attr("disabled", "disabled");
            let nonce = $("#contentrepublish_nonce").val();
            let status = $("#contentrepublish-status");
            $.ajax({
                dataType: "json",
                url: ajaxurl,
                type: "post",
                data: {
                    _wpnonce: nonce,
                    action: "contentrepublish_clone_post",
                    post_id: CONTENT_REPUBLISH_POST_STATUS.post_id
                },
                success: function (response) {
                    if (response.data.message) {
                        status.html(response.data.message)
                    }
                }
            });
        });
    } else {
        $("#contentrepublish-convert-republish-btn").on( "click", function(e) {
            e.preventDefault();
            let $this = $(this);
            $this.attr("disabled", "disabled");
            let parent_id = $("#contentrepublish_post_parent").val();
            let status = $("#contentrepublish-status");
            let nonce = $("#contentrepublish_nonce").val();
            if (isNaN(parent_id)) {
                status.text('Error: Post ID is not a number.');
            } else {
                $.post(
                    ajaxurl,
                    {
                        _wpnonce: nonce,
                        action: "contentrepublish_republish_conversion",
                        post_id: CONTENT_REPUBLISH_POST_STATUS.post_id,
                        parent_id: parent_id
                    },
                    function (response) {
                        if (response.data.message) {
                            status.text(response.data.message)
                        }
                        if (response.success) {
                            $this.removeAttr("disabled")
                        }
                    })
            }
        });
    }
})(jQuery);
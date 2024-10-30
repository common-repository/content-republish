( function( wp ) {
    const {__} = wp.i18n;
    const {subscribe} = wp.data;
    const editor = wp.data.select('core/editor');

    if ( ContentRepublishBlockUI.is_republish ) {
        let notice = [
            {
                url: ContentRepublishBlockUI.parent_url,
                label: __( 'View original', 'content-republish' )
            }
        ];
        if ( ContentRepublishBlockUI.parent_edit_link ) {
            notice.push({
                url: ContentRepublishBlockUI.parent_edit_link,
                label: __('Edit original', 'content-republish')
            })
        }
        // add an admin post notice to notify that this is a republish post.
        wp.data.dispatch('core/notices').createNotice(
            'warning', // Can be one of: success, info, warning, error.
            ContentRepublishBlocki18n.message,
            {
                isDismissible: false, // Whether the user can dismiss the notice.
                // Any actions the user can perform.
                actions: notice
            }
        );

        // change the Publish button text to Republish
        wp.i18n.setLocaleData({
            'Publish': [
                'Republish',
                'content-republish'
            ],
            'Schedule': [
                'Schedule Republish',
                'content-republish'
            ]
        });

        let initialPostStatus = editor.isSavingPost();
        let unsubscribe = subscribe( () => {
            const afterPostStatus = editor.isSavingPost();
            const PostStatus = editor.getEditedPostAttribute( 'status' );
            if (initialPostStatus && !afterPostStatus && ('publish' === PostStatus || 'republish' === PostStatus || 'republish-trash' === PostStatus) ) {
                window.location.replace(ContentRepublishBlockUI.redirect_url);
            } else {
                initialPostStatus = afterPostStatus;
            }
        });
    } else {
        if ( ContentRepublishBlockUI.last_update) {
            wp.data.dispatch('core/notices').createNotice(
                'success', // Can be one of: success, info, warning, error.
                ContentRepublishBlocki18n.message,
            {
                isDismissible: true, // Whether the user can dismiss the notice.

            })
        }
    }

} )( window.wp );
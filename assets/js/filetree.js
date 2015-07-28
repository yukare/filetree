(function ($, Drupal) {
    Drupal.behaviors.filetree = {
        attach: function (context, drupalSettings) {
            // Collapse the sub-folders.
            $('.filetree .files ul').hide();

            // Expand/collapse sub-folder when clicking parent folder.
            $('.filetree .files li:has(ul)', context).click(function (e) {
                // A link was clicked, so don't mess with the folders.
                if ($(e.target).is('a')) {
                    return;
                }
                // If multiple folders are not allowed, collapse non-parent folders.
                if (!$(this).parents('.filetree').hasClass('multi')) {
                    $(this).parents('.files').find('li:has(ul)').not($(this).parents()).not($(this)).removeClass('expanded').find('ul:first').hide('fast');
                }
                // Expand.
                if (!$(this).hasClass('expanded')) {
                    $(this).addClass('expanded').find('ul:first').show('fast');
                }
                // Collapse.
                else {
                    $(this).removeClass('expanded').find('ul:first').hide('fast');
                }
                // Prevent collapsing parent folders.
                return false;
            });

            // Expand/collapse all when clicking controls.
            $('.filetree .controls a').click(function () {
                if ($(this).hasClass('expand')) {
                    $(this).parents('.filetree').find('.files li:has(ul)').addClass('expanded').find('ul:first').show('fast');
                }
                else {
                    $(this).parents('.filetree').find('.files li:has(ul)').removeClass('expanded').find('ul:first').hide('fast');
                }
                return false;
            });
        }

    };

})(jQuery, Drupal);

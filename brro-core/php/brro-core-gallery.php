<?php
//
// ******************************************************************************************************************************************************************** Brro Gallery
//
// Index of Functions
//
// 1. brro_add_custom_gallery_meta_box
//      - Registers a meta box titled 'Afbeeldingen gallerij' to the post editor's sidebar.
// 2. brro_custom_gallery_callback 
//      - Renders the custom gallery meta box content. Enqueues necessary scripts and styles, retrieves stored gallery IDs, and displays the gallery interface.
// 3. brro_save_gallery_meta 
//      - Saves the gallery meta data when the post is saved, ensuring proper permissions and avoiding autosave conflicts.
//
// Custom gallery field

$brro_gallery = get_option('brro_gallery', 0);
if ($brro_gallery == 1) {
    add_action('add_meta_boxes', 'brro_add_custom_gallery_meta_box');
    function brro_add_custom_gallery_meta_box() {
        add_meta_box(
            'brro_custom_gallery',
            'Afbeeldingen gallerij',
            'brro_custom_gallery_callback',
            array_filter(explode(',', get_option('brro_gallery_post_types', 'post')), 'post_type_exists'),
            'side',
            'core'
        );
    }
}
function brro_custom_gallery_callback($post) {
    // Ensure the script and styles for media uploader and jQuery UI are loaded
    wp_enqueue_media();
    wp_enqueue_script('jquery-ui-sortable');

    // Retrieve stored gallery IDs from the post meta
    $gallery_ids = get_post_meta($post->ID, 'brro_image_gallery_ids', true);
    $gallery_ids_array = explode(',', $gallery_ids);
    ?>
    <style>
        #brro-image-list {
            list-style-type: none; /* Remove list styling */
            padding: 0; /* Remove padding */
        }
        #brro-image-list li {
            display: inline-block; /* Display items inline */
            margin-right: 10px; /* Add some space between the images */
            position: relative; /* Position relative for absolute positioning of remove button */
        }
        .brro-remove-image {
            position: absolute; /* Position absolutely within the list item */
            top: -3px; /* Position at the top */
            left: -3px; /* Position at the left */
            background: red; /* Background color for the remove button */
            color: white; /* Text color for the remove button */
            cursor: pointer; /* Pointer cursor on hover */
            padding: 2px 6px; /* Padding for better appearance */
			border-radius:3px;
            font-size: 12px; /* Smaller font size */
        }
        #brro-image-list img {
            height: 140px; /* Larger thumbnail size */
			cursor: grab;
        }
    </style>
    <div id="brro-custom-gallery-container">
        <a href="#" id="brro-upload-gallery-button" class="button" style="margin-top:16px">Kies afbeeldingen</a>
        <ul id="brro-image-list">
            <?php if (!empty($gallery_ids_array)) : ?>
                <?php foreach ($gallery_ids_array as $image_id) : ?>
                    <?php $image_url = wp_get_attachment_url($image_id); ?>
                    <?php if ($image_url) : ?>
                        <li data-id="<?php echo esc_attr($image_id); ?>">
                            <span class="brro-remove-image">-</span>
                            <img src="<?php echo esc_url($image_url); ?>">
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
        <!-- Hidden input to store gallery IDs -->
        <input type="hidden" id="brro_image_gallery_ids" name="brro_image_gallery_ids" value="<?php echo esc_attr($gallery_ids); ?>">
    </div>
    <script>
    jQuery(function($) {
        var image_frame; // Maintain frame instance

        $('#brro-upload-gallery-button').click(function(e) {
            e.preventDefault();

            // Initialize the frame only once
            if (!image_frame) {
                // Define image_frame as wp.media object
                image_frame = wp.media({
                    title: 'Select Images',
                    button: {text: 'Use Images'},
                    multiple: 'add',
                    library: {
                        type: 'image' // Only show images
                    }
                });

                // When the frame's select button is clicked, update the selection
                image_frame.on('select', function() {
                    var selection = image_frame.state().get('selection');
                    var gallery_ids = [];
                    $('#brro-image-list').empty(); // Clear current list
                    selection.each(function(attachment) {
                        gallery_ids.push(attachment.id); // Push attachment id into array
                        $('#brro-image-list').append(
                            '<li data-id="' + attachment.id + '">' +
                            '<span class="brro-remove-image">-</span>' +
                            '<img src="' + attachment.attributes.url + '" />' +
                            '</li>'
                        );
                    });
                    $('#brro_image_gallery_ids').val(gallery_ids.join(",")); // Update hidden input value
                });
            }

            // Open the frame
            image_frame.open();

            // Pre-select images in the open frame based on saved input:
            var ids = $('#brro_image_gallery_ids').val().split(',');
            var currentSelection = image_frame.state().get('selection');
            currentSelection.reset(); // Clear current selection in frame
            // Fetch each attachment by ID and add to the selection in the order they appear in the input
            ids.forEach(function(id) {
                if (id) {
                    var attachment = wp.media.attachment(id);
                    attachment.fetch().then(function() {
                        currentSelection.add([attachment]);
                    });
                }
            });

        });

        // Remove image event
        $('#brro-image-list').on('click', '.brro-remove-image', function() {
            var $li = $(this).closest('li');
            var id = $li.data('id');
            $li.remove(); // Remove the image from the list
            var ids = $('#brro_image_gallery_ids').val().split(',').filter(function(value) {
                return value !== id.toString();
            });
            $('#brro_image_gallery_ids').val(ids.join(',')); // Update the hidden input value
        });

        // Make the list sortable and update hidden input on order change
        $('#brro-image-list').sortable({
            update: function(event, ui) {
                var orderedIDs = $(this).sortable('toArray', { attribute: 'data-id' });
                $('#brro_image_gallery_ids').val(orderedIDs.join(','));
            }
        });
    });
    </script>
    <?php
}
add_action('save_post', 'brro_save_gallery_meta', 10, 2);
function brro_save_gallery_meta($post_id, $post) {
    // Check for nonce here if you add one for security purposes

    // Avoid saving during autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check the user's permissions.
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['brro_image_gallery_ids'])) {
        $images = sanitize_text_field($_POST['brro_image_gallery_ids']);
        update_post_meta($post_id, 'brro_image_gallery_ids', $images);
    }
}


/*
*
add_action("elementor/dynamic_tags/register_tags", function ($dynamic_tags) {
    class Brro_Custom_Gallery_Image_Tag extends \Elementor\Core\DynamicTags\Data_Tag
    {
        public function get_name() {
            return "brro-custom-gallery-image";
        }
    
        public function get_title() {
            return __("Brro Custom Gallery Image", "brro-core");
        }
    
        public function get_group() {
            return [\ElementorPro\Modules\DynamicTags\Module::SITE_GROUP];
        }
    
        public function get_categories() {
            return [\ElementorPro\Modules\DynamicTags\Module::GALLERY_CATEGORY];
        }
    
        protected function get_value(array $options = []) {
            $gallery_ids = get_post_meta(get_the_ID(), 'brro_image_gallery_ids', true);
            $ids = explode(',', $gallery_ids);
            $images = array_map(function ($id) {
                return [
                    "id" => $id,
                    "url" => wp_get_attachment_url($id),
                ];
            }, $ids);
    
            return $images;
        }
    }
    $dynamic_tags->register_tag("Brro_Custom_Gallery_Image_Tag");
});
*
*/
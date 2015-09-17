<?php
/**
 * Adds WPLastSeen_Widget
 */
class WPLastSeen_Widget extends WP_Widget {

    public $WP_LastSeen;
    public $version;

    /**
     * Register widget with WordPress.
     */
    public function __construct() {

        parent::__construct(
            'WPLastSeen_Widget', // Base ID
            __('WP Last Seen Widget', 'wp-last-seen'), // Name
            array(
                'description' => __('Creates a last seen user widget in front of your site', 'wp-last-seen'),
                ) // Args
       );

        add_action('sidebar_admin_setup', array($this, 'admin_setup'));
        $this->version = '1.0.0';

    }

    public function admin_setup(){

        wp_enqueue_media();
        // $handle, $src, $dependencies, $ver, $in_footer
        wp_register_script('wp-last-seen-widget-admin-js', plugins_url('static/js/widget-admin.js', __FILE__), array(), $this->version, true);
        wp_enqueue_script('wp-last-seen-widget-admin-js');
        wp_enqueue_style('wp-last-seen-widget-admin-css', plugins_url('static/css/widget-admin.css', __FILE__));

    }

    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget($args, $instance) {

        // use a template for the output so that it can easily be overridden by theme

        // check for template in active theme
        $template = locate_template(array('wp-last-seen-widget_template.php'));

        // if none found use the default template
        if (empty($template)){
            $template = 'wp-last-seen-widget_template.php';
        }

        $this->WP_LastSeen = $GLOBALS['WP_LastSeen'];

        include $template;

    }

    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database
     */
    public function form($instance) {

        $title_text = (isset($instance['title_text'])) ? $instance['title_text'] : '';

        $html = "
                <h4>Title (optional)</h4>
                <p>
                    <div class='widget_input'>
                        <input
                            class='title_text'
                            id='{$this->get_field_id('title_text')}'
                            name='{$this->get_field_name('title_text')}'
                            value='{$title_text}'
                            type='text'
                        />
                    </div>
                </p>
        ";
        echo $html;

    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update($new_instance, $old_instance) {

        $instance = array();
        $instance['title_text'] = (!empty($new_instance['title_text'])) ? strip_tags($new_instance['title_text']) : '';
        return $instance;
    }

}

add_action('widgets_init', function(){
    register_widget('WPLastSeen_Widget');
});
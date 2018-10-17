<?php
/**
 * Plugin Name: TinyForms
 * Plugin URI: https://plugin.com/
 * Description: A tiny form plugin to take user input
 * Version: 1.0.0
 * Author: M Yakub Mizan
 * Author URI: https://author.com/.
 */
if (!defined('ABSPATH')) {
    exit; // Prevent direct execution outside WordPress environment.
}

/**
 * TINYFORMS_Main Class.
 *
 * Main class that works as the entry point for all our form logic
 */
class TINYFORMS_Main
{
    /**
     * The single instance of the class.
     *
     * @var TINYFORMS_Main
     */
    protected static $_instance = null;

    /**
     * Main TINYFORMS_Main Instance.
     *
     * Make sure that we have only instance
     *
     * @static
     *
     * @return TINYFORMS_Main
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * All action hooks and filter hooks should go here.
     */
    public function __construct()
    {
        add_shortcode('tinyforms', array($this, 'shortcode_callback'));
        add_shortcode('tinyforms_formdata', array($this, 'formdata_shortcode_callback'));
        add_action('admin_post_nopriv_tinyforms_submit', array($this, 'save_form_callback'));
        add_action('admin_post_tinyforms_submit', array($this, 'save_form_callback'));
    }

    /**
     * Shortcode callback function. Return the form.
     *
     * @return string form
     */
    public function shortcode_callback()
    {
        return $this->form_builder();
    }

    /**
     * Return table with submitted form data.
     *
     * @return string table with form data
     */
    public function formdata_shortcode_callback()
    {
        return $this->show_submitted_forms();
    }

    /**
     * Generate the form to display on the front page.
     *
     * @todo Ideally it should a seperate class with form builder
     * @todo Remove the inline CSS
     * @todo Seperate the template from the code
     *
     * @return string The form html returned
     */
    public function form_builder()
    {
        return "<form method='POST' action='".admin_url('admin-post.php')."'>".
            wp_nonce_field('tinyforms_submit', 'tinyforms_nonce', false, false).
            "<input type='hidden' name='action' value='tinyforms_submit' />".
            "<label style='display:block;'> Name </label>".
            "<input style='display:block; width:100%;' type='text' value='' name='name' required />".
            "<label style='display:block;'> Description </label> ".
            "<textarea style='display:block;' name='description' required /> </textarea>".
            "<input style='margin-top:10px;' type='submit' value='Save' />".
            '</form>';
    }

    /**
     * Save the submitted form data in options table.
     *
     * @todo Being saved in the options table for the sake of simplicity.
     * Should be in a seperate table for a production grade application
     *
     * @return resource a redirect is issued
     */
    public function save_form_callback()
    {
        //verify nonce to ensure it's not coming from a submission bot
        if (!isset($_POST['tinyforms_nonce']) ||
            !wp_verify_nonce($_POST['tinyforms_nonce'], 'tinyforms_submit')) {
            wp_die('Submission expired. Try reloding the page.');
        }

        $option = get_option('tinyforms_data'); //we save all our data in options table

        if (!is_array($option)) {
            $option = array();
        }

        $option[] = array(
            'name' => sanitize_text_field($_POST['name']), //no meta-characters or tags allowed
            'description' => sanitize_textarea_field($_POST['description']), //preserve new line but strip tags
        );

        update_option('tinyforms_data', $option);

        wp_redirect(wp_get_referer()); //redirect back to the form page
    }

    /**
     * Display submitted form data in a table.
     *
     * @todo keep template code seperate from the logic
     * @todo Internationalization needs to be added
     *
     * @return string
     */
    public function show_submitted_forms()
    {
        $option = get_option('tinyforms_data');
        $data = '<p> Data will be shown here once submitted </p>';

        if (is_array($option)) {
            $data = ''; //we hvae data to show. Remove default message.
            $data .= '<table> <tr> <th> Name </th> <th> Description </th> </tr>';

            foreach ($option as $single_option) {
                //database too can be unsafe source in case its hacked, so sanitize that too.
                $data .= '<tr> <td> '.esc_html($single_option['name']).'</td> <td> '.esc_html($single_option['description']).'</td> </tr>';
            }
            $data .= '</table>';
        }

        return $data;
    }
}

TINYFORMS_Main::instance();

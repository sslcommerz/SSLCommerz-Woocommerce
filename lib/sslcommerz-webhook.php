<?php 

/*
* Rewrite url for IPN listener.
*/

class SSLCommerz_Ipn
{

    protected static $instance = NULL;

    public function __construct()
    { }

    public static function get_instance()
    {
        NULL === self::$instance and self::$instance = new self;
        return self::$instance;
    }

    public function setup()
    {
        add_action('init', array($this, 'rewrite_rules'));
        add_filter('query_vars', array($this, 'query_vars'), 10, 1);
        add_action('parse_request', array($this, 'parse_request'), 10, 1);

        register_activation_hook(__FILE__, array($this, 'flush_rules'));
    }

    public function rewrite_rules()
    {
        add_rewrite_rule('sslcommerzipn/?$', 'easyCheckout.php?sslcommerzipn', 'top');
    }

    public function flush_rules()
    {
        $this->rewrite_rules();
        flush_rewrite_rules();
    }

    public function query_vars($vars)
    {
        $vars[] = 'sslcommerzipn';
        return $vars;
    }

    public function parse_request($wp)
    {
        if (array_key_exists('sslcommerzipn', $wp->query_vars)) {
            include plugin_dir_path(__FILE__) . 'sslcommerz_ipn.php';
            exit();
        }
    }
}
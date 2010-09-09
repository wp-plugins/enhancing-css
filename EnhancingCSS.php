<?php
/*
Plugin Name: Enhancing CSS
Plugin URI: http://firegoby.theta.ne.jp/wp/enhancingcss
Description: Add & Edit custom stylesheet throught WordPress Dashboard.
Author: Takayuki Miyauchi (THETA NETWORKS Co,.Ltd)
Version: 0.1
Author URI: http://firegoby.theta.ne.jp/
*/

/*
Copyright (c) 2010 Takayuki Miyauchi (THETA NETWORKS Co,.Ltd).

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

require_once(dirname(__FILE__).'/addrewriterules.class.php');

new EnhancingCSS();


class EnhancingCSS{

    private $title      = '';
    private $name       = 'EnhancingCSS';
    private $role       = 'edit_theme_options';
    private $basedir    = null;

    function __construct()
    {
        new AddRewriteRules(
            $this->name.'.css$',
            $this->name,
            array(&$this, 'get_style')
        );
        $this->basedir = WP_PLUGIN_URL.'/'.dirname(plugin_basename(__FILE__));
        add_action('admin_menu', array(&$this, 'add_admin_page'));
        add_action('admin_head', array(&$this, 'admin_head'));
        add_action('wp_head', array(&$this, 'wp_head'));
    }

    private function get_style_url()
    {
        global $wp_rewrite;
        if ($wp_rewrite->using_permalinks()) {
            $url = site_url().'/'.$this->name.'.css';
        } else {
            $url = site_url().'/?'.$this->name.'=true';
        }
        return $url;
    }

    public function wp_head()
    {
        $css = "<link rel=\"stylesheet\" href=\"%s\" type=\"text/css\" />";
        echo "<!-- Enhancing CSS Plugin -->\n";
        echo sprintf($css, $this->get_style_url())."\n";
    }

    public function get_style()
    {
        header('Content-type: text/css');
        echo $this->get_style_src();
        exit;
    }

    private function get_style_src()
    {
        if($style = trim(get_option('EnhancingCSS'))){
            $css = stripslashes($style);
        } else {
            $css = "/* {$this->title} */\n";
        }
        return $css;
    }

    public function add_admin_page(){
        load_plugin_textdomain(
            $this->name, 
            PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/langs', 
            dirname(plugin_basename(__FILE__)).'/langs'
        );
        $this->title = __('Enhancing CSS', $this->name);
        add_theme_page(
            $this->title,
            $this->title,
            $this->role,
            $this->name,
            array(&$this, 'admin_page')
        );
    }

    public function admin_head()
    {
        if (isset($_GET['page']) && $_GET['page'] == $this->name) {
            $js = $this->basedir.'/js/codemirror.js';
            $script = '<script src="%s" type="%s" charset="%s"></script>';
            echo sprintf($script, $js, 'text/javascript', 'UTF-8')."\n";
            $css = "<link rel=\"stylesheet\" href=\"%s\" type=\"text/css\" />";
            echo sprintf($css, $this->basedir.'/css/style.css');
        }
        add_filter('mce_css', array(&$this, 'add_style'));
    }

    public function add_style($css)
    {
        if (get_option('EnhancingCSS.AddStyle')) {
            $files = preg_split("/,/", $css);
            $files[] = $this->get_style_url();
            $files = array_map('trim', $files);
            return join(",", $files);
        }
    }

    public function admin_page(){
        global $wp_rewrite;

        echo '<div class="wrap">';
        echo '<form id="edit_css" action="'.$_SERVER['REQUEST_URI'].'" method="post">';
        echo '<input type="hidden" id="action" name="action" value="save">';
        echo '<div id="icon-themes" class="icon32"><br /></div>';

        echo '<h2>'.$this->title.'</h2>';
        if ( isset($_POST['action']) && $_POST['action'] == 'save' ){
            update_option('EnhancingCSS', $_POST['EnhancingCSS']);
            if (isset($_POST['AddStyle'])) {
                update_option('EnhancingCSS.AddStyle', 1);
            } else {
                update_option('EnhancingCSS.AddStyle', 0);
            }
            echo "<div id=\"message\" class=\"updated fade\"><p><strong>".__("Saved.")."</strong></p></div>";
        }

        $url = $this->get_style_url();
        echo "<p><a href=\"{$url}\">{$url}</a></p>";
        echo '<textarea id="EnhancingCSS" name="EnhancingCSS" style="width:90%;height:300px;">';
        echo $this->get_style_src();
        echo '</textarea>';
        echo "<p>";
        if (get_option('EnhancingCSS.AddStyle')) {
            echo "<input type=\"checkbox\" id=\"AddStyle\" name=\"AddStyle\" value=\"1\" checked=\"checked\" />";
        } else {
            echo "<input type=\"checkbox\" id=\"AddStyle\" name=\"AddStyle\" value=\"1\" />";
        }
        echo "<label for=\"AddStyle\">".__('Add style to The Visual Editor.', $this->name)."</label>";
        echo "</p>";

        echo '<p>';
        echo '<input type="submit" value="'.__('Save').'"> ';
        echo '</p>';
        echo '</form>';
        echo '</div>';
        echo "<script type=\"text/javascript\">\n";
        echo "  var editor = CodeMirror.fromTextArea('EnhancingCSS', {\n";
        echo "    height: \"350px\",\n";
        echo "    parserfile: [\"parsecss.js\"],\n";
        echo "    stylesheet: [\"".$this->basedir."/css/csscolors.css\"],\n";
        echo "    path: \"".$this->basedir."/js/\"\n";
        echo "  });\n";
        echo "</script>";
    }
}

?>

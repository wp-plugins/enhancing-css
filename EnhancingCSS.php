<?php
/*
Plugin Name: Enhancing CSS
Plugin URI: http://firegoby.theta.ne.jp/wp/enhancingcss
Description: Add & Edit custom stylesheet throught WordPress Dashboard.
Author: Takayuki Miyauchi (THETA NETWORKS Co,.Ltd)
Version: 0.7
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
        add_action('wp_head', array(&$this, 'wp_head'), 9999);
    }

    private function get_style_url()
    {
        global $wp_rewrite;
        if ($wp_rewrite->using_permalinks()) {
            $url = get_bloginfo('url').'/'.$this->name.'.css';
        } else {
            $url = get_bloginfo('url').'/?'.$this->name.'=true';
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
        if (is_user_logged_in() && isset($_GET['download']) && $_GET['download'] == 1) {
            header('Content-type: text/download');
            header('Content-Disposition: attachment; filename="style.css"');
            $theme = get_template_directory().'/style.css';
            echo "/*\n";
            echo "Theme Name: MyTheme\n";
            echo "Template: ".get_template()."\n";
            echo "*/\n";
            echo "\n";
            echo "@import url(../".get_template()."/style.css);\n";
            echo "\n";
        } else {
            header('Content-type: text/css');
            $this->conditional_get(get_option('EnhancingCSS.last_modified', 0));
        }
        echo $this->get_style_src();
        exit;
    }

    private function conditional_get($time = 0)
    {
        $last_modified = gmdate('D, d M Y H:i:s T', $time);
        $etag = md5($last_modified);
        header('Last-Modified: '.$last_modified);
        header('ETag: "'.$etag.'"');
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            if ($_SERVER['HTTP_IF_MODIFIED_SINCE'] == $last_modified) {
                header('HTTP/1.1 304 Not Modified');
                exit;
            }
        }
        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            if (preg_match("/{$etag}/", $_SERVER['HTTP_IF_NONE_MATCH'])) {
                header('HTTP/1.1 304 Not Modified');
                exit;
            }
        }
    }

    private function get_style_src()
    {
        if($style = trim(get_option('EnhancingCSS'))){
            $style = str_replace(array("\r\n", "\r"), "\n", $style);
            $css = stripslashes($style);
        } else {
            $css = "/* Your style */\n";
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
            $script = '<script src="%s" type="%s" charset="%s"></script>';
            echo sprintf($script, $this->basedir.'/codemirror/codemirror.js', 'text/javascript', 'UTF-8')."\n";
            echo sprintf($script, $this->basedir.'/js/csseditor.js', 'text/javascript', 'UTF-8')."\n";
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
        } else {
            return $css;
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
            update_option('EnhancingCSS.last_modified', time());
            if (isset($_POST['AddStyle'])) {
                update_option('EnhancingCSS.AddStyle', 1);
            } else {
                update_option('EnhancingCSS.AddStyle', 0);
            }
            echo "<div id=\"message\" class=\"updated fade\"><p><strong>".__("Saved.")."</strong></p></div>";
        }

        $url = $this->get_style_url();
        echo "<p>";
        echo "<a href=\"{$url}\">{$url}</a>";
        echo "&nbsp;- &nbsp;<a href=\"{$url}?download=1\">";
        echo __("Download for the Child Theme", $this->name);
        echo "</a>";
        echo "</p>";
        echo "<div id=\"editor\" class=\"stuffbox\">";
        echo '<textarea id="EnhancingCSS" name="EnhancingCSS" style="width:90%;height:300px;">';
        echo $this->get_style_src();
        echo '</textarea>';
        echo "</div>";
        echo "<p>";
        if (get_option('EnhancingCSS.AddStyle')) {
            echo "<input type=\"checkbox\" id=\"AddStyle\" name=\"AddStyle\" value=\"1\" checked=\"checked\" />";
        } else {
            echo "<input type=\"checkbox\" id=\"AddStyle\" name=\"AddStyle\" value=\"1\" />";
        }
        echo "<label for=\"AddStyle\">".__('Add style to The Visual Editor.', $this->name)."</label>";
        echo "</p>";

        echo '<p>';
        echo '<input type="submit" class="button-primary" value="'.__('Save Changes').'"> ';
        echo '</p>';
        echo '</form>';
        echo '</div>';
        echo "<script type=\"text/javascript\">\n";
        echo "  var obj = document.getElementById('EnhancingJS');\n";
        echo "  var btn = [\n";
        echo "    ['".__('Undo', $this->name)."', 'undo'],\n";
        echo "    ['".__('Redo', $this->name)."', 'redo'],\n";
        echo "    ['".__('Search', $this->name)."', 'search'],\n";
        echo "    ['".__('Replace', $this->name)."', 'replace']\n";
        echo "  ];\n";
        echo "  new CSSEditor('EnhancingCSS', '".$this->basedir."', btn);";
        echo "</script>";
    }
}

?>

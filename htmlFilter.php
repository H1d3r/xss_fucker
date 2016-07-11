<?php

include_once 'urlFilter.php';
include_once 'htmlParser.php';
//include_once 'cssParser.php';


class htmlFilter
{
    private $_lastError = 0;
    private $_lastErrorMsg = "";
    private $_htmlDom = null;
    private $_safeDom = null;
    private $_uf = null;
    
    //������ѡ������Ƿ��������������޷��ҵ�������ЧHTML��ǩʱ�����Զ��պ�
    //��ѡ����Թ���������input��valueע���img��srcע�������domע�룬���磺x" onerror="alert(/xss/)��payload���Ա���������
    //����ѡ������ܻ���ɴ�����ˣ��������룺if select == "abcdefg" or select >= 5 then aaa = "ccc"
    //�����������if select == ">= 5 then aaa = "ccc"
    //�����о����Ƿ�Ĭ�Ͽ�������ʹ��setAutoClosing�������䶯̬����
    private $_opt_autoclosing = true;

    const ERR_CODE_OK = 0;
    const ERR_CODE_BAD_HTML = 1;
    const ERR_CODE_EMPTY_HTML = 2;

    const ERR_MSG_OK = "";
    const ERR_MSG_SHD_NOT_FOUND = "class 'simple_html_dom' NOT found";
    const ERR_MSG_SCP_NOT_FOUND = "class 'simple_css_parser' NOT found";
    const ERR_MSG_SHDN_NOT_FOUND = "class 'simple_html_dom_node' NOT found";
    const ERR_MSG_SUF_NOT_FOUND = "class 'simple_url_filter' NOT found";
    const ERR_MSG_BAD_HTML = "Bad HTML string";
    const ERR_MSG_EMPTY_HTML = "Empty HTML string";


    /*
     * ���ð�������ǩ������������ԣ����Ǹñ�ǩ�ǰ��������������ñ�ǩ����������ǩ���ݣ�
     * ע�⣬�������ʵ����Ҫͬ���Ƴ���form��ǩ���Է�ֹ�û�α��һ����¼���Ѽ���Ϣ�ı���
     * �ò���ȫΪСд
     */
    private $_ALLOW_TAGS = array(
        "a" => array("class", "title", "style", "dir", "lang", "xml:lang", "charset", "coords", "href", "hreflang", "name", "rel", "rev", "shape", "target", "type"),
        "abbr" => array("class", "title", "style", "dir", "lang", "xml:lang"),
        "acronym" => array("class", "title", "style", "dir", "lang", "xml:lang"),
        "address" => array("class", "title", "style", "dir", "lang", "xml:lang"),
        "area" => array("class", "title", "style", "dir", "lang", "xml:lang", "alt", "coords", "href", "nohref", "shape", "target"),
        "b" => array("class", "title", "style", "dir", "lang", "xml:lang"),
        "bdo" => array("class", "title", "style", "dir", "lang", "xml:lang"),
        "big" => array("class", "title", "style", "dir", "lang", "xml:lang"),
        "blockquote" => array("class", "title", "style", "dir", "lang", "xml:lang", "cite"),
        "br" => array("class", "title", "style"),
        "button" => array("class", "title", "style", "dir", "lang", "xml:lang", "tabindex", "disabled", "name", "type", "value", "size"),
        "caption" => array("class", "title", "style", "dir", "lang", "xml:lang", "alignspan"),
        "center" => array("class", "title", "style", "dir", "lang", "xml:lang"),
        "cite" => array("class", "title", "style", "dir", "lang", "xml:lang"),
        "col" => array("class", "title", "style", "dir", "lang", "xml:lang", "align", "char", "charoff", "span", "valign", "width"),
        "colgroup" => array("class", "title", "style", "dir", "lang", "xml:lang", "align", "char", "charoff", "span", "valign", "width"),
        "dd" => array("class", "title", "style", "dir", "lang", "xml:lang"),
        "del" => array("class", "title", "style", "dir", "lang", "xml:lang", "cite", "datetime"),
        "dfn" => array("class", "title", "style", "dir", "lang", "xml:lang"),
        "div" => array("class", "title", "style", "dir", "lang", "xml:lang", "data-widget-type", "data-widget-config"),
        "dl" => array("class", "title", "style", "dir", "lang", "xml:lang"),
        "dt" => array("class", "title", "style", "dir", "lang", "xml:lang"),
        "em" => array("class", "title", "style", "dir", "lang", "xml:lang"),
        "embed" => array("class", "title", "style", "dir", "lang", "xml:lang", "allowscriptaccess", "allownetworking", "flashvars", "height", "name", "quality", "src", "type", "var", "width", "wmode", "border", "contenteditable", "pluginspage", "play", "loop", "menu"),
        "fieldset" => array("class", "title", "style", "dir", "lang", "xml:lang"),
        "font" => array("class", "title", "style", "dir", "lang", "xml:lang", "color", "face", "size"),
        "h1" => array("class", "title", "style", "dir", "lang", "xml:lang", "align"),
        "h2" => array("class", "title", "style", "dir", "lang", "xml:lang", "align"),
        "h3" => array("class", "title", "style", "dir", "lang", "xml:lang", "align"),
        "h4" => array("class", "title", "style", "dir", "lang", "xml:lang", "align"),
        "h5" => array("class", "title", "style", "dir", "lang", "xml:lang", "align"),
        "h6" => array("class", "title", "style", "dir", "lang", "xml:lang", "align"),
        "hr" => array("class", "title", "style", "dir", "lang", "xml:lang", "align", "noshade", "size", "width"),
        "marquee" => array("class", "title", "style", "dir", "lang", "xml:lang", "behavior", "direction", "scrolldelay", "scrollamount", "loop", "width", "height", "vspace", "hspace", "bgcolor"),
        "i" => array("class", "contenteditable", "contextmenu", "dir", "draggable", "irrelevant", "lang", "ref", "registrationmark", "tabindex", "template", "title"),
        "img" => array("class", "title", "style", "lang", "xml:lang", "alt", "src", "align", "border", "height", "hspace", "ismap", "long", "desc", "usemap", "vspace", "width"),
        "input" => array("class", "title", "style", "lang", "xml:lang", "alt", "checked", "disabled", "maxlength", "name", "readonly", "size", "src", "tabindex", "type", "usemap", "value"),
        "ins" => array("class", "title", "style", "lang", "xml:lang", "cite", "datetime"),
        "kbd" => array("class", "title", "style", "lang", "xml:lang"),
        "label" => array("class", "title", "style", "lang", "xml:lang", "for"),
        "legend" => array("class", "title", "style", "lang", "xml:lang", "align"),
        "li" => array("class", "title", "style", "dir", "lang", "xml:lang", "type", "value"),
        "map" => array("class", "title", "style", "dir", "lang", "xml:lang", "name"),
        "ol" => array("class", "title", "style", "dir", "lang", "xml:lang", "compact", "start", "type"),
        "optgroup" => array("class", "title", "style", "dir", "lang", "xml:lang", "label", "disabled"),
        "option" => array("class", "title", "style", "dir", "lang", "xml:lang", "disabled", "label", "selected", "value"),
        "p" => array("class", "title", "style", "dir", "lang", "xml:lang", "align"),
        "pre" => array("class", "title", "style", "dir", "lang", "xml:lang", "xml:space", "width"),
        "q" => array("class", "title", "style", "dir", "lang", "xml:lang", "cite"),
        "s" => array("class", "title", "style", "dir", "lang", "xml:lang"),
        "select" => array("class", "title", "style", "dir", "lang", "xml:lang", "accesskey", "tabindex", "disabled", "multiple", "name", "size"),
        "small" => array("class", "title", "style", "dir", "lang"),
        "span" => array("class", "title", "style", "dir", "lang", "xml:lang"),
        "strike" => array("class", "title", "style", "dir", "lang", "xml:lang"),
        "strong" => array("class", "title", "style", "dir", "lang", "xml:lang"),
        "sub" => array("class", "title", "style", "dir", "lang", "xml:lang"),
        "sup" => array("class", "title", "style", "dir", "lang", "xml:lang"),
        "table" => array("class", "title", "style", "dir", "lang", "xml:lang", "align", "bgcolor", "border", "cellpadding", "cellspacing", "frame", "rules", "summary", "width"),
        "tbody" => array("class", "title", "style", "dir", "lang", "xml:lang", "align", "char", "charoff", "valign"),
        "td" => array("class", "title", "style", "dir", "lang", "xml:lang", "abbr", "align", "axis", "bgcolor", "char", "charoff", "colspan", "headers", "height", "nowrap", "rowspan", "scope", "valign", "width"),
        "textarea" => array("class", "title", "style", "dir", "lang", "xml:lang", "cols", "rows", "disabled", "name", "readonly"),
        "tfoot" => array("class", "title", "style", "dir", "lang", "xml:lang", "align", "char", "charoff", "valign"),
        "th" => array("class", "title", "style", "dir", "lang", "xml:lang", "abbr", "align", "axis", "bgcolor", "char", "charoff", "colspan", "headers", "height", "nowrap", "rowspan", "scope", "valign", "width"),
        "thead" => array("class", "title", "style", "dir", "lang", "xml:lang", "align", "char", "charoff", "valign"),
        "tr" => array("class", "title", "style", "dir", "lang", "xml:lang", "align", "bgcolor", "char", "charoff", "valign"),
        "tt" => array("class", "title", "style", "dir", "lang"),
        "u" => array("class", "title", "style", "dir", "lang", "xml:lang"),
        "ul" => array("class", "title", "style", "dir", "lang", "xml:lang", "compact", "type"),
        "var" => array("class", "title", "style", "dir", "lang", "xml:lang"),
        "section" => array("class", "title", "style", "dir", "lang", "xml:lang"),

        //һЩH5���еı�ǩ
        "article" => array("class", "title", "style", "dir", "lang", "xml:lang", "spellcheck", "translate", "hidden", "dropzone", "draggable", "contenteditable"),
        "aside" => array("class", "title", "style", "dir", "lang", "xml:lang", "spellcheck", "translate", "hidden", "dropzone", "draggable", "contenteditable"),
        "audio" => array("class", "title", "style", "dir", "lang", "xml:lang", "spellcheck", "translate", "hidden", "dropzone", "draggable", "contenteditable", "autoplay", "controls", "loop", "muted", "preload", "src"),
        "bdi" => array("class", "title", "style", "dir", "lang", "xml:lang", "spellcheck", "translate", "hidden", "dropzone", "draggable", "contenteditable"),
        "datalist" => array("class", "title", "style", "dir", "lang", "xml:lang", "spellcheck", "translate", "hidden", "dropzone", "draggable", "contenteditable"),
        "details" => array("class", "title", "style", "dir", "lang", "xml:lang", "spellcheck", "translate", "hidden", "dropzone", "draggable", "contenteditable"),
        "figcaption" => array("class", "title", "style", "dir", "lang", "xml:lang", "spellcheck", "translate", "hidden", "dropzone", "draggable", "contenteditable"),
        "figure" => array("class", "title", "style", "dir", "lang", "xml:lang", "spellcheck", "translate", "hidden", "dropzone", "draggable", "contenteditable"),
        "mark" => array("class", "title", "style", "dir", "lang", "xml:lang", "spellcheck", "translate", "hidden", "dropzone", "draggable", "contenteditable"),
        "progress" => array("class", "title", "style", "dir", "lang", "xml:lang", "spellcheck", "translate", "hidden", "dropzone", "draggable", "contenteditable", "max", "value"),
        "source" => array("class", "title", "style", "dir", "lang", "xml:lang", "spellcheck", "translate", "hidden", "dropzone", "draggable", "contenteditable", "media", "src", "type"),
        "summary" => array("class", "title", "style", "dir", "lang", "xml:lang", "spellcheck", "translate", "hidden", "dropzone", "draggable", "contenteditable"),
        "time" => array("class", "title", "style", "dir", "lang", "xml:lang", "spellcheck", "translate", "hidden", "dropzone", "draggable", "contenteditable", "datetime", "pubdate"),
        "track" => array("class", "title", "style", "dir", "lang", "xml:lang", "spellcheck", "translate", "hidden", "dropzone", "draggable", "contenteditable", "src", "srclang", "label", "kind", "default"),
        "video" => array("class", "title", "style", "dir", "lang", "xml:lang", "spellcheck", "translate", "hidden", "dropzone", "draggable", "contenteditable", "autoplay", "controls", "height", "loop", "muted", "poster", "preload", "src", "width"),
        "wbr" => array("class", "title", "style", "dir", "lang", "xml:lang", "spellcheck", "translate", "hidden", "dropzone", "draggable", "contenteditable"),

        //����һЩ�Զ����ǩ
        "code" => array(),
        "comment" => array(),
        "_" => array(
            "class","title","style","dir","lang","xml:lang","charset","coords","href","hreflang","name","rel","rev","shape","target","type","alt","nohref","cite","tabindex","disabled","value","size","alignspan","align","char","charoff","span","valign","width","datetime","data-widget-type","data-widget-config","allowscriptaccess","allownetworking","flashvars","height","quality","src","var","wmode","border","contenteditable","pluginspage","play","loop","menu","color","face","noshade","behavior","direction","scrolldelay","scrollamount","vspace","hspace","bgcolor","contextmenu","draggable","irrelevant","ref","registrationmark","template","ismap","long","desc","usemap","checked","maxlength","readonly","for","compact","start","label","selected","xml:space","accesskey","multiple","cellpadding","cellspacing","frame","rules","summary","abbr","axis","colspan","headers","nowrap","rowspan","scope","cols","rows","spellcheck","translate","hidden","dropzone","autoplay","controls","muted","preload","max","media","pubdate","srclang","kind","default","poster"
        ),     //����һ������ı�ǩ�����ڼ�⵱ĳ���������ֻ��ĳ��html��ǩ��һ����ʱ��ʹ�ø������ǩǿ�бպϣ�������Ԥ�ȹ���
        "root" => array(),  //simple_html_dom���õ�һ�������ǩ����ʾDOM���ĸ��ڵ㣬�������丽���κ����ԣ�ͬʱ�����ʱ�������ñ�ǩ
        "text" => array()   //simple_html_dom���õ�һ�������ǩ����ʾ��ǩ�а�����һ���ı����������丽���κ����ԣ����ʱ�ᾭ�������չʾ
    );

    /*
     * ����ĳЩ��ǩ��ѡ����
     * ����ñ�ѡ����ǿ��Ϊ�ض�ֵ��������ض�ֵ��������null��
     */
    private $_TAG_BASE_ATTRS = array(
        "embed" => array("allowscriptaccess" => "nerver"),
        "img" => array("src" => null),
        "optgroup" => array("label" => null),

        "audio" => array("src" => null),
        "source" => array("src" => null),
        "track" => array("src" => null),
        "video" => array("src" => null)
    );
    
    /*
     * ָʾ��Щ������Ҫ����URL���˵�
     */
    private $_ATTR_URL_FILT = array(
        "src", "href"
    );


    function __construct($html=null, $autoclosing=null)
    {
        if (!class_exists("simple_html_dom", true)) exit(self::ERR_MSG_SHD_NOT_FOUND);
        if (!class_exists("simple_html_dom_node", true)) exit(self::ERR_MSG_SHDN_NOT_FOUND);
        //if (!class_exists("simple_css_parser", true)) exit(self::ERR_MSG_SCP_NOT_FOUND);
        if (!class_exists("simple_url_filter", true)) exit(self::ERR_MSG_SUF_NOT_FOUND);
        
        $this->_uf = new simple_url_filter();
        
        if (is_bool($autoclosing)) $this->setAutoClosing($autoclosing);
        if (is_string($html)) $this->safeHTML($html);
    }
    
    public function setAutoClosing($switch=false)
    {
        $this->_opt_autoclosing = ($switch === true) ? true : false;
        return $this;
    }

    //���Խ���HTML�ַ�������ת��ΪDOM����
    private function _parserHTML($html_string="")
    {
        if (!is_string($html_string))
        {
            $this->_setError(self::ERR_CODE_BAD_HTML, self::ERR_MSG_BAD_HTML);
            return false;
        }

        $this->_htmlDom = new simple_html_dom($html_string);
        $this->_setError();
        return true;
    }

    private function _setError($errorCode=self::ERR_CODE_OK, $errorMsg=self::ERR_MSG_OK)
    {
        $this->_lastError = $errorCode;
        $this->_lastErrorMsg = $errorMsg;
    }

    function getLastError($intext=false)
    {
        if (!$intext) return $this->_lastError;
        return $this->_lastErrorMsg;
    }

    function safeHTML($html_string="", $autoclose=false)
    {

        $this->_safeDom = null;
        $this->_parserHTML($html_string);

        //�����ǰ�������0����Ч��HTML��ǩ��������ǿ�бպ�
        if (!isset($this->_htmlDom->root->children) || empty($this->_htmlDom->root->children))
        {
            //���ǿ�бպϺ���û�з���������HTML��ǩ���򷵻�Դ�ַ�����Ӧ����˵���ᷢ���������ɺǺ��գ�
            if ($autoclose === true)
            {
                $this->_setError(self::ERR_CODE_EMPTY_HTML, self::ERR_MSG_EMPTY_HTML);
                return substr($html_string, 8, -6);  //��ͷȥβ
            }
            
            if ($this->_opt_autoclosing !== true) return $html_string;
            
            $html_string_1 = "<_ dir=\"" . $html_string . ' \'" />';   //��������ǩ
            $html_string_2 = "<_ dir='" . $html_string . ' \'" />';   //��������ǩ
            
            $result1 = $this->safeHTML($html_string_1, true);
            $result2 = $this->safeHTML($html_string_2, true);
            
            $outputHTML = (strlen($result1) > strlen($result2)) ? $result2 : $result1; 
            
            return $outputHTML;
        }

        $this->_safeHTML($this->_htmlDom);

        $outputHTML = $this->_htmlDom->__toString();
        if ($autoclose === true && $this->_opt_autoclosing === true)
        {
            if ($outputHTML === "<_ />" || $outputHTML === "<_>" || $outputHTML === "<_/>" || $outputHTML === "<_ >")   //�����������ǩ���������ˣ�����Ϊ�ύ�����ݲ������κ�HTML������ȫ����
            {
                echo $outputHTML."<br />";
                $outputHTML = $html_string;
            }
            
            return substr($outputHTML, 8, -6);
        }
        return $outputHTML;

    }


    private function _safeHTML(&$node)
    {
        if (isset($node->children) && !empty($node->children))
        {
            foreach ($node->children as $_key=>$_children_node)
            {
                //var_dump($_children_node);
                if ($_children_node instanceof simple_html_dom_node)
                {
                    $this->_safeHTML($node->children[$_key]);
                }
            }
        }

        if (isset($node->nodes) && !empty($node->nodes))
        {
            foreach ($node->nodes as $_key=>$_sub_node)
            {
                if ($_sub_node instanceof simple_html_dom_node)
                {

                    //����ǩ�Ƿ�����
                    if (!isset($this->_ALLOW_TAGS[$_sub_node->tag]))
                    {
                        //echo "��ǩ {$_sub_node->tag}, ��������,��ɾ��.\n";

                        //��������ǩ���������������
                        $node->nodes[$_key]->outertext = "";  //����׼���÷���ֱ�Ӳ����˶�������
                        $node->nodes[$_key]->innertext = "";
                        continue;
                    }

                    //��������Ƿ�����
                    foreach ($_sub_node->attr as $_attr_name => $_attr_value)
                    {
                        if (!in_array($_attr_name, $this->_ALLOW_TAGS[$_sub_node->tag]))
                        {
                            $node->nodes[$_key]->removeAttribute($_attr_name);
                        }else{
                            
                            //��CSS���ԣ�style�����н�����Ԥ����
                            if ($_attr_name == "style")
                            {
                                //����Ԥ����
                                //�����Ȳ����� phith0n@wooyun��˼·���������ˣ�����������ʱ��ú�����һ��cssParser
                                //echo "�����Ǹ߹��STYLE���ˣ��Ǻ���{$_attr_value}<br />";
                                $_attr_value = str_replace('\\', ' ', $_attr_value);
                                $_attr_value = str_replace(array('&#', '/*', '*/'), ' ', $_attr_value);
                                $_attr_value = preg_replace('#e.*x.*p.*r.*e.*s.*s.*i.*o.*n#Uis', ' ', $_attr_value);
                                $node->nodes[$_key]->setAttribute("style", $_attr_value);
                            }
                            
                            //�������Ҫ����URL���ݵģ�����й���
                            if (in_array($_attr_name, $this->_ATTR_URL_FILT))
                            {
                                $safeURL = $this->_uf->safeURL($_attr_value);
                                $node->nodes[$_key]->setAttribute($_attr_name, $safeURL);
                            }

                            //������ԺϷ���
                            if (isset($this->_TAG_BASE_ATTRS[$_sub_node->tag][$_attr_name]))
                            {
                                //���������Ҫ��ǿ�Ƹ��ǵģ���ǿ�Ƹ�����
                                if (!is_null($this->_TAG_BASE_ATTRS[$_sub_node->tag][$_attr_name]))
                                {
                                    $node->nodes[$_key]->setAttribute($_attr_name, (string)$this->_TAG_BASE_ATTRS[$_sub_node->tag][$_attr_name]);
                                }
                            }
                        }
                    }

                    if (isset($this->_TAG_BASE_ATTRS[$_sub_node->tag]))
                    {
                        //Ȼ�����Ƿ�ȱʧ��ѡ���ԣ�ȱʧ��ѡ���Եģ�ֱ�ӿ���
                        foreach ($this->_TAG_BASE_ATTRS[$_sub_node->tag] as $_base_attr_name => $_base_attr_value)
                        {
                            $_node_attr_value = $_sub_node->getAttribute($_base_attr_name);
                            if (empty($_node_attr_value))
                            {
                                //echo "��ǩ {$_sub_node->tag}, ȱʧ��Ҫ���� {$_base_attr_name},��ɾ��.\n";

                                $node->nodes[$_key]->outertext = "";  //����׼���÷���ֱ�Ӳ����˶�������
                                $node->nodes[$_key]->innertext = "";
                                break;
                            }
                        }
                    }

                }
            }
        }

        return true;
    }

}


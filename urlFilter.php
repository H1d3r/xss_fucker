<?php


class simple_url_filter
{
    private $_eip = 0;
    private $_input_url = "";
    private $_input_url_len = 0;
    
    private $_scheme = "";
    private $_scheme_delimiter = "";
    private $_host = "";
    private $_host_array = array();
    private $_uri = "";
    private $_query = "";   //�����������ʱ����query_string����k-v�����Խ�Լ����
    
    private $_ALLOW_DOMAINS = array(
        "*"
    );
    
    private $_DISALLOW_DOMAINS = array(
        
    );
    
    private $_DISALLOW_URI = array(
        
    );
    
    private $_ALLOW_SCHEME = array(
        "http", "https", "ftp", ""
    );   //ע�⣬�������յĺ���Ϊ������Э�顣����ر������ַ��������������·����Э��̳�ʱ����������⡣
    

    public function safeURL($url)
    {
        if (empty($url) || strlen(trim($url)) < 1) return " ";
        $this->_input_url = trim((string)$url);
        $this->_input_url_len = strlen($this->_input_url);
        $this->_first();
        
        $this->_parserURL();
        

        //����scheme���������
        if (!in_array($this->_scheme, $this->_ALLOW_SCHEME)) return " ";
        
        //����HOST���������
        //if ($this->_in_host($this->_host_array, $this->_DISALLOW_DOMAINS)) return " ";
        
        //����HOST���������
        //if (!$this->_in_host($this->_host_array, $this->_ALLOW_DOMAINS)) return " ";
        
        //����URI���������
        //if ($this->_match_uri($this->_uri, $this->_DISALLOW_URI)) return " ";

        
        return $this->_toString();
        
    }
        
    
    private function _match_uri($needle, $haystack)
    {
        
    }
    
    private function _in_host($needle, $haystack)
    {
        
    }
    
    private function _first()
    {
        return $this->_index(0);
    }
    
    private function _last()
    {
        return $this->_index(($this->_input_url_len - 1));
    }
    
    private function _index($i=null)
    {
        if (is_numeric($i))
        {
            if ($i >= 0 && $i < $this->_input_url_len)
            {
                $this->_eip = $i;
                return $this->_input_url{$i};
            }else{
                return null;
            }
        }else{
            return $this->_eip;
        }
    }
    
    private function _current()
    {
        return $this->_input_url{$this->_eip};
    }
    
    private function _next()
    {
        if ($this->_eip + 1 >= $this->_input_url_len) return null;
        
        return $this->_input_url{$this->_eip++};
    }
    
    private function _pre()
    {
        if ($this->_eip < 1) return null;
        if ($this->_eip + 1 >= $this->_input_url_len) return null;
        
        return $this->_input_url{$this->_eip--};
    }
    
    private function _substr($start=0, $length=null)
    {
        if (!is_numeric($length)) return substr($this->_input_url, $start);
        
        return substr($this->_input_url, $start, $length);
    }
    
    
    private function _parserURL()
    {
        //��ʼ������
        $this->_eip = 0;
        $this->_scheme = "";
        $this->_scheme_delimiter = "";
        $this->_host = "";
        $this->_host_array = array();
        $this->_uri = "";
        $this->_query = "";
        
        $this->_getScheme();       
        $this->_getHost();
        $this->_host2Arr();
        $this->_getUri();
        $this->_getQuery();        
    }
    
    private function _host2Arr()
    {
        $this->_host_array = explode(".", $this->_host);
        return true;
    }
    
    private function _getScheme()
    {
        $this->_scheme = "";
        $this->_scheme_delimiter = "";
        $chars = "";
        
        if ($this->_next() == "/" && $this->_next() == "/")
        {
            //�����ж��Ƿ�Ϊ˫б�ܴ�ͷ��scheme�̳�д��
            $this->_scheme_delimiter = "//";
            return true;
            
        }else{
            $this->_pre();
            $this->_pre();
        }
        
        do {
            $char = $this->_next();
            if ($char == ":")
            {
                $this->_scheme = $chars;
                $this->_scheme_delimiter = $char;
                
                if ($this->_next() == "/")
                {
                    $this->_scheme_delimiter .= "/";
                }else{
                    $this->_pre();
                }
                
                if ($this->_next() == "/")
                {
                    $this->_scheme_delimiter .= "/";
                }else{
                    $this->_pre();
                }
                
                return true;
            }else{
                $chars .= $char;
            }
        }while($char !== null);
        
        //���û���ҵ�scheme_delimiter������Ϊ��URLû��ָ��scheme��
        $this->_first();
        
        return true;
    }
    
    private function _getHost()
    {
        $inHost = false;
        $this->_host = "";
        $chars = "";
        
        do {
            $char = $this->_next();
            if ($char == "/" && $inHost == false)
            {
                //�ų��ʼ��˫б�ܣ����
                $_next = $this->_next();
                if ($_next == "/")
                {
                    $inHost = true;
                    continue;
                }else{
                    //������Ϊ�����·�����ص���������ֹͣhost�ı���
                    $this->_pre();
                    $this->_pre();
                    break;
                }
            }
            
            if (($char == "/" || $char == "#" || $char == "?") && $inHost == true)
            {
                //��ʱ����Ϊhost�����Ѿ�ȡֵ��ϡ�����������#?����������ţ��ڲ���������У���host��������������ŵģ�Ҳ��Ϊ��host��β��
                $this->_pre();
                break;
            }
            
            $chars .= $char;
            
        }while($char !== null);
        
        $this->_host = $chars;
        
        return true;
    }
    
    private function _getUri()
    {
        $this->_uri = "";
        $chars = "";
        $lastDirIndex = 0;
        $endIndex = 0;
        
        do {
            $char = $this->_next();

            if ($char == "/") $lastDirIndex = $this->_index();
            
            if ($char == "#" || $char == "?")
            {
                //��ʱ����Ϊuri�����Ѿ�ȡֵ��ϡ�����������#?����������ţ��ڲ���������У���host��������������ŵģ�Ҳ��Ϊ��host��β��
                $this->_pre();
                $endIndex = $this->_index();
                break;
            }
        
            $chars .= $char;
        
        }while($char !== null);
        
        //����Ƿ���Ҫ���ˣ���ָ����query_stringʱ
        if ($endIndex > 0)
        {
            $chars = substr($chars, 0, $endIndex);
        }
        
        $this->_uri = $chars;
        
        return true;
    }
    
    private function _getQuery()
    {
        //ע�⣬�����ê�ı����Ҳ��Ϊquery����#��֮�󲿷֣�
        $this->_query = "";
        
        $query = $this->_substr($this->_index());
        
        if (!empty($query)) $this->_query = $query;
        
        return true;       
    }
        
    private function _toString()
    {
        $domain = join(".", $this->_host_array);
        
        $output = $this->_scheme;
        
        //���domainΪ�գ���scheme_delimiter��Ҫ����һ��/
        
        if (empty($domain))
        {
            $output .= substr($this->_scheme_delimiter, 0, -1);
        }else{
            $output .= $this->_scheme_delimiter;
        }
        
        $output .= $domain . $this->_uri . $this->_query;

        return $output;
    }
}
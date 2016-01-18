<?php

namespace SparQL;

# class xx_xml adapted code found at http://php.net/manual/en/function.xml-parse.php
# class is cc-by
# hello at rootsy dot co dot uk / 24-May-2008 09:30

class ParseXml
{

    // XML parser variables
    protected $parser;
    protected $name;
    protected $attr;
    protected $data = array();
    protected $stack = array();
    protected $keys;
    protected $path;
    protected $chars = "";
    protected $looks_legit = false;
    // Public properties
    public $rows;
    public $fields;
    // either you pass url atau contents.
    // Use 'url' or 'contents' for the parameter
    protected $type;

    // public function with the default parameter value
    public function __construct($url)
    {
        $this->url = $url;
        $this->type = 'contents';

        if (preg_match('~^https?://~', $url)) {
            $this->type = 'url';
        } else if (preg_match('~^file://~', $url)) {
            $filename = str_replace('file://', '', $url);
            if (!file_exists($filename)) {
                throw new Exception("File name $url does not exists");
            }
            $this->url = file_get_contents($filename);
        }

        $this->parse();
    }

    // parse XML data
    protected function parse()
    {
        $this->rows = array();
        $this->fields = array();
        $data = '';
        $this->parser = xml_parser_create("UTF-8");
        xml_set_object($this->parser, $this);
        xml_set_element_handler($this->parser, 'startXml', 'endXml');
        xml_set_character_data_handler($this->parser, 'charXml');

        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);

        if ($this->type == 'url') {
            // if use type = 'url' now we open the XML with fopen

            if (!($fp = @fopen($this->url, 'rb'))) {
                throw new Exception("Cannot open {$this->url}");
            }

            while (($data = fread($fp, 8192))) {
                if (!xml_parse($this->parser, $data, feof($fp))) {
                    throw new Exception(sprintf('XML error at line %d column %d',
                        xml_get_current_line_number($this->parser), xml_get_current_column_number($this->parser))
                    );
                }
            }
        } else if ($this->type == 'contents') {
            // Now we can pass the contents, maybe if you want
            // to use CURL, SOCK or other method.
            $lines = explode("\n", $this->url);
            foreach ($lines as $val) {
                $data = $val . "\n";
                if (!xml_parse($this->parser, $data)) {
                    throw new Exception($data . "\n" . sprintf('XML error at line %d column %d',
                        xml_get_current_line_number($this->parser), xml_get_current_column_number($this->parser))
                    );
                }
            }
        }
        if (!$this->looks_legit) {
            throw new Exception("Didn't even see a sparql element, is this really an endpoint?");
        }
    }

    protected function startXml($parser, $name, $attr)
    {
        if ($name == "sparql") {
            $this->looks_legit = true;
        }
        if ($name == "result") {
            $this->result = array();
        }
        if ($name == "binding") {
            $this->part = $attr["name"];
        }
        if ($name == "uri" || $name == "bnode") {
            $this->part_type = $name;
            $this->chars = "";
        }
        if ($name == "literal") {
            $this->part_type = "literal";
            if (isset($attr["datatype"])) {
                $this->part_datatype = $attr["datatype"];
            }
            if (isset($attr["xml:lang"])) {
                $this->part_lang = $attr["xml:lang"];
            }
            $this->chars = "";
        }
        if ($name == "variable") {
            $this->fields[] = $attr["name"];
        }
    }

    protected function endXml($parser, $name)
    {
        if ($name == "result") {
            $this->rows[] = $this->result;
            $this->result = array();
        }
        if ($name == "uri" || $name == "bnode" || $name == "literal") {
            $this->result[$this->part] = array("type" => $name, "value" => $this->chars);
            if (isset($this->part_lang)) {
                $this->result[$this->part]["lang"] = $this->part_lang;
            }
            if (isset($this->part_datatype)) {
                $this->result[$this->part]["datatype"] = $this->part_datatype;
            }
            $this->part_datatype = null;
            $this->part_lang = null;
        }
    }

    protected function charXml($parser, $data)
    {
        $this->chars .= $data;
    }
}

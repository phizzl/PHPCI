<?php


namespace Phizzl\PHPCI\Plugins\Codeception;


class ReportParserJson
{
    /**
     * @var string
     */
    private $filepath;

    /**
     * ReportParserJson constructor.
     * @param $filepath
     */
    public function __construct($filepath)
    {
        $this->filepath = $filepath;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function parse()
    {
        if(!$contents = file_get_contents($this->filepath)){
            throw new Exception("File could not be opened!");
        }

        if(!$parsed = json_decode($contents, true)){
            $parsed = $this->tryToParseParts($contents);
        }

        return $this->formatParsed($parsed);
    }

    /**
     * @param array $parsed
     * @return array
     */
    private function formatParsed(array $parsed)
    {
        $formatted = array();
        foreach($parsed as $item){
            $formatted[] = array(
                'suite' => isset($item['suite']) ? (string)$item['suite'] : '',
                'file' => isset($item['file']) ? (string)$item['file'] : '',
                'name' => isset($item['name']) ? (string)$item['name'] : '',
                'feature' => isset($item['test']) ? (string)$item['test'] : '',
                'assertions' => isset($item['assertions']) ? (int)$item['assertions'] : '',
                'time' => isset($item['time']) ? (float)$item['time'] : '',
                'class' => isset($item['class']) ? (string)$item['class'] : '',
                'pass' => isset($item['status']) && $item['status'] === 'pass',
                'event' => isset($item['event']) ? (string)$item['event'] : '',
                'message' => isset($item['message']) ? (string)$item['message'] : '',
                'output' => isset($item['output']) ? (string)$item['output'] : '',
                'trace' => isset($item['trace']) ? $item['trace'] : array(),
            );
        }

        return $formatted;
    }

    /**
     * @param string $contents
     * @return array
     * @throws Exception
     */
    private function tryToParseParts($contents)
    {
        $parsed = array();
        foreach(explode('{', $contents) as $part){
            if(!$partParsed = json_decode('{' . $part, true)){
                throw new Exception("File does not contain JSON");
            }
            $parsed[] = $partParsed;
        }

        return $parsed;
    }
}
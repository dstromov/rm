<?php

namespace rm\View;

class View
{
    private $templatesPath;
    
    public function __construct(string $templatesPath)
    {  
        $this->templatesPath = $templatesPath;
    }
    
    public function renderHtml(string $templateName)
    {
        ob_start();      
        include $this->templatesPath. '\\' . $templateName;
        $buffer = ob_get_contents();
        ob_end_clean();

        echo $buffer;

    }
}
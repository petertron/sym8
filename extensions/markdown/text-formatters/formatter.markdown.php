<?php

include_once(EXTENSIONS . '/markdown/lib/parsedown/Parsedown.php');
include_once(EXTENSIONS . '/markdown/lib/parsedown-extra/ParsedownExtra.php');
include_once(EXTENSIONS . '/markdown/lib/smartypants/smartypants.php');

Class formatterMarkdown extends TextFormatter {

    private static $_parser;

    public function about(){
        return array(
            'name' => 'Markdown',
            'version' => '1.8',
            'release-date' => '2010-04-30',
            'author' => array(
                'name' => 'Alistair Kearney',
                'website' => 'http://getsymphony.com',
                'email' => 'alistair@getsymphony.com'
            ),
            'description' => 'Write entries in the Markdown format. Wrapper for the PHP Markdown text-to-HTML conversion tool written by Michel Fortin.'
        );
    }

    public function run($string)
    {
        // Apply Markdown Extra
        $Parsedown = new ParsedownExtra();
        $Parsedown = $Parsedown->setBreaksEnabled(true);
        $string = $Parsedown->text($string);

        // Apply SmartyPants
        $string = SmartyPants($string, 1);

        // Return result
        return $string;
    }

}


<?php

class extension_register_php_functions extends Extension
{
    /**
     * Delegates and callbacks
     *
     * @return array
     */
    public function getSubscribedDelegates()
    {
        return array(
            array(
                'page'     => '/frontend/',
                'delegate' => 'FrontendOutputPreGenerate',
                'callback' => 'frontendOutputPreGenerate'
            ),
        );
    }
    
    /**
     * Register PHP functions
     *
     * @uses FrontendOutputPreGenerate
     * @param  string $context
     * @return void
     */
    public function frontendOutputPreGenerate($context)
    {
        $functions = array(
            'urlencode',
            'rawurlencode'
        );

        $context['page']->registerPHPFunction($functions);
    }
}


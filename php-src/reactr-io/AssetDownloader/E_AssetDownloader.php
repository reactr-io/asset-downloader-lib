<?php

namespace ReactrIO\AssetDownloader;

class E_AssetDownloader extends \RuntimeException
{
    // Unfortunately, PHP won't allow us to define the constructor as private, as RuntimeException's
    // constructor is public
    public static function create($message, $context=array(), $code=0, $previous=NULL)
    {
        if (!is_array($context)) $context= array();
        $context['error_msg'] = $context['msg'] = $message;

        $klass = get_called_class();

        return new $klass(json_encode($context), $code, $previous);
    }

    function getContext()
    {
        return json_decode(parent::getMessage(), TRUE);
    }

    function getErrMsg()
    {
        return $this->getContext()['msg'];
    }
}
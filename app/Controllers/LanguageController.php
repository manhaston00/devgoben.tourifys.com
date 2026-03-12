<?php

namespace App\Controllers;

class LanguageController extends BaseController
{
    public function switch($lang)
    {
        $allowed = ['th', 'en'];

        if (!in_array($lang, $allowed)) {
            $lang = 'th';
        }

        session()->set('site_locale', $lang);

        return redirect()->back();
    }
}
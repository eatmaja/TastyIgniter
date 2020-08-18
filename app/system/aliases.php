<?php

return [

    // Laravel aliases
    'App' => Illuminate\Support\Facades\App::class,
    'Artisan' => Illuminate\Support\Facades\Artisan::class,
    'Broadcast' => Illuminate\Support\Facades\Broadcast::class,
    'Bus' => Illuminate\Support\Facades\Bus::class,
    'Cache' => Illuminate\Support\Facades\Cache::class,
    'Config' => Illuminate\Support\Facades\Config::class,
    'Cookie' => Illuminate\Support\Facades\Cookie::class,
    'Crypt' => Illuminate\Support\Facades\Crypt::class,
    'DB' => Illuminate\Support\Facades\DB::class,
    'Eloquent' => Illuminate\Database\Eloquent\Model::class,
    'Event' => Illuminate\Support\Facades\Event::class,
    'Input' => Illuminate\Support\Facades\Input::class,
    'Hash' => Illuminate\Support\Facades\Hash::class,
    'Lang' => Illuminate\Support\Facades\Lang::class,
    'Log' => Illuminate\Support\Facades\Log::class,
    'Mail' => Illuminate\Support\Facades\Mail::class,
    'Queue' => Illuminate\Support\Facades\Queue::class,
    'Redirect' => Illuminate\Support\Facades\Redirect::class,
    'Redis' => Illuminate\Support\Facades\Redis::class,
    'Request' => Illuminate\Support\Facades\Request::class,
    'Response' => Illuminate\Support\Facades\Response::class,
    'Route' => Illuminate\Support\Facades\Route::class,
    'Schema' => Illuminate\Support\Facades\Schema::class,
    'Session' => Illuminate\Support\Facades\Session::class,
    'Storage' => Illuminate\Support\Facades\Storage::class,
    'URL' => Illuminate\Support\Facades\URL::class,
    'Validator' => Illuminate\Support\Facades\Validator::class,
    'View' => Illuminate\Support\Facades\View::class,

    // TastyIgniter aliases
    'Assets' => System\Facades\Assets::class,
    'Country' => System\Facades\Country::class,
    'File' => Igniter\Flame\Support\Facades\File::class,
    'Flash' => Igniter\Flame\Flash\Facades\Flash::class,
    'Form' => Igniter\Flame\Html\FormFacade::class,
    'Html' => Igniter\Flame\Html\HtmlFacade::class,
    'Model' => Igniter\Flame\Database\Model::class,
    'Parameter' => Igniter\Flame\Setting\Facades\Parameter::class,
    'Setting' => Igniter\Flame\Setting\Facades\Setting::class,
    'Str' => Igniter\Flame\Support\Str::class,

    'Admin' => Admin\Facades\Admin::class,
    'AdminAuth' => Admin\Facades\AdminAuth::class,
    'AdminLocation' => Admin\Facades\AdminLocation::class,
    'AdminMenu' => Admin\Facades\AdminMenu::class,
    'Auth' => Main\Facades\Auth::class,
    'Template' => Admin\Facades\Template::class,

    'SystemException' => Igniter\Flame\Exception\SystemException::class,
    'ApplicationException' => Igniter\Flame\Exception\ApplicationException::class,
    'AjaxException' => Igniter\Flame\Exception\AjaxException::class,
    'ValidationException' => Igniter\Flame\Exception\ValidationException::class,
];

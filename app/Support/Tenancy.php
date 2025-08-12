<?php

namespace App\Support;

class Tenancy
{
    public static function currentCompanyId()
    {
        return app()->bound('currentCompanyId') ? app('currentCompanyId') : null;
    }
}
<?php

namespace App\Models;

class Guide extends CmsModel
{

    public array $translatable = ['title', 'summary', 'content'];

    public array $jsonFields = [];

}

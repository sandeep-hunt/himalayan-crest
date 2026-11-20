<?php

namespace App\Models;

class Review extends CmsModel
{

    public array $translatable = ['country', 'trek_label', 'text'];

    public array $jsonFields = [];

}

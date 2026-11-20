<?php

namespace App\Models;

class Category extends CmsModel
{

    public array $translatable = ['title', 'description', 'link_label'];

    public array $jsonFields = [];

}

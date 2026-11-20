<?php

namespace App\Models;

class Faq extends CmsModel
{

    public array $translatable = ['question', 'answer'];

    public array $jsonFields = [];

}

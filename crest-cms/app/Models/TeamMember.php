<?php

namespace App\Models;

class TeamMember extends CmsModel
{
    protected $table = 'team_members';

    public array $translatable = ['designation', 'experience', 'regions', 'bio'];

    public array $jsonFields = [];

}

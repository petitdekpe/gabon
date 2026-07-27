<?php

namespace App\Entity\Enum;

enum RegistrantStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
}

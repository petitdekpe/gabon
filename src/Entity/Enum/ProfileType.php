<?php

namespace App\Entity\Enum;

enum ProfileType: string
{
    case Student = 'student';
    case Employee = 'employee';
    case Entrepreneur = 'entrepreneur';
}

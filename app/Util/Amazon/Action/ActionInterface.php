<?php

namespace App\Util\Amazon\Action;

interface ActionInterface
{
    public function run(): bool;
}
<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class Pipeline extends Page
{
    protected static ?string                 $navigationLabel = 'Pipeline';
    protected static \BackedEnum|string|null $navigationIcon  = 'heroicon-o-view-columns';
    protected static ?string                 $title           = 'Pipeline Comercial';
    protected static ?int                    $navigationSort  = 1;
    protected string                         $view            = 'filament.pages.pipeline';

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}

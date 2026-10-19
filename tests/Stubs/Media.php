<?php

declare(strict_types=1);

namespace Shopper\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;

/**
 * The stock tests boot the core alone, without the media library; they
 * only need the relation the delete observers clean up.
 */
class Media extends Model
{
    protected $guarded = [];

    protected $table = 'media';
}

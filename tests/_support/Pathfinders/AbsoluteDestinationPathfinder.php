<?php

/**
 * This file is part of Liaison Revision.
 *
 * (c) 2020 John Paul E. Balandan, CPA <paulbalandan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Support\Pathfinders;

use Liaison\Revision\Paths\AbstractPathfinder;

class AbsoluteDestinationPathfinder extends AbstractPathfinder
{
    protected $paths = [
        [
            'origin'      => SYSTEMPATH . '../spark',
            'destination' => ROOTPATH . 'spark',
        ],
    ];
}

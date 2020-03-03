<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Rysev Alexander
 * @email angelojack75@yahoo.com
 * @version 1.0
 * @date 03/03/2020 02:35
 */

namespace Modules\Admin\Contrib;

/**
 * Interface SingleAdminModelInterface
 * @package Modules\Admin\Contrib
 */
interface SingleAdminModelInterface
{
    /**
     * Modele verbose (human-readable) name
     * @return mixed
     */
    public function getVerboseName();

    /**
     * Triggered after single model update
     */
    public function afterSettingsUpdate();
}
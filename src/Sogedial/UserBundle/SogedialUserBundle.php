<?php

namespace Sogedial\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class SogedialUserBundle extends Bundle {

    public function getParent() {
        return 'FOSUserBundle';
    }

}

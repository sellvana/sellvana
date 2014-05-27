<?php defined('BUCKYBALL_ROOT_DIR') || die();

namespace MtHaml\Node;

use MtHaml\Escaping;

abstract class EscapableAbstract extends NodeAbstract
{
    private $escaping;

    public function getEscaping()
    {
        if (null === $this->escaping) {
            $this->escaping = new Escaping;
        }
        return $this->escaping;
    }
}


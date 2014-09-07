<?php defined('BUCKYBALL_ROOT_DIR') || die();

namespace MtHaml\Node;

interface NestInterface
{
    public function addChild(NodeAbstract $child);
    public function hasContent();
    public function allowsNestingAndContent();
}


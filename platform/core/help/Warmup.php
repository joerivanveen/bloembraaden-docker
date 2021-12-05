<?php

namespace Peat;
/**
 * Class Warmup
 * @package Peat
 */
class Warmup extends BaseLogic
{
    private array $slugs = array();

    /**
     * constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function Warmup(string $slug, int $instance_id): bool
    {
        if (isset($this->slugs[$slug])) return false; // no need to warmup more than once
        $this->slugs[$slug] = true;
        // the slug for cache may contain spaces and other non-slug elements, so slugify is out of the question
        //$slug = implode('/', array_map('rawurlencode', explode('/', $slug)));
        $resolver = new Resolver($slug, $instance_id);
        if (true === $resolver->hasInstructions()) {
            $this->addMessage(sprintf(__('‘%s’ is never cached', 'peatcms'), $slug), 'note');

            return false;
        }
        $element = $resolver->getElement($from_history, null, true);
        // if the cached element now has a different slug, you can remove the old version safely
        if ($from_history) {
            $this->getDB()->deleteFromCache($slug);
        } elseif ($element instanceof BaseElement) {
            if ($slug !== $element->getPath()) $this->getDB()->deleteFromCache($slug);
        }

        return (bool)$element->cacheOutputObject(false);
    }
}
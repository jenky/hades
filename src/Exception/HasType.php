<?php

namespace Jenky\Hades\Exception;

trait HasType
{
    /**
     * @var string
     */
    protected $type;

    /**
     * Get exception type.
     *
     * @param  string
     * @return $this
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Get exception type.
     */
    public function getType(): string
    {
        return $this->type;
    }
}

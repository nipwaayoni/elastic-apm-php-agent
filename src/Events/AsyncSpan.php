<?php


namespace Nipwaayoni\Events;

class AsyncSpan extends Span
{
    /** @var bool  */
    protected $isBlocking = false;
}

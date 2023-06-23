<?php

namespace ABTesting\Exception;

class AbTestingException extends \Exception
{
    public const INCOMPLETE_ANNOTATION = 1;
    public const UNDEFINED_TEST_CODE = 0;
}

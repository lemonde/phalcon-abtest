<?php

namespace ABTesting\Exception;

class AbTestingException extends \Exception
{
    public const int INCOMPLETE_ANNOTATION = 1;
    public const int UNDEFINED_TEST_CODE = 0;
}

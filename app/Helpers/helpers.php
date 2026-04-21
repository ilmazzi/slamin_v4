<?php

use Domain\Audit\Support\AuditLogger;

if (! function_exists('audit')) {
    function audit(): AuditLogger
    {
        return app(AuditLogger::class);
    }
}

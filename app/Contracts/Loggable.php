<?php

namespace App\Contracts;

interface Loggable
{
    /**
     * How this record should be named in the activity log.
     *
     * Stored as a plain string on the entry rather than looked up later, so it
     * still reads sensibly once the record itself is gone.
     */
    public function activityLabel(): string;
}

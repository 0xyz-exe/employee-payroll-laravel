<?php

return [
    // Minor currency units per overtime hour. Set to 0 unless an approved local formula exists.
    'overtime_minor_per_hour' => (int) env('PAYROLL_OVERTIME_MINOR_PER_HOUR', 0),
];

<?php
namespace App\Observers;

use App\Models\CrimeReport;
use App\Models\AuditLog;

class CrimeReportObserver
{
    public function created(CrimeReport $crimeReport)
    {
        AuditLog::logCrimeReport($crimeReport);
    }

    public function updated(CrimeReport $crimeReport)
    {
        if ($crimeReport->isDirty('status')) {
            AuditLog::logEvent('status_changed', $crimeReport, 
                ['status' => $crimeReport->getOriginal('status')],
                ['status' => $crimeReport->status]
            );
        }
    }
}
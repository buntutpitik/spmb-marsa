<?php

namespace App\Exports;

use App\Exports\Sheets\PeriodComparisonAdmissionPathSheet;
use App\Exports\Sheets\PeriodComparisonDataSourceSheet;
use App\Exports\Sheets\PeriodComparisonFinanceSheet;
use App\Exports\Sheets\PeriodComparisonGenderSheet;
use App\Exports\Sheets\PeriodComparisonMajorSheet;
use App\Exports\Sheets\PeriodComparisonOriginSchoolSheet;
use App\Exports\Sheets\PeriodComparisonReferralSheet;
use App\Exports\Sheets\PeriodComparisonStatusSheet;
use App\Exports\Sheets\PeriodComparisonSummarySheet;
use App\Exports\Sheets\PeriodComparisonTrendSheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Sheets\PeriodComparisonRegistrationDayTrendSheet;

class PeriodComparisonExport implements Export, WithMultipleSheets
{
    public function __construct(
        private readonly array $comparison
    ) {
    }

    public function sheets(): array
    {
        return [
            new PeriodComparisonSummarySheet($this->comparison),
            new PeriodComparisonStatusSheet($this->comparison),
            new PeriodComparisonMajorSheet($this->comparison),
            new PeriodComparisonGenderSheet($this->comparison),
            new PeriodComparisonAdmissionPathSheet($this->comparison),
            new PeriodComparisonDataSourceSheet($this->comparison),
            new PeriodComparisonOriginSchoolSheet($this->comparison),
            new PeriodComparisonReferralSheet($this->comparison),
            new PeriodComparisonTrendSheet($this->comparison),
            new PeriodComparisonRegistrationDayTrendSheet(
                $this->comparison
            ),
            new PeriodComparisonFinanceSheet($this->comparison),
        ];
    }
}
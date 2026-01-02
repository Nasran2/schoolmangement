<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Application for Admission</title>
    <style>
        @page { margin: 10mm 10mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0b1b3a; }

        .page { background: #e9f6ff; border: 2px solid #3950b6; padding: 10px; }

        .header { display: table; width: 100%; }
        .header .cell { display: table-cell; vertical-align: middle; }
        .logo { width: 85px; }
        .logo img { width: 75px; height: auto; }
        .school { text-align: center; }
        .school .name { font-size: 26px; font-weight: 800; color: #1f2e8a; line-height: 1.05; }
        .school .bar { margin-top: 8px; background: #1f2e8a; color: #fff; font-size: 18px; font-weight: 800; padding: 4px 10px; display: inline-block; }

        .photo-box { width: 140px; text-align: center; border: 2px solid #3950b6; background: #ffffff; padding: 8px; font-size: 11px; color: #6b7280; }

        .section-title { text-align: center; font-weight: 800; color: #1f2e8a; text-decoration: underline; margin: 10px 0 6px; }

        .note { margin: 0 0 8px 0; font-weight: 700; color: #1f2e8a; }

        .row { margin: 6px 0; }
        .label { font-weight: 800; color: #1f2e8a; }

        .line { display: inline-block; border: 2px solid #3950b6; background: #ffffff; height: 20px; vertical-align: middle; }
        .line.big { width: 100%; }
        .line.medium { width: 75%; }
        .line.small { width: 120px; }

        .boxes { display: inline-block; vertical-align: middle; }
        .box { display: inline-block; width: 18px; height: 18px; border: 2px solid #3950b6; background: #ffffff; margin-right: 4px; text-align: center; line-height: 18px; font-weight: 800; }

        .table { width: 100%; border-collapse: collapse; }
        .table td, .table th { border: 2px solid #3950b6; background: #ffffff; padding: 6px; vertical-align: top; }
        .table th { background: #1f2e8a; color: #ffffff; font-weight: 800; text-align: center; }

        .subhead { font-weight: 800; color: #1f2e8a; }

        .yn-box { display: inline-block; width: 28px; height: 18px; border: 2px solid #3950b6; background: #ffffff; text-align: center; line-height: 18px; font-weight: 800; margin-left: 6px; }

        .page-no { text-align: center; margin-top: 8px; }
        .page-no span { background: #1f2e8a; color: #fff; padding: 4px 18px; font-weight: 800; }

        .page-break { page-break-after: always; }

        .muted { color: #6b7280; }
    </style>
</head>
<body>
@php
    $studentNameWithInitial = $student->name_with_initial ?? $student->name ?? '';
    $dobDay = $student->date_of_birth ? $student->date_of_birth->format('d') : '';
    $dobMonth = $student->date_of_birth ? $student->date_of_birth->format('m') : '';
    $dobYear = $student->date_of_birth ? $student->date_of_birth->format('Y') : '';

    $gender = strtolower((string)($student->gender ?? ''));
    $male = $gender === 'male';
    $female = $gender === 'female';

    $yes = fn ($v) => ((string)$v === '1' || $v === true);
@endphp

<!-- PAGE 1 -->
<div class="page">
    <div class="header">
        <div class="cell logo">
            @if(!empty($schoolLogoDataUri))
                <img src="{{ $schoolLogoDataUri }}" alt="Logo" />
            @endif
        </div>
        <div class="cell school">
            <div class="name">{{ $schoolName }}</div>
            <div class="bar">Application for Admission</div>
        </div>
        <div class="cell" style="width:150px; text-align:right;">
            <div class="photo-box">
                <div class="muted">Passport Sized</div>
                <div class="muted">Colour</div>
                <div class="muted">Photograph with</div>
                <div class="muted">Blue or White</div>
                <div class="muted">Background</div>
            </div>
        </div>
    </div>

    <div class="section-title">For office use only:</div>

    <table class="table" style="margin-bottom:10px;">
        <tr>
            <td style="width:40%;">
                <span class="label">Application - Accepted</span>
                <span class="box"></span>
                &nbsp;&nbsp;
                <span class="label">Rejected</span>
                <span class="box"></span>
            </td>
            <td style="width:60%;">
                <span class="label">Student’s Admission No:</span>
                <span class="boxes">
                    @php
                        $adm = (string)($student->admission_number ?? '');
                    @endphp
                    @for($i=0;$i<6;$i++)
                        <span class="box">{{ $adm !== '' && isset($adm[$i]) ? e($adm[$i]) : '' }}</span>
                    @endfor
                </span>
            </td>
        </tr>
    </table>

    <div class="section-title">Details of Applicant</div>
    <div class="note">• Write in block capitals and leave a space between two words.</div>

    <div class="row">
        <span class="label">01). First Name:</span>
        <div class="line big">{{ $student->first_name ?? '' }}</div>
    </div>

    <div class="row">
        <span class="label">02). Other Names :</span>
        <div class="line big">{{ $student->other_names ?? '' }}</div>
    </div>

    <div class="row">
        <span class="label">03). Name With Initial :</span>
        <div class="line big">{{ $studentNameWithInitial }}</div>
    </div>

    <div class="row">
        <span class="label">04). Date of Birth:</span>
        &nbsp; <span class="label">Date</span> <span class="box">{{ $dobDay }}</span>
        &nbsp; <span class="label">Month</span> <span class="box">{{ $dobMonth }}</span>
        &nbsp; <span class="label">Year</span> <span class="boxes">
            @for($i=0;$i<4;$i++)
                <span class="box">{{ $dobYear !== '' && isset($dobYear[$i]) ? e($dobYear[$i]) : '' }}</span>
            @endfor
        </span>
        &nbsp;&nbsp; <span class="label">Sex:-</span>
        &nbsp; <span class="label">Male</span> <span class="box">{{ $male ? '✓' : '' }}</span>
        &nbsp; <span class="label">Female</span> <span class="box">{{ $female ? '✓' : '' }}</span>
    </div>

    <div class="row">
        <span class="label">05). Permanent Address of Parent or Guardian:</span>
        <table class="table" style="margin-top:6px;">
            <tr><td style="height:36px;">{{ $student->parent_address ?? '' }}</td></tr>
            <tr><td style="height:36px;"></td></tr>
        </table>
    </div>

    <div class="row">
        <span class="label">06). Religion of the Child:</span>
        <div class="line big">{{ $student->religion ?? '' }}</div>
    </div>

    <div class="row">
        <span class="label">07). Class the parent wishes the child to be admitted</span>
        <span class="line small">{{ $student->desired_class ?? '' }}</span>
    </div>

    <div class="row">
        <span class="label">08). Medical History of child</span>
        <table class="table" style="margin-top:6px;">
            <tr>
                <td>
                    <div class="subhead">a). Is the child receiving any long term / Permanent Medication</div>
                    <div style="margin-top:4px;">
                        <span class="label">Yes</span> <span class="yn-box">{{ $yes($student->long_term_medication ?? false) ? '✓' : '' }}</span>
                        &nbsp;&nbsp;
                        <span class="label">No</span> <span class="yn-box">{{ !$yes($student->long_term_medication ?? false) ? '✓' : '' }}</span>
                    </div>
                    <div style="margin-top:10px;" class="subhead">b). Has the child been diagnosed with any learning disabilities</div>
                    <div style="margin-top:4px;">
                        <span class="label">Yes</span> <span class="yn-box">{{ $yes($student->learning_disabilities ?? false) ? '✓' : '' }}</span>
                        &nbsp;&nbsp;
                        <span class="label">No</span> <span class="yn-box">{{ !$yes($student->learning_disabilities ?? false) ? '✓' : '' }}</span>
                    </div>
                    @if(!empty($student->medical_history))
                        <div style="margin-top:10px;"><span class="label">Notes:</span> {{ $student->medical_history }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="row">
        <span class="label">09). Previous school of child and the grade Studying :</span>
        <table class="table" style="margin-top:6px;">
            <tr>
                <td style="width:28%;" class="label">School Name</td>
                <td>{{ $student->previous_school ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">Grade Studied</td>
                <td>{{ $student->previous_grade ?? '' }}</td>
            </tr>
        </table>
    </div>

    <div class="row">
        <span class="label">10). Siblings of child</span>
        <table class="table" style="margin-top:6px;">
            <tr>
                <td>
                    <div class="subhead">a). Does the child have brothers or sisters at this college</div>
                    <div style="margin-top:4px;">
                        <span class="label">Yes</span> <span class="yn-box">{{ $yes($student->has_siblings_in_college ?? false) ? '✓' : '' }}</span>
                        &nbsp;&nbsp;
                        <span class="label">No</span> <span class="yn-box">{{ !$yes($student->has_siblings_in_college ?? false) ? '✓' : '' }}</span>
                    </div>
                    <div style="margin-top:10px;" class="subhead">b). If yes, Name / Grade</div>
                    <div style="margin-top:6px;" class="line big">{{ $student->siblings ?? '' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="page-no"><span>Page - 01</span></div>
</div>

<div class="page-break"></div>

<!-- PAGE 2 -->
<div class="page">
    <div class="header">
        <div class="cell logo">
            @if(!empty($schoolLogoDataUri))
                <img src="{{ $schoolLogoDataUri }}" alt="Logo" />
            @endif
        </div>
        <div class="cell school">
            <div class="name">{{ $schoolName }}</div>
            <div class="bar">Application for Admission</div>
        </div>
        <div class="cell" style="width:150px;"></div>
    </div>

    <div class="row" style="margin-top:10px;">
        <span class="label">11). Information of Parent / Guardian</span>
    </div>

    @if($student->use_guardian)
        <table class="table" style="margin: 8px 0 12px;">
            <tr>
                <th colspan="2">Guardian</th>
            </tr>
            <tr>
                <td class="label" style="width:35%;">Name</td>
                <td>{{ $student->guardian_name ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">Relationship</td>
                <td>{{ $student->guardian_relationship ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">Phone / Mobile</td>
                <td>{{ $student->guardian_phone ?? '' }}</td>
            </tr>
        </table>
    @endif

    <table class="table" style="margin-bottom: 12px;">
        <tr>
            <th colspan="2">Father</th>
        </tr>
        <tr><td class="label" style="width:35%;">Name with Initial</td><td>{{ $student->father_name_with_initial ?? '' }}</td></tr>
        <tr><td class="label">N.I.C / Passport Number</td><td>{{ $student->father_nic_passport ?? '' }}</td></tr>
        <tr><td class="label">Religion</td><td>{{ $student->father_religion ?? '' }}</td></tr>
        <tr><td class="label">Nationality</td><td>{{ $student->father_nationality ?? '' }}</td></tr>
        <tr><td class="label">Occupation</td><td>{{ $student->father_occupation ?? '' }}</td></tr>
        <tr><td class="label">Phone / Mobile</td><td>{{ $student->father_phone ?? '' }}</td></tr>
        <tr><td class="label">Whats app Number</td><td>{{ $student->father_whatsapp ?? '' }}</td></tr>
        <tr><td class="label">Office Telephone Number</td><td>{{ $student->father_office_phone ?? '' }}</td></tr>
        <tr><td class="label">Number incase of Emergency</td><td>{{ $student->father_emergency_number ?? '' }}</td></tr>
    </table>

    <table class="table" style="margin-bottom: 12px;">
        <tr>
            <th colspan="2">Mother</th>
        </tr>
        <tr><td class="label" style="width:35%;">Name with Initial</td><td>{{ $student->mother_name_with_initial ?? '' }}</td></tr>
        <tr><td class="label">N.I.C / Passport Number</td><td>{{ $student->mother_nic_passport ?? '' }}</td></tr>
        <tr><td class="label">Religion</td><td>{{ $student->mother_religion ?? '' }}</td></tr>
        <tr><td class="label">Nationality</td><td>{{ $student->mother_nationality ?? '' }}</td></tr>
        <tr><td class="label">Occupation</td><td>{{ $student->mother_occupation ?? '' }}</td></tr>
        <tr><td class="label">Phone / Mobile</td><td>{{ $student->mother_phone ?? '' }}</td></tr>
        <tr><td class="label">Whats app Number</td><td>{{ $student->mother_whatsapp ?? '' }}</td></tr>
        <tr><td class="label">Office Telephone Number</td><td>{{ $student->mother_office_phone ?? '' }}</td></tr>
        <tr><td class="label">Number incase of Emergency</td><td>{{ $student->mother_emergency_number ?? '' }}</td></tr>
    </table>

    <table class="table" style="margin-bottom: 12px;">
        <tr>
            <td style="background:#e9f6ff; font-weight:800; color:#1f2e8a;">
                • I certify that the above particulars furnished by me are correct. I agree to abide by the rules and regulations of the college.
            </td>
        </tr>
        <tr>
            <td style="background:#e9f6ff;">
                <div style="text-align:center;">
                    <div style="display:inline-block; width:230px; height:60px; border:2px solid #3950b6; background:#ffffff;"></div>
                    <div style="margin-top:6px; font-weight:800; color:#1f2e8a;">Signature of Parent/Guardian</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">For office use only:</div>
    <table class="table">
        <tr>
            <td style="width:30%; background:#e9f6ff; font-weight:800; color:#1f2e8a;">Admitted to grade</td>
            <td style="width:25%;"></td>
            <td style="width:25%; background:#e9f6ff; font-weight:800; color:#1f2e8a;">Admission Number</td>
            <td style="width:20%;">{{ $student->admission_number ?? '' }}</td>
        </tr>
        <tr>
            <td style="background:#e9f6ff; font-weight:800; color:#1f2e8a;">Date</td>
            <td></td>
            <td style="background:#e9f6ff; font-weight:800; color:#1f2e8a;">Administrative Director/Directress</td>
            <td></td>
        </tr>
    </table>

    <div class="page-no"><span>Page - 02</span></div>
</div>

</body>
</html>

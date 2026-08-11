<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Student Individual Inventory - {{ $submission->user->student_id }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Dancing+Script&display=swap');
        
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.4;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .mb-1 { margin-bottom: 5px; }
        .mb-2 { margin-bottom: 10px; }
        .mb-4 { margin-bottom: 20px; }
        .mt-4 { margin-top: 20px; }
        .mt-8 { margin-top: 40px; }
        
        .header { border-bottom: 2px solid #2d3748; padding-bottom: 10px; margin-bottom: 20px; }
        .logo { max-width: 80px; }
        
        .section-title {
            background-color: #f7fafc;
            border-left: 4px solid #4a5568;
            padding: 5px 10px;
            font-size: 14px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { padding: 8px; border: 1px solid #e2e8f0; vertical-align: top; }
        th { background-color: #f7fafc; text-align: left; width: 30%; }
        
        .info-table th { width: 25%; }
        
        .response-table th { width: 70%; background-color: #fff; border: 1px solid #cbd5e0; }
        .response-table td { width: 30%; text-align: center; border: 1px solid #cbd5e0; }
        .response-table thead th { background-color: #edf2f7; text-align: left; }
        .response-table thead td { background-color: #edf2f7; font-weight: bold; }
        
        .page-break { page-break-after: always; }
        
        .signature-box {
            border: 1px solid #cbd5e0;
            padding: 15px;
            text-align: center;
            width: 250px;
            margin-top: 10px;
        }
        
        .typed-signature {
            font-size: 28px;
            font-family: 'Dancing Script', 'Times New Roman', cursive, serif;
        }
        
        .drawn-signature {
            max-width: 100%;
            max-height: 80px;
        }

        .footer {
            position: fixed; 
            bottom: -30px; 
            left: 0px; 
            right: 0px;
            height: 30px; 
            font-size: 10px;
            color: #718096;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }
        .page-number:after { content: counter(page); }

        .dass-note {
            font-size: 10px;
            color: #718096;
            font-style: italic;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="footer">
        Submitted at: {{ $submission->submitted_at->format('F d, Y h:i A') }} | Page <span class="page-number"></span>
    </div>

    <div class="header text-center">
        <!-- Assume a logo exists at public/images/guidance-logo.png -->
        <h2 class="mb-1">CCT Guidance and Counseling Services Unit</h2>
        <h1 class="mb-1">Student Individual Inventory</h1>
        <p>Academic Year: <strong>{{ $submission->academic_year }}</strong> | Year Level: <strong>{{ $submission->user->year_level }} Year</strong></p>
    </div>

    <div class="section-title">Student Information</div>
    <table class="info-table">
        <tr>
            <th>Name</th>
            <td>{{ $submission->user->last_name }}, {{ $submission->user->first_name }} {{ $submission->user->middle_initial }}</td>
            <th>Student ID</th>
            <td>{{ $submission->user->student_id }}</td>
        </tr>
        <tr>
            <th>Program</th>
            <td>
                @if($submission->user->structuredProgram)
                    {{ $submission->user->structuredProgram->department->name ?? '' }} - {{ $submission->user->structuredProgram->name }}
                @else
                    {{ $submission->user->program }}
                @endif
            </td>
            <th>Section</th>
            <td>{{ $submission->user->section }}</td>
        </tr>
        <tr>
            <th>Email</th>
            <td>{{ $submission->user->email }}</td>
            <th>Contact Number</th>
            <td>{{ $submission->user->contact_number ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Birthdate</th>
            <td colspan="3">{{ $submission->user->birthdate ? \Carbon\Carbon::parse($submission->user->birthdate)->format('F d, Y') : 'N/A' }}</td>
        </tr>
    </table>

    <div class="section-title">Data Privacy Consent</div>
    <div style="border: 1px solid #e2e8f0; padding: 15px; margin-bottom: 20px; background-color: #f7fafc;">
        <p class="mb-2"><strong>Data Privacy Agreement (v{{ $submission->consent_version }})</strong></p>
        <p class="mb-4" style="font-size: 11px;">
            The City College of Tagaytay (CCT) Guidance and Counseling Services Unit is committed to protecting your privacy... 
            (Student consented to the Data Privacy Agreement presented during the inventory process.)
        </p>
        
        <p class="mb-4 font-bold">✓ I have read, understood, and agree to the terms of the Data Privacy Agreement.</p>
        
        <p class="mb-1"><strong>Consent Given At:</strong> {{ $submission->consent_given_at ? \Carbon\Carbon::parse($submission->consent_given_at)->format('F d, Y h:i A') : 'N/A' }}</p>
        
        <div class="signature-box mt-4">
            @if($submission->signature_type === 'drawn' && $submission->signature_data)
                <img src="{{ $submission->signature_data }}" class="drawn-signature" alt="Signature">
            @elseif($submission->signature_type === 'typed' && $submission->signature_data)
                <div class="typed-signature">{{ $submission->signature_data }}</div>
            @else
                <div>No signature recorded</div>
            @endif
            <div style="border-top: 1px solid #cbd5e0; margin-top: 5px; padding-top: 5px; font-size: 10px;">
                Student Signature
            </div>
        </div>
    </div>

    <div class="page-break"></div>

    <div class="header text-center">
        <h2 class="mb-1">Inventory Responses</h2>
        <p>Student: {{ $submission->user->last_name }}, {{ $submission->user->first_name }} | ID: {{ $submission->user->student_id }}</p>
    </div>

    @foreach($categories as $category)
        @if($responsesByCategory->has($category->name))
            <div class="section-title">{{ $category->name }}</div>
            
            @if($category->instructions)
                <p class="mb-2" style="font-size: 11px; font-style: italic;">{{ $category->instructions }}</p>
            @endif

            <table class="response-table">
                <thead>
                    <tr>
                        <th>Item Prompt</th>
                        <td>Response</td>
                    </tr>
                </thead>
                <tbody>
                    @foreach($responsesByCategory->get($category->name)->sortBy('item_number') as $response)
                        <tr>
                            <th>{{ $response->item_number }}. {{ $response->questionItem->prompt ?? 'Unknown Item' }}</th>
                            <td>
                                @if($category->scale_type === 'Learning Style')
                                    {{ $response->response_value }}
                                @else
                                    {{ $response->response_value }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach

    <div class="page-break"></div>

    <div class="header text-center">
        <h2 class="mb-1">Computed Scores</h2>
        <p>Student: {{ $submission->user->last_name }}, {{ $submission->user->first_name }} | ID: {{ $submission->user->student_id }}</p>
    </div>

    @foreach($scoresByCategory as $categoryName => $scores)
        <div class="section-title">{{ $categoryName }}</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 40%;">Subscale / Metric</th>
                    <th style="width: 20%;">Raw Score</th>
                    @if(str_contains(strtolower($categoryName), 'dass'))
                        <th style="width: 20%;">Scaled Score</th>
                        <th style="width: 20%;">Severity Label</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($scores as $score)
                    <tr>
                        <td>{{ $score->subscale ?: 'Total' }}</td>
                        <td>{{ $score->score }}</td>
                        @if(str_contains(strtolower($categoryName), 'dass'))
                            <td>{{ $score->score * 2 }}</td>
                            <td class="font-bold">{{ $score->severity_label ?? 'N/A' }}</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if(str_contains(strtolower($categoryName), 'dass'))
            <p class="dass-note">* Note: DASS-21 severity labels reflect symptom severity over the past week and do not constitute a clinical diagnosis.</p>
        @endif
    @endforeach

</body>
</html>

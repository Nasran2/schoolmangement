<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Students Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 20px; }
        .school-name { font-size: 18px; font-weight: bold; margin-bottom: 5px; }
        .report-title { font-size: 14px; font-weight: bold; margin-bottom: 5px; text-transform: uppercase; }
        .meta { font-size: 11px; color: #555; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background-color: #f4f4f4; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge-active { color: #166534; font-weight: bold; }
        .badge-inactive { color: #991b1b; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-name">{{ config('app.name') }}</div>
        <div class="report-title">Students Report</div>
        <div class="meta">
            @php
                $className = 'All Classes';
                if (!empty($filters['class_room_id'])) {
                    $class = $classRooms->firstWhere('id', $filters['class_room_id']);
                    if ($class) $className = $class->name;
                }
            @endphp
            Class: {{ $className }}<br>
            Generated: {{ now()->format('Y-m-d H:i A') }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="15%">Admission No</th>
                <th width="30%">Name</th>
                <th width="15%">Grade/Class</th>
                <th width="15%">Phone</th>
                <th width="10%">Joined Date</th>
                <th width="10%" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->admission_number ?? $item->id }}</td>
                <td>{{ $item->name }}</td>
                <td>{{ $item->classRoom?->name ?? '-' }}</td>
                <td>{{ $item->phone ?? '-' }}</td>
                <td>{{ optional($item->joining_date)->format('Y-m-d') ?? '-' }}</td>
                <td class="text-center">
                    @if($item->active)
                        <span class="badge-active">Active</span>
                    @else
                        <span class="badge-inactive">Inactive</span>
                    @endif
                </td>
            </tr>
            @endforeach
            @if($items->isEmpty())
            <tr>
                <td colspan="7" class="text-center">No students found.</td>
            </tr>
            @endif
        </tbody>
    </table>

</body>
</html>

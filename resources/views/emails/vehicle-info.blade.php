<!DOCTYPE html>
<html>
<head>
    <title>Vehicle Information</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 15px;
            text-align: center;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .vehicle-info {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .vehicle-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .vehicle-info table th {
            text-align: left;
            padding: 10px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #ddd;
        }
        .vehicle-info table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .footer {
            font-size: 12px;
            text-align: center;
            margin-top: 30px;
            color: #777;
        }
        .active-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        .active-yes {
            background-color: #28a745;
        }
        .active-no {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Vehicle Information</h2>
        <p>Details for vehicle with license plate: <strong>{{ $license_plate }}</strong></p>
    </div>

    <div class="vehicle-info">
        <table>
            <tr>
                <th>License Plate</th>
                <td>{{ $license_plate }}</td>
            </tr>
            <tr>
                <th>Lot Name</th>
                <td>{{ $lot_name }}</td>
            </tr>
            <tr>
                <th>Permit ID</th>
                <td>{{ $permit_id ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>{{ $status ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Start Date/Time</th>
                <td>{{ $start_date }}</td>
            </tr>
            <tr>
                <th>End Date/Time</th>
                <td>{{ $end_date }}</td>
            </tr>
            <tr>
                <th>Duration (Hours)</th>
                <td>{{ $duration_hours ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Active</th>
                <td>
                    <span class="active-badge {{ $is_active == 'Yes' ? 'active-yes' : 'active-no' }}">
                        {{ $is_active }}
                    </span>
                </td>
            </tr>
            @if(isset($notes) && !empty($notes))
            <tr>
                <th>Notes</th>
                <td>{{ $notes }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="footer">
        <p>This is an automated email. Please do not reply to this message.</p>
        {{-- <p>&copy; {{ date('Y') }} Vehicle Management System. All rights reserved.</p> --}}
    </div>
</body>
</html>

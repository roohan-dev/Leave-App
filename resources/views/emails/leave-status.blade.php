@component('mail::message')
# Leave Request Status

Dear {{ $leave->user->name }},

Your leave request has been **{{ $leave->status }}**.

@component('mail::panel')
- Start Date: {{ $leave->start_date->format('d M, Y') }}
- End Date: {{ $leave->end_date->format('d M, Y') }}
- Type: {{ ucfirst($leave->type) }}
@endcomponent

@component('mail::button', ['url' => $url, 'color' => 'success'])
View Details
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
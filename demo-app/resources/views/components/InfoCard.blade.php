<div style="background: #ecf0f1; padding: 20px; border-radius: 8px; margin: 20px 0;">
    {!! $slot !!}

    @if(isset($slots['footer']))
    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #bdc3c7; color: #7f8c8d; font-size: 0.9em;">
        {!! $slots['footer'] !!}
    </div>
    @endif
</div>

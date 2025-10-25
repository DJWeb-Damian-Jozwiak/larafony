<span style="
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.85em;
    font-weight: bold;
    @if($status === 'success')
        background: #d4edda;
        color: #155724;
    @elseif($status === 'warning')
        background: #fff3cd;
        color: #856404;
    @elseif($status === 'error')
        background: #f8d7da;
        color: #721c24;
    @else
        background: #d1ecf1;
        color: #0c5460;
    @endif
    @if(!$active)
        opacity: 0.5;
        text-decoration: line-through;
    @endif
">
    {!! $slot !!}
</span>

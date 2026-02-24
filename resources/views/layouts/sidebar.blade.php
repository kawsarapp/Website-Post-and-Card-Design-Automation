@php
    $userPerms = is_array(auth()->user()->permissions) ? auth()->user()->permissions : json_decode(auth()->user()->permissions, true) ?? [];
@endphp

@if(in_array('can_manage_staff', $userPerms))
    <a href="{{ route('client.staff.index') }}" class="nav-link">
        <i class="fa-solid fa-users"></i> Staff Management
    </a>
@endif
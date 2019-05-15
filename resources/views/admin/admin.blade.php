@extends('layouts.admin_dashboard')

@section('content')
<!-- Dashboard 2 Header -->
<div class="content-header">
    <ul class="nav-horizontal text-left">
        <li class="active">
            <a href="{{url('/admin/manage-users')}}"><h3>{{$users_count}}</h3>Total Users</a>
        </li>
        <li class="active">
            <a href="{{url('/admin/manage-users')}}"><h3>{{$stylist_count}}</h3>Total Stylists</a>
        </li>
        <li class="active">
            <a href="{{url('/admin/manage-bookings')}}"><h3>{{$booking_count}}</h3>Total Bookings</a>
        </li>
        <li class="active">
            <a href="#"><h3>AED {{$total_earing}}</h3>Total Earning</a>
        </li>

    </ul>
</div>
<!-- END Dashboard 2 Header -->
@endsection

@extends('layouts.admin_dashboard')

@section('content')

<!-- Datatables Header -->
<div class="content-header">
    <div class="header-section">
        <h1>
            <i class="fa fa-table"></i>Booking Management<br><small>You can manage your entire registered bookings!</small>
        </h1>
    </div>
</div>
<ul class="breadcrumb breadcrumb-top">
    <li>Bookings</li>
    <li><a href="{{url('admin/manage-bookings')}}">Manage Booking</a></li>
</ul>
<!-- END Datatables Header -->

<!-- Datatables Content -->
<div class="block full">
    <div class="block-title">
        <h2><strong>Manage</strong> Bookings</h2>
    </div>

    @if(session()->has('success'))
    <div class="alert alert-success">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        {{ session()->get('success') }}
    </div>
    @endif
    <div class="table-responsive">
        <table id="example-datatable" class="table table-vcenter table-condensed table-bordered">
            <thead>
                <tr>
                    <th class="text-center">ID</th>
                    <th>Stylist Name</th>
                    <th>User Name</th>
                    <th>Booking Title</th>
                    <th>Booking Status</th>
                    <th>Meeting Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @php
                $i = 1
                @endphp
                @foreach($all_booking as $allUser)
                @php 
                $encodedUserId = encrypt($allUser->id) ;
                $stylist_data=DB::table('users')->where('id',$allUser->stylist_id)->first();
                $user_data=DB::table('users')->where('id',$allUser->user_id)->first();
                @endphp
                <tr>
                    <td class="text-center">{{$i}}</td>
                    <td>{{$stylist_data->name.' '.$stylist_data->last_name}}</td>
                    <td>{{$user_data->name.' '.$user_data->last_name}}</td>
                    <td>{{$allUser->booking_title}}</td>
                    <td>
                        @if($allUser->booking_status=='0')
                        <button type="button" class="btn btn-info">Under Discussion</button>
                        @else
                        <button type="button" class="btn btn-success">Processed</button>
                        @endif
                       
                    </td>
                    <td>
                        <button type="button" class="btn btn-success">{{$allUser->meeting_status}}</button>
                    </td>
                    
<!--                    <td><span title="click to change status" onclick="window.location.href='{{url('admin/update-booking-status/'.$encodedUserId)}}'" class="label {{$allUser->status=='1' ? 'label-success' : 'label-danger'}}">{{$allUser->status=='1' ? 'Click to Deactive' : 'Click to Activate'}}</span></td>                    -->
                    <td class="text-center">
                        <div class="btn-group">
                            <a href="javascript:void(0)" onclick="window.location.href='{{url('admin/view-booking/'.$encodedUserId)}}'" data-toggle="tooltip" title="View Detail" class="btn btn-xs btn-default"><i class="fa fa-eye"></i></a>
                            <!--<a href="javascript:void(0)" onclick="window.location.href='{{url('admin/edit-terms/'.$encodedUserId)}}'" data-toggle="tooltip" title="Edit" class="btn btn-xs btn-default"><i class="fa fa-pencil"></i></a>-->
                            <a href="javascript:void(0)" onclick="window.location.href='{{url('admin/delete-booking/'.$encodedUserId)}}'" data-toggle="tooltip" title="Delete" class="btn btn-xs btn-danger"><i class="fa fa-times"></i></a>
                        </div>
                    </td>
                </tr>
                 @php $i++; @endphp
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<!-- END Datatables Content -->

@endsection

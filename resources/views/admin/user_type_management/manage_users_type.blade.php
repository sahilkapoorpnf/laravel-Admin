@extends('layouts.admin_dashboard')

@section('content')

<!-- Datatables Header -->
<div class="content-header">
    <div class="header-section">
        <h1>
            <i class="fa fa-table"></i>User Type Management<br><small>You can manage your entire registered users!</small>
        </h1>
    </div>
</div>
<ul class="breadcrumb breadcrumb-top">
    <li>Users</li>
    <li><a href="{{url('admin/manage-users-type')}}">Manage User Types</a></li>
</ul>
<!-- END Datatables Header -->

<!-- Datatables Content -->
<div class="block full">
    <div class="block-title">
        <h2><strong>Manage</strong> user type</h2>
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
                    <th>Title</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @php
                $i = 1
                @endphp
                @foreach($allUserTypes as $allUser)
                @php $encodedUserId = encrypt($allUser->id) @endphp
                <tr>
                    <td class="text-center">{{$i}}</td>
                    <td><a href="javascript:void(0)">{{$allUser->title}}</a></td>
                    <td><span title="click to change status" onclick="window.location.href='{{url('admin/update-users-type-status/'.$encodedUserId)}}'" class="label {{$allUser->status=='1' ? 'label-success' : 'label-danger'}}">{{$allUser->status=='1' ? 'Click to Deactive' : 'Click to Activate'}}</span></td>                    <td class="text-center">
                        <div class="btn-group">
                            <a href="javascript:void(0)" onclick="window.location.href='{{url('admin/edit-user-type/'.$encodedUserId)}}'" data-toggle="tooltip" title="Edit" class="btn btn-xs btn-default"><i class="fa fa-pencil"></i></a>
                            <a href="javascript:void(0)" onclick="window.location.href='{{url('admin/delete-user-type/'.$encodedUserId)}}'" data-toggle="tooltip" title="Delete" class="btn btn-xs btn-danger"><i class="fa fa-times"></i></a>
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

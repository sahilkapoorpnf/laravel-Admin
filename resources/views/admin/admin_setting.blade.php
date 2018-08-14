@extends('layouts.admin_dashboard')

@section('content')

<!-- Datatables Header -->
<div class="content-header">
    <div class="header-section">
        <h1>
            <i class="fa fa-table"></i>User Management<br><small>You can add new user from here to submit this form!</small>
        </h1>
    </div>
</div>
<ul class="breadcrumb breadcrumb-top">
    <li>Users</li>
    <li><a href="{{url('admin/add-user')}}">Add Users</a></li>
</ul>
<!-- END Datatables Header -->
<div class="col-md-12">
    <!-- Form Validation Example Block -->
    <div class="block">
        <!-- Form Validation Example Title -->
        <div class="block-title">
            <h2><strong>Add</strong> User</h2>
        </div>
          @if(session()->has('success'))
    <div class="alert alert-success">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        {{ session()->get('success') }}
    </div>
    @endif
    
         @if(session()->has('error'))
    <div class="alert alert-danger">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        {{ session()->get('error') }}
    </div>
    @endif
        <!-- END Form Validation Example Title -->

        <!-- Form Validation Example Content -->
        <form id="form-validation" action="" method="post" class="form-horizontal form-bordered" enctype="multipart/form-data">
            {{ csrf_field() }}
            <fieldset>
                <legend><i class="fa fa-angle-right"></i> User Info</legend>
                <div class="form-group">
                    <label class="col-md-4 control-label" for="val_username">Current Password <span class="text-danger">*</span></label>
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="password" id="val_username" value="" name="current_password" class="form-control" placeholder="Current Password">
                            <span class="input-group-addon"><i class="gi gi-user"></i></span>
                        </div>
                        <span class="text-danger">{{ $errors->first('current_password') }}</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-4 control-label" for="val_email">Password <span class="text-danger">*</span></label>
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="password"  id="val_email" name="password" value="" class="form-control" placeholder="Password">
                            <span class="input-group-addon"><i class="gi gi-user"></i></span>
                        </div>
                        <span class="text-danger">{{ $errors->first('password') }}</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-md-4 control-label" for="val_email">Re-enter Password <span class="text-danger">*</span></label>
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="password"  id="val_email" name="password_confirmation" value="" class="form-control" placeholder="Confirm Password">
                            <span class="input-group-addon"><i class="gi gi-user"></i></span>
                        </div>
                        <span class="text-danger">{{ $errors->first('password_confirmation') }}</span>
                    </div>
                </div>
                
                <div class="form-group form-actions">
                    <div class="col-md-8 col-md-offset-4">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-arrow-right"></i> Submit</button>
                        <button type="reset" class="btn btn-sm btn-warning"><i class="fa fa-repeat"></i> Reset</button>
                    </div>
                </div>
            </fieldset>

        </form>
        <!-- END Form Validation Example Content -->


    </div>
    <!-- END Validation Block -->
</div>


@endsection

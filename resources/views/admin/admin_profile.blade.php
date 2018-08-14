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
        <!-- END Form Validation Example Title -->

        <!-- Form Validation Example Content -->
        <form id="form-validation" action="" method="post" class="form-horizontal form-bordered" enctype="multipart/form-data">
            {{ csrf_field() }}
            <fieldset>
                <legend><i class="fa fa-angle-right"></i> User Info</legend>
                <div class="form-group">
                    <label class="col-md-4 control-label" for="val_username">Username <span class="text-danger">*</span></label>
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" id="val_username" value="{{$userData->name}}" name="username" class="form-control" placeholder="Your username..">
                            <span class="input-group-addon"><i class="gi gi-user"></i></span>
                        </div>
                        <span class="text-danger">{{ $errors->first('username') }}</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-4 control-label" for="val_email">Email <span class="text-danger">*</span></label>
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" readonly="" id="val_email" name="email" value="{{$userData->email }}" class="form-control" placeholder="test@example.com">
                            <span class="input-group-addon"><i class="gi gi-envelope"></i></span>
                        </div>
                        <span class="text-danger">{{ $errors->first('email') }}</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-md-4 control-label" for="val_email">Image <span class="text-danger">*</span></label>
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="file" id="val_email" name="image" value="" class="form-control" >
                            <span class="input-group-addon"><i class="gi gi-file"></i></span>
                        </div>
                        <span class="text-danger">{{ $errors->first('image') }}</span>
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

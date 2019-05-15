@extends('layouts.admin_dashboard')

@section('content')

<!-- Datatables Header -->
<div class="content-header">
    <div class="header-section">
        <h1>
            <i class="fa fa-table"></i>Terms<br><small>You can add new term from here to submit this form!</small>
        </h1>
    </div>
</div>
<ul class="breadcrumb breadcrumb-top">
    <li>Terms</li>
    <li><a href="{{url('admin/add-user-type')}}">Add Term</a></li>
</ul>
<!-- END Datatables Header -->
<div class="col-md-12">
    <!-- Form Validation Example Block -->
    <div class="block">
        <!-- Form Validation Example Title -->
        <div class="block-title">
            <h2><strong>Add</strong> Terms</h2>
        </div>
        <!-- END Form Validation Example Title -->

        <!-- Form Validation Example Content -->
        <form id="form-validation" action="" method="post" class="form-horizontal form-bordered">
            {{ csrf_field() }}
            <fieldset>
                <legend><i class="fa fa-angle-right"></i> Term Info</legend>
                <div class="form-group">
                    <label class="col-md-4 control-label" for="val_username">Title <span class="text-danger">*</span></label>
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" id="val_username" value="{{old('title') }}" name="title" class="form-control" placeholder="Your title..">
                            <span class="input-group-addon"><i class="gi gi-user"></i></span>
                        </div>
                        <span class="text-danger">{{ $errors->first('title') }}</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-4 control-label" for="val_username">Description <span class="text-danger">*</span></label>
                    <div class="col-md-6">
                        <div class="input-group">
                            <textarea class="form-control" id="val_username" name="description"></textarea>
                            <span class="input-group-addon"></span>
                        </div>
                        <span class="text-danger">{{ $errors->first('description') }}</span>
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

@extends('layouts.admin_dashboard')

@section('content')

<!-- Datatables Header -->
<div class="content-header">
    <div class="header-section">
        <h1>
            <i class="fa fa-table"></i>Flash Screen Management<br><small>You can update flash screen from here to submit this form!</small>
        </h1>
    </div>
</div>
<ul class="breadcrumb breadcrumb-top">
    <li>Mobile</li>
    <li><a href="{{url('admin/update-flash-screen')}}">Flash Screen</a></li>
</ul>
<!-- END Datatables Header -->
<div class="col-md-12">
    <!-- Form Validation Example Block -->
    <div class="block">
        <!-- Form Validation Example Title -->
        <div class="block-title">
            <h2><strong>Edit</strong> User</h2>
        </div>
        <!-- END Form Validation Example Title -->
      
        <!-- Form Validation Example Content -->
        <form id="form-validation" action="" method="post" enctype="multipart/form-data" class="form-horizontal form-bordered">
            {{ csrf_field() }}
            <fieldset>
                <legend><i class="fa fa-angle-right"></i> User Info</legend>
                <div class="form-group">
                    <label class="col-md-4 control-label" for="val_username">Title <span class="text-danger">*</span></label>
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" id="val_username" value="{{$userData->title}}" name="title" class="form-control" placeholder="Your title..">
                            <span class="input-group-addon"><i class="gi gi-user"></i></span>
                        </div>
                        <span class="text-danger">{{ $errors->first('title') }}</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-4 control-label" for="val_username">Description <span class="text-danger">*</span></label>
                    <div class="col-md-6">
                        <div class="input-group">
                            <textarea class="form-control" name="short_description">{{$userData->short_description}}</textarea>
                            <span class="input-group-addon"></span>
                        </div>
                        <span class="text-danger">{{ $errors->first('short_description') }}</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-4 control-label" for="val_username">Images <span class="text-danger">*</span></label>
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="file" name="images[]" multiple accept="image/gif, image/jpeg, image/png" class="form-control">
                            <span class="input-group-addon"></span>
                        </div>
                        <span class="text-danger">{{ $errors->first('images') }}</span>
                    </div>
                </div>
                <div class="text-center">
                    @php
                    $images = explode(',',$userData->images);
                    @endphp
                    
                    @foreach($images as $image)
                    
                    <img src="{{url('../storage/flash_images/'.$image)}}" height="50" width="50" >
                        
                    @endforeach
                   
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

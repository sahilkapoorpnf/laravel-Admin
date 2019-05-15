@extends('layouts.admin_dashboard')

@section('content')

<!-- Datatables Header -->
<div class="content-header">
    <div class="header-section">
        <h1>
            <i class="fa fa-table"></i>Refer Amount Management<br><small>You can update refer amount from here to submit this form!</small>
        </h1>
    </div>
</div>
<ul class="breadcrumb breadcrumb-top">
    <li>Mobile</li>
    <li><a href="{{url('admin/update-refer-amount')}}">Refer Amount</a></li>
</ul>
<!-- END Datatables Header -->
<div class="col-md-12">
    <!-- Form Validation Example Block -->
    <div class="block">
        <!-- Form Validation Example Title -->
        <div class="block-title">
            <h2><strong>Edit</strong> Refer Amount</h2>
        </div>
        <!-- END Form Validation Example Title -->
      @if(session()->has('success'))
        <div class="alert alert-success">
            {{ session()->get('success') }}
        </div>
    @endif
        <!-- Form Validation Example Content -->
        <form id="form-validation" action="" method="post" enctype="multipart/form-data" class="form-horizontal form-bordered">
            {{ csrf_field() }}
            <fieldset>
                <legend><i class="fa fa-angle-right"></i> Refer Amount Info</legend>
                <div class="form-group">
                    <label class="col-md-4 control-label" for="val_username">Amount (AED)<span class="text-danger">*</span></label>
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" id="val_username" value="{{$referData->amount}}" name="amount" class="form-control" placeholder="Your amount..">
                            <span class="input-group-addon"><i class="gi gi-user"></i></span>
                        </div>
                        <span class="text-danger">{{ $errors->first('amount') }}</span>
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

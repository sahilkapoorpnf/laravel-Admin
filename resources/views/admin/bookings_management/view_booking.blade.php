@extends('layouts.admin_dashboard')

@section('content')

<!-- Datatables Header -->
<div class="content-header">
    <div class="header-section">
        <h1>
            <i class="fa fa-table"></i>View Booking<br><small>You can see entire booking detail over here!</small>
        </h1>
    </div>
</div>
<ul class="breadcrumb breadcrumb-top">
    <li>Bookings</li>
    <li><a href="{{url('admin/manage-bookings')}}">View Booking</a></li>
</ul>
<!-- END Datatables Header -->

<div class="block" style="overflow:hidden;">
	
	<div class="block-title">
		<div class="block-options pull-right">
			<a href="javascript:void(0)" class="btn btn-alt btn-sm btn-success">{{ucfirst($final_data[0]->meeting_status)}}</a>
<!--			<a href="javascript:void(0)" class="btn btn-alt btn-sm btn-danger" style="display:none;">Pending</a>
			<a href="javascript:void(0)" class="btn btn-alt btn-sm btn-warning" style="display:none;">Process</a>-->
		</div>
		<h2><strong>{{$final_data[0]->booking_title}} <small>• ({{$final_data[0]->id}})</small></strong>
		<small>• <i class="fa fa-map-marker text-primary"></i> {{$final_data[0]->booking_location}}</small>	
		</h2>
	</div>
	
	<div class="col-md-6 col-sm-12">
		<div class="bookus_details">
			<h5 class="title">User Details</h5>
			<div class="table-responsive">
				<table class="table table-bordered table-striped">
					<tbody>				
						<tr>
							<td><strong>Name</strong></td>
							<td class="text-primary">{{$final_data[0]->get_user_name}}</td>
						</tr>
						<tr>
							<td><strong>Email</strong></td>
							<td class="text-primary">{{$final_data[0]->user_email}}</td>
						</tr>
						<tr>
							<td><strong>Phone</strong></td>
							<td class="text-primary">{{$final_data[0]->user_phone}}</td>
						</tr>				
					</tbody>
				</table> 
			</div>
		</div>
	</div>
	<div class="col-md-6 col-sm-12">
		<div class="bookus_details">
			<h5 class="title">Stylist Details</h5>
			<div class="table-responsive">
				<table class="table table-bordered table-striped">
					<tbody>				
						<tr>
							<td><strong>Name</strong></td>
							<td class="text-primary">{{$final_data[0]->get_stylist_name}}</td>
						</tr>
						<tr>
							<td><strong>Email</strong></td>
							<td class="text-primary">{{$final_data[0]->stylist_email}}</td>
						</tr>
						<tr>
							<td><strong>Phone</strong></td>
							<td class="text-primary">{{$final_data[0]->stylist_phone}}</td>
						</tr>				
					</tbody>
				</table> 
			</div>
		</div>
	</div>
	
	<div class="col-sm-12">
		<div class="bookus_details">
			<h5 class="title">Payment Details</h5>
			<div class="table-responsive">
				<table class="table table-bordered table-striped">
					<thead>
						<tr>
							<th>S.No</th>
							<th>Payment Id</th>
							<th>Time</th>
							<th>Amount</th>
						</tr>
					</thead>
					<tbody>
                                            @php $i = 1; 
                                            $total_sum = 0;
                                            @endphp
                                            @foreach($final_data as $final_datas)
                                            @php
                                            $total_sum+=$final_datas->payment_amount;
                                            @endphp
						<tr>
							<td>{{$i}}</td>
                                                        @if($final_datas->payment_id!='')
							<td>{{$final_datas->payment_id}}</td>
                                                        @else
							<td>Wallet</td>
                                                        @endif
                                                         @if($final_datas->payment_id=='')
							<td>00:60:00</td>
                                                        @else
                                                        <td>{{$final_datas->time}}</td>
                                                        @endif
							<td>AED. {{$final_datas->payment_amount}}</td>
						</tr>
                                                 @php $i++; @endphp
					    @endforeach	
						<tr>							
							<td colspan="3" class="text-right" style="font-size:15px;"><b>Total Amount</b></td>
							<td class="text-primary" style="font-size:15px;"><b>AED. {{$total_sum}}</b></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	
</div>


@endsection

$(document).ready(function () {
	$('#PaymentForm_OrderForm_BillingAddressCheckoutComponent_PostalCode').change(function () {
		if($('.shippingcheckoutcomponent li.droppoint input:checked').length > 0){
			if ($('#DeliveryIsBilling:checked').length > 0) {
				updateDroppoints();
			}
		}
	});

	$('#PaymentForm_OrderForm_ShippingAddressCheckoutComponent_PostalCode').change(function () {
		if($('.shippingcheckoutcomponent li.droppoint input:checked').length > 0){
			if (!$('#DeliveryIsBilling:checked').length > 0) {
				updateDroppoints();
			}
		}
	});

	$('#PaymentForm_OrderForm_ShippingCheckoutComponent_ShippingMethodID input.radio').change(function(){
		console.log('shipment change');
		var val = $(this).val();
		if ($(this).parent().parent().hasClass('droppoint')) {
			moveSelectBox($(this));
		}else{
			$('#PaymentForm_OrderForm_ShippingCheckoutComponent_WebshipperDroppoint').removeAttr('required');
		}

		//Get new total (in case deliverymethod isn't free)
		$('.ordersummary').addClass('loading');
		$.ajax({
			url: window.location + 'updateOrderTotal',
			type: "POST",
			data: {id: val},
			success: function (data) {
				if (data) {
					$('.ordersummary').removeClass('loading').html(data);
				}
			}
		});
	});
	var selectedDroppoingShipping = $('.shippingcheckoutcomponent li.droppoint input:checked')
	if($(selectedDroppoingShipping).length > 0){
		moveSelectBox($(selectedDroppoingShipping));
	}


});

function moveSelectBox(shippingMethod) {
	console.log('move box');
	var droppointSelector = $('#PaymentForm_OrderForm_ShippingCheckoutComponent_WebshipperDroppoint');
	droppointSelector.appendTo($(shippingMethod).parent());
	droppointSelector.attr('required', 'required');
	updateDroppoints();
}

function updateDroppoints() {
	//Add loading animation
	$('#ShippingCheckoutComponent_ShippingMethodID #PaymentForm_OrderForm_ShippingCheckoutComponent_ShippingMethodID').addClass('loading');
	var droppointSelector = $('#PaymentForm_OrderForm_ShippingCheckoutComponent_WebshipperDroppoint');

	if ($('#DeliveryIsBilling:checked').length > 0) {
		var zipCode = $('#PaymentForm_OrderForm_BillingAddressCheckoutComponent_PostalCode').val();
		var country = $('#PaymentForm_OrderForm_BillingAddressCheckoutComponent_Country').val();
	}else{
		var zipCode = $('#PaymentForm_OrderForm_ShippingAddressCheckoutComponent_PostalCode').val();
		var country = $('#PaymentForm_OrderForm_ShippingAddressCheckoutComponent_Country').val();
	}

	var selectedShipping = $('.shippingcheckoutcomponent li.droppoint input:checked').val();

	//Fetch droppoint from shipping zip code
	$.ajax({
		url: window.location + 'getDroppoints',
		type: "POST",
		data: {zip: zipCode, country: country, selectedShipping: selectedShipping},
		success: function (data) {
			if (data) {
				//Remove any old optios except "Please select option"
				droppointSelector.children('option:not(:first)').remove();
				var jsonData = JSON.parse(data);
				$.each(jsonData, function(key,value) {
					droppointSelector.append($("<option></option>").attr("value", value['drop_point_id']).text(value['name'] + ', ' + value['address_1'] + ', ' + value['zip'] + ' ' + value['city']));
				});
				$('#ShippingCheckoutComponent_ShippingMethodID #PaymentForm_OrderForm_ShippingCheckoutComponent_ShippingMethodID').removeClass('loading');
			}
		}
	});
}

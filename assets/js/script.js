jQuery(function($) {

	'use strict';

	$(".cnphem-select").select2();

	$(document).ready( cnphem_admin_page_update );
	$(document).on('change' , $("#cnphem_cartAlways") , cnphem_admin_page_update );

	function cnphem_admin_page_update(){
		if ($('#cnphem_cartAlways').is(":checked")) {
			$('.cart_page_products').hide();
			$('.cart_page_pro_cats').hide();
		}
		else{
			$('.cart_page_products').show();
			$('.cart_page_pro_cats').show();
		}
	}

});